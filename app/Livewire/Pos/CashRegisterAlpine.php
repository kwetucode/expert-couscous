<?php

declare(strict_types=1);

namespace App\Livewire\Pos;

use App\Services\Pos\PaymentService;
use App\Services\Pos\PaymentData;
use App\Services\Pos\StatsService;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ProductVariantRepository;
use App\Repositories\ClientRepository;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Composant POS optimisé avec Alpine.js
 *
 * Ce composant gère uniquement les opérations backend:
 * - Chargement initial des données (produits, clients)
 * - Validation et sauvegarde de la vente
 * - Statistiques du jour
 *
 * Tout l'état du panier est géré côté client par Alpine.js
 */
class CashRegisterAlpine extends Component
{
    // Données initiales à charger
    public array $products = [];
    public array $clients = [];
    public array $categories = [];
    public ?int $defaultClientId = null;

    // Stats du jour
    public array $todayStats = [
        'sales_count' => 0,
        'revenue' => 0,
        'transactions' => 0
    ];
    public bool $showStats = false;

    // Services & Repositories
    private PaymentService $paymentService;
    private StatsService $statsService;
    private ProductRepository $productRepository;
    private ProductVariantRepository $variantRepository;
    private ClientRepository $clientRepository;

    public function boot(
        PaymentService $paymentService,
        StatsService $statsService,
        ProductRepository $productRepository,
        ProductVariantRepository $variantRepository,
        ClientRepository $clientRepository
    ): void {
        $this->paymentService = $paymentService;
        $this->statsService = $statsService;
        $this->productRepository = $productRepository;
        $this->variantRepository = $variantRepository;
        $this->clientRepository = $clientRepository;
    }

    public function mount(): void
    {
        $this->loadInitialData();
        $this->loadTodayStats();
    }

    /**
     * Obtient le storeId courant (même logique que PosProductGrid)
     */
    private function getCurrentStoreId(): ?int
    {
        $storeId = current_store_id();
        $user = auth()->user();

        if (!user_can_access_all_stores() && !$storeId && $user) {
            $firstStore = $user->stores()->first();
            if ($firstStore) {
                $user->update(['current_store_id' => $firstStore->id]);
                return $firstStore->id;
            }
        }

        return $storeId;
    }

    /**
     * Charge les données initiales pour Alpine.js
     * Utilise les Repositories comme PosProductGrid
     */
    private function loadInitialData(): void
    {
        $storeId = $this->getCurrentStoreId();
        $canAccessAllStores = user_can_access_all_stores();

        // Charger les produits avec variants et stock via ProductRepository
        $query = $this->productRepository->query()
            ->with([
                'category:id,name',
                'variants' => function($q) use ($storeId, $canAccessAllStores) {
                    $q->select('id', 'product_id', 'size', 'color', 'sku', 'stock_quantity');

                    // Toujours charger storeStocks pour éviter le lazy loading
                    if ($storeId) {
                        $q->with(['storeStocks' => function($sq) use ($storeId) {
                            $sq->where('store_id', $storeId);
                        }]);
                    } else {
                        // Charger tous les storeStocks si accès à tous les stores
                        $q->with('storeStocks');
                    }

                    // Filtrer les variantes avec du stock
                    if ($storeId && !$canAccessAllStores) {
                        $q->whereHas('storeStocks', function($sq) use ($storeId) {
                            $sq->where('store_id', $storeId)->where('quantity', '>', 0);
                        });
                    } else {
                        $q->where('stock_quantity', '>', 0);
                    }
                }
            ])
            ->select('id', 'name', 'reference', 'price', 'max_discount_amount', 'stock_alert_threshold', 'category_id', 'status', 'image', 'store_id')
            ->where('status', 'active');

        // Filtre par magasin - produit doit avoir du stock dans le magasin OU être créé par ce magasin
        if (!$canAccessAllStores && $storeId) {
            $query->where(function($q) use ($storeId) {
                // Produit créé par ce magasin
                $q->where('store_id', $storeId)
                // OU produit ayant du stock dans ce magasin
                ->orWhereHas('variants.storeStocks', function($sq) use ($storeId) {
                    $sq->where('store_id', $storeId)->where('quantity', '>', 0);
                });
            });
        } elseif (!$canAccessAllStores && !$storeId) {
            $query->whereRaw('1 = 0');
        }

        $this->products = $query->orderBy('name')
        ->get()
        ->filter(function($product) {
            // Garder uniquement les produits qui ont des variants avec du stock
            return $product->variants->count() > 0;
        })
        ->map(function($product) use ($storeId) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'reference' => $product->reference,
                'barcode' => $product->barcode,
                'price' => (float) $product->price,
                'max_discount_amount' => (float) ($product->max_discount_amount ?? 0),
                'stock_alert_threshold' => (int) ($product->stock_alert_threshold ?? 10),
                'category_id' => $product->category_id,
                'category' => $product->category?->name ?? 'Sans catégorie',
                'variants' => $product->variants->map(function($variant) use ($product, $storeId) {
                    // Obtenir le stock du magasin actif
                    $stockQuantity = $storeId && $variant->storeStocks->isNotEmpty()
                        ? $variant->storeStocks->first()->quantity
                        : $variant->stock_quantity;

                    return [
                        'id' => $variant->id,
                        'size' => $variant->size,
                        'color' => $variant->color,
                        'stock_quantity' => $stockQuantity,
                        'product' => [
                            'id' => $product->id,
                            'name' => $product->name,
                            'price' => (float) $product->price,
                            'max_discount_amount' => (float) ($product->max_discount_amount ?? 0),
                        ]
                    ];
                })->toArray()
            ];
        })
        ->values()
        ->toArray();



        // Charger les clients via ClientRepository
        $organizationId = auth()->user()?->default_organization_id;
        $this->clients = $organizationId
            ? $this->clientRepository->query()
                ->where('organization_id', $organizationId)
                ->select('id', 'name', 'email', 'phone')
                ->orderBy('name')
                ->get()
                ->toArray()
            : [];

        // Client par défaut (Comptant) via ClientRepository
        $this->defaultClientId = $organizationId
            ? Cache::remember(
                "pos.default_client_id.{$organizationId}",
                3600,
                fn() => $this->clientRepository->query()
                    ->where('organization_id', $organizationId)
                    ->where(function($query) {
                        $query->where('name', 'Comptant')
                              ->orWhere('name', 'Client Comptant')
                              ->orWhere('name', 'LIKE', '%Comptant%');
                    })
                    ->first()
                    ?->id
            )
            : null;

        // Charger les catégories depuis le repository comme PosProductGrid
        $this->categories = $this->getCachedCategories($storeId)
            ->map(fn($cat) => ['id' => $cat->id, 'name' => $cat->name])
            ->toArray();
    }

    /**
     * Récupère les catégories avec mise en cache
     */
    private function getCachedCategories(?int $storeId): \Illuminate\Support\Collection
    {
        $cacheKey = $storeId ? "pos.categories.store.{$storeId}" : 'pos.categories.all';

        return Cache::remember(
            $cacheKey,
            3600,
            fn() => app(CategoryRepository::class)->all()
        );
    }

    /**
     * Charge les statistiques du jour
     */
    private function loadTodayStats(): void
    {
        $this->todayStats = $this->statsService->loadTodayStats($this->getUserId());
    }

    /**
     * Obtient l'ID de l'utilisateur authentifié
     */
    private function getUserId(): int
    {
        /** @var int|null $userId */
        $userId = auth()->id();
        if (!$userId) {
            throw new \RuntimeException('Utilisateur non authentifié');
        }
        return $userId;
    }

    /**
     * Bascule l'affichage des statistiques
     */
    public function toggleStats(): void
    {
        $this->showStats = !$this->showStats;
    }

    /**
     * Traite et sauvegarde la vente (appelé par Alpine.js)
     *
     * @param array $saleData ['cart' => [], 'client_id' => int, 'payment' => []]
     * @return array ['success' => bool, 'sale_id' => int, 'receipt_url' => string, 'message' => string]
     */
    public function processSale(array $saleData): array
    {
        DB::beginTransaction();

        try {
            // Validation des données
            if (empty($saleData['cart'])) {
                return [
                    'success' => false,
                    'message' => 'Le panier est vide'
                ];
            }

            if (!isset($saleData['client_id']) || !$saleData['client_id']) {
                return [
                    'success' => false,
                    'message' => 'Client non sélectionné'
                ];
            }

            if (empty($saleData['payment'])) {
                return [
                    'success' => false,
                    'message' => 'Informations de paiement manquantes'
                ];
            }

            // Vérifier que le client appartient à l'organisation via ClientRepository
            $organizationId = session('active_organization_id');
            $client = $this->clientRepository->query()
                ->where('id', $saleData['client_id'])
                ->where('organization_id', $organizationId)
                ->first();

            if (!$client) {
                return [
                    'success' => false,
                    'message' => 'Client invalide'
                ];
            }

            // Vérifier le stock en temps réel pour chaque article via ProductVariantRepository
            $storeId = session('active_store_id');
            foreach ($saleData['cart'] as $item) {
                $variant = $this->variantRepository->query()
                    ->where('id', $item['variant_id'])
                    ->where('store_id', $storeId)
                    ->first();

                if (!$variant) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => "Produit {$item['product_name']} non trouvé"
                    ];
                }

                if ($variant->stock_quantity < $item['quantity']) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => "Stock insuffisant pour {$item['product_name']}. Disponible: {$variant->stock_quantity}"
                    ];
                }
            }

            // Préparer les données pour le service de paiement
            $cartItems = collect($saleData['cart'])->map(function($item) {
                return [
                    'product_variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                ];
            })->toArray();

            // Calculer les totaux
            $total = collect($saleData['cart'])->sum(fn($item) => $item['price'] * $item['quantity']);
            $amountReceived = $saleData['payment']['amount_received'] ?? $total;

            // Créer l'objet PaymentData
            $paymentData = new PaymentData(
                userId: $this->getUserId(),
                clientId: $saleData['client_id'],
                storeId: $storeId,
                paymentMethod: $saleData['payment']['method'] ?? 'cash',
                items: $cartItems,
                discount: 0,
                tax: 0,
                paidAmount: $amountReceived,
                total: $total,
                notes: null,
                stockValidation: [] // Sera validé par le service
            );

            // Traiter la vente via le PaymentService
            $result = $this->paymentService->process($paymentData);

            if (!$result->success) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => $result->error ?? 'Erreur lors du traitement'
                ];
            }

            DB::commit();

            // Récupérer l'ID de la vente
            $saleId = $result->sale?->id;
            if (!$saleId) {
                return [
                    'success' => false,
                    'message' => 'Vente créée mais ID non disponible'
                ];
            }

            // Rafraîchir les statistiques
            $this->statsService->invalidateStatsCache($this->getUserId());
            $this->loadTodayStats();

            // Émettre événement de succès
            $this->dispatch('sale-completed', saleId: $saleId);

            return [
                'success' => true,
                'sale_id' => $saleId,
                'receipt_url' => route('sales.receipt', $saleId),
                'message' => 'Vente enregistrée avec succès'
            ];

        } catch (\App\Exceptions\Pos\InsufficientStockException $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (\App\Exceptions\Pos\InsufficientPaymentException $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur POS Alpine:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->getUserId(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la vente. Veuillez réessayer.'
            ];
        }
    }

    /**
     * Recherche un produit par code-barres via ProductVariantRepository
     */
    public function searchByBarcode(string $barcode): ?array
    {
        $storeId = session('active_store_id');
        $organizationId = session('active_organization_id');

        $variant = $this->variantRepository->query()
            ->whereHas('product', function($query) use ($barcode, $organizationId) {
                $query->where('organization_id', $organizationId)
                      ->where('active', true)
                      ->where(function($q) use ($barcode) {
                          $q->where('reference', $barcode)
                            ->orWhere('barcode', $barcode);
                      });
            })
            ->where('store_id', $storeId)
            ->where('stock_quantity', '>', 0)
            ->with('product')
            ->first();

        if (!$variant) {
            return null;
        }

        return [
            'id' => $variant->id,
            'size' => $variant->size,
            'color' => $variant->color,
            'stock_quantity' => $variant->stock_quantity,
            'product' => [
                'id' => $variant->product->id,
                'name' => $variant->product->name,
                'price' => (float) $variant->product->price,
                'max_discount_amount' => (float) ($variant->product->max_discount_amount ?? 0),
            ]
        ];
    }

    /**
     * Écoute le rafraîchissement des stats
     */
    #[On('stats-refresh')]
    public function refreshStats(): void
    {
        $this->statsService->invalidateStatsCache($this->getUserId());
        $this->loadTodayStats();
    }

    /**
     * Rafraîchit les produits et retourne les données mises à jour
     */
    public function refreshProducts(): array
    {
        $this->loadInitialData();
        return $this->products;
    }

    /**
     * Écoute la complétion d'une vente
     */
    #[On('sale-completed')]
    public function onSaleCompleted(): void
    {
        $this->refreshStats();
        $this->loadInitialData(); // Recharger les produits avec le nouveau stock
        
        // Dispatcher un événement pour mettre à jour Alpine.js
        $this->dispatch('products-updated', products: $this->products);
    }

    public function render()
    {
        return view('livewire.pos.cash-register-alpine');
    }
}

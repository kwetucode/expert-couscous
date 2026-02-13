<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Actions\Stock\AddStockAction;
use App\Actions\Stock\AdjustStockAction;
use App\Actions\Stock\RemoveStockAction;
use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Repositories\StockMovementRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Controller API Mobile - Gestion des Mouvements de Stock
 *
 * Permet d'ajouter, retirer et ajuster le stock via l'API mobile
 */
class MobileStockMovementController extends Controller
{
    public function __construct(
        private StockMovementRepository $movementRepository,
        private AddStockAction $addStockAction,
        private RemoveStockAction $removeStockAction,
        private AdjustStockAction $adjustStockAction,
    ) {}

    /**
     * Liste des mouvements de stock récents
     *
     * GET /api/mobile/stock/movements
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Rafraîchir l'utilisateur pour obtenir le current_store_id à jour
            $user = Auth::user()->fresh();

            $perPage = (int) $request->input('per_page', 20);
            $perPage = min(max($perPage, 10), 100);

            $type = $request->input('type'); // in, out
            $movementType = $request->input('movement_type'); // purchase, sale, adjustment, transfer, return
            $variantId = $request->input('variant_id');

            $query = StockMovement::with(['productVariant.product', 'user'])
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc');

            // Appliquer le filtre de store automatiquement
            if (!user_can_access_all_stores() && current_store_id()) {
                $query->where('store_id', current_store_id());
            }

            if ($type) {
                $query->where('type', $type);
            }

            if ($movementType) {
                $query->where('movement_type', $movementType);
            }

            if ($variantId) {
                $query->where('product_variant_id', $variantId);
            }

            $movements = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'movements' => $movements->items(),
                    'pagination' => [
                        'current_page' => $movements->currentPage(),
                        'last_page' => $movements->lastPage(),
                        'per_page' => $movements->perPage(),
                        'total' => $movements->total(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des mouvements',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Détail d'un mouvement de stock
     *
     * GET /api/mobile/stock/movements/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $movement = StockMovement::with(['productVariant.product', 'user', 'store'])
                ->find($id);

            if (!$movement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mouvement non trouvé',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatMovement($movement),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du mouvement',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Ajouter du stock (entrée)
     *
     * POST /api/mobile/stock/movements/add
     */
    public function addStock(Request $request): JsonResponse
    {
        // Vérifier que l'organisation n'est pas de type service uniquement
        if (is_service_organization()) {
            return response()->json([
                'success' => false,
                'message' => 'La gestion de stock n\'est pas disponible pour les organisations de type service.',
                'error' => 'stock_not_available_for_services',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'movement_type' => 'nullable|in:purchase,return,adjustment,transfer',
            'reference' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:500',
            'unit_price' => 'nullable|numeric|min:0',
            'date' => 'nullable|date',
            'update_product_cost' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['user_id'] = Auth::id();
            $data['movement_type'] = $data['movement_type'] ?? StockMovement::MOVEMENT_PURCHASE;

            // Générer une référence automatique si non fournie
            if (empty($data['reference'])) {
                $data['reference'] = $this->generateReference($data['movement_type']);
            }

            $movement = $this->addStockAction->execute($data);

            return response()->json([
                'success' => true,
                'message' => 'Stock ajouté avec succès',
                'data' => $this->formatMovement($movement),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Retirer du stock (sortie)
     *
     * POST /api/mobile/stock/movements/remove
     */
    public function removeStock(Request $request): JsonResponse
    {
        // Vérifier que l'organisation n'est pas de type service uniquement
        if (is_service_organization()) {
            return response()->json([
                'success' => false,
                'message' => 'La gestion de stock n\'est pas disponible pour les organisations de type service.',
                'error' => 'stock_not_available_for_services',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'movement_type' => 'nullable|in:sale,adjustment,transfer,return',
            'reference' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:500',
            'date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['user_id'] = Auth::id();
            $data['movement_type'] = $data['movement_type'] ?? StockMovement::MOVEMENT_ADJUSTMENT;

            // Générer une référence automatique si non fournie
            if (empty($data['reference'])) {
                $data['reference'] = $this->generateReference($data['movement_type']);
            }

            // Vérifier le stock disponible
            $variant = ProductVariant::find($data['product_variant_id']);
            if (!$variant->hasStock($data['quantity'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Stock insuffisant. Disponible: {$variant->stock_quantity}, Demandé: {$data['quantity']}",
                ], 400);
            }

            $movement = $this->removeStockAction->execute($data);

            return response()->json([
                'success' => true,
                'message' => 'Stock retiré avec succès',
                'data' => $this->formatMovement($movement),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Ajuster le stock (correction)
     *
     * POST /api/mobile/stock/movements/adjust
     */
    public function adjustStock(Request $request): JsonResponse
    {
        // Vérifier que l'organisation n'est pas de type service uniquement
        if (is_service_organization()) {
            return response()->json([
                'success' => false,
                'message' => 'La gestion de stock n\'est pas disponible pour les organisations de type service.',
                'error' => 'stock_not_available_for_services',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'product_variant_id' => 'required|exists:product_variants,id',
            'new_quantity' => 'required|integer|min:0',
            'reason' => 'required|string|max:500',
            'reference' => 'nullable|string|max:255',
            'date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['user_id'] = Auth::id();

            // Récupérer le stock actuel pour info
            $variant = ProductVariant::find($data['product_variant_id']);
            $currentStock = $variant->stock_quantity;
            $difference = $data['new_quantity'] - $currentStock;

            // Pas de mouvement si pas de différence
            if ($difference === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Aucun ajustement nécessaire, le stock est déjà à cette valeur',
                    'data' => [
                        'current_stock' => $currentStock,
                        'new_quantity' => $data['new_quantity'],
                        'difference' => 0,
                    ],
                ]);
            }

            $movement = $this->adjustStockAction->execute($data);

            return response()->json([
                'success' => true,
                'message' => $difference > 0
                    ? "Stock ajusté: +{$difference} unités"
                    : "Stock ajusté: {$difference} unités",
                'data' => [
                    'movement' => $this->formatMovement($movement),
                    'previous_stock' => $currentStock,
                    'new_stock' => $data['new_quantity'],
                    'difference' => $difference,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Récupérer le stock d'une variante
     *
     * GET /api/mobile/stock/variant/{variantId}
     */
    public function getVariantStock(int $variantId): JsonResponse
    {
        try {
            $variant = ProductVariant::with(['product', 'storeStocks.store'])
                ->find($variantId);

            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variante non trouvée',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'name' => $variant->full_name ?? $variant->sku,
                    'product' => [
                        'id' => $variant->product->id,
                        'name' => $variant->product->name,
                    ],
                    'stock_quantity' => $variant->stock_quantity,
                    'low_stock_threshold' => $variant->low_stock_threshold,
                    'min_stock_threshold' => $variant->min_stock_threshold,
                    'is_low_stock' => $variant->isLowStock(),
                    'is_out_of_stock' => $variant->isOutOfStock(),
                    'status' => $variant->stock_status,
                    'store_stocks' => $variant->storeStocks->map(fn($ss) => [
                        'store_id' => $ss->store_id,
                        'store_name' => $ss->store->name,
                        'quantity' => $ss->quantity,
                    ])->toArray(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du stock',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Historique des mouvements d'une variante
     *
     * GET /api/mobile/stock/variant/{variantId}/history
     */
    public function getVariantHistory(Request $request, int $variantId): JsonResponse
    {
        try {
            $variant = ProductVariant::find($variantId);

            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variante non trouvée',
                ], 404);
            }

            $perPage = (int) $request->input('per_page', 20);
            $perPage = min(max($perPage, 10), 100);

            $movements = StockMovement::with(['user'])
                ->where('product_variant_id', $variantId)
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'variant' => [
                        'id' => $variant->id,
                        'name' => $variant->full_name ?? $variant->sku,
                        'current_stock' => $variant->stock_quantity,
                    ],
                    'movements' => collect($movements->items())->map(fn($m) => $this->formatMovement($m)),
                    'pagination' => [
                        'current_page' => $movements->currentPage(),
                        'last_page' => $movements->lastPage(),
                        'per_page' => $movements->perPage(),
                        'total' => $movements->total(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Rechercher des variantes pour sélection
     *
     * GET /api/mobile/stock/search-variants
     */
    public function searchVariants(Request $request): JsonResponse
    {
        try {
            $search = $request->input('search', '');
            $limit = (int) $request->input('limit', 20);
            $limit = min(max($limit, 5), 50);

            $query = ProductVariant::with('product')
                ->whereHas('product', function ($q) use ($search) {
                    if ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    }

                    // Filtrer par store si nécessaire
                    if (!user_can_access_all_stores() && current_store_id()) {
                        $q->where('store_id', current_store_id());
                    }
                });

            if ($search) {
                $query->orWhere('sku', 'like', "%{$search}%");
            }

            $variants = $query->limit($limit)->get();

            return response()->json([
                'success' => true,
                'data' => $variants->map(fn($v) => [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'name' => $v->full_name ?? $v->sku,
                    'product_name' => $v->product->name,
                    'stock_quantity' => $v->stock_quantity,
                    'is_low_stock' => $v->isLowStock(),
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Types de mouvements disponibles
     *
     * GET /api/mobile/stock/movement-types
     */
    public function movementTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'in_types' => [
                    ['value' => 'purchase', 'label' => 'Achat'],
                    ['value' => 'return', 'label' => 'Retour client'],
                    ['value' => 'adjustment', 'label' => 'Ajustement'],
                    ['value' => 'transfer', 'label' => 'Transfert entrant'],
                ],
                'out_types' => [
                    ['value' => 'sale', 'label' => 'Vente'],
                    ['value' => 'return', 'label' => 'Retour fournisseur'],
                    ['value' => 'adjustment', 'label' => 'Ajustement'],
                    ['value' => 'transfer', 'label' => 'Transfert sortant'],
                ],
            ],
        ]);
    }

    /**
     * Vue groupée des mouvements de stock (cohérent avec Livewire StockIndex)
     *
     * GET /api/mobile/stock/movements/grouped
     */
    public function groupedMovements(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 20);
            $perPage = min(max($perPage, 10), 100);

            $type = $request->input('type'); // in, out
            $movementType = $request->input('movement_type'); // purchase, sale, adjustment, transfer, return
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            $query = StockMovement::with(['productVariant.product', 'user'])
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc');

            // Appliquer le filtre de store automatiquement
            if (!user_can_access_all_stores() && current_store_id()) {
                $query->where('store_id', current_store_id());
            }

            if ($type) {
                $query->where('type', $type);
            }

            if ($movementType) {
                $query->where('movement_type', $movementType);
            }

            // Filtre par date
            if ($dateFrom && $dateTo) {
                $query->whereBetween('date', [$dateFrom, $dateTo]);
            } elseif ($dateFrom) {
                $query->whereDate('date', '>=', $dateFrom);
            } elseif ($dateTo) {
                $query->whereDate('date', '<=', $dateTo);
            }

            // Get all movements for grouping
            $allMovements = $query->get();

            // Group movements by product variant
            $groupedMovements = $allMovements->groupBy('product_variant_id')->map(function ($movements) {
                $firstMovement = $movements->first();
                $totalIn = $movements->where('type', 'in')->sum('quantity');
                $totalOut = $movements->where('type', 'out')->sum('quantity');
                $lastDate = $movements->max('date');
                $movementCount = $movements->count();

                return [
                    'product_variant_id' => $firstMovement->product_variant_id,
                    'product_variant' => $firstMovement->productVariant ? [
                        'id' => $firstMovement->productVariant->id,
                        'sku' => $firstMovement->productVariant->sku,
                        'name' => $firstMovement->productVariant->full_name ?? $firstMovement->productVariant->sku,
                        'product_name' => $firstMovement->productVariant->product?->name,
                        'current_stock' => $firstMovement->productVariant->stock_quantity,
                    ] : null,
                    'total_in' => $totalIn,
                    'total_out' => $totalOut,
                    'net_change' => $totalIn - $totalOut,
                    'movement_count' => $movementCount,
                    'last_date' => $lastDate,
                ];
            })->values();

            // Paginate grouped movements manually
            $page = (int) $request->input('page', 1);
            $total = $groupedMovements->count();
            $paginatedData = $groupedMovements->forPage($page, $perPage)->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'grouped_movements' => $paginatedData,
                    'summary' => [
                        'total_products' => $total,
                        'total_movements' => $allMovements->count(),
                        'total_in' => $allMovements->where('type', 'in')->sum('quantity'),
                        'total_out' => $allMovements->where('type', 'out')->sum('quantity'),
                    ],
                    'pagination' => [
                        'current_page' => $page,
                        'last_page' => (int) ceil($total / $perPage),
                        'per_page' => $perPage,
                        'total' => $total,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des mouvements groupés',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ==================== MÉTHODES PRIVÉES ====================

    /**
     * Générer une référence automatique
     */
    private function generateReference(string $movementType): string
    {
        $prefixes = [
            StockMovement::MOVEMENT_PURCHASE => 'ACH',
            StockMovement::MOVEMENT_SALE => 'VT',
            StockMovement::MOVEMENT_ADJUSTMENT => 'AJ',
            StockMovement::MOVEMENT_TRANSFER => 'TR',
            StockMovement::MOVEMENT_RETURN => 'RT',
        ];

        $prefix = $prefixes[$movementType] ?? 'MV';
        $year = now()->format('Y');
        $month = now()->format('m');

        $count = StockMovement::where('movement_type', $movementType)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

        $nextNumber = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}{$month}-{$nextNumber}";
    }

    /**
     * Formater un mouvement pour la réponse API
     */
    private function formatMovement(StockMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'type' => $movement->type,
            'type_label' => $movement->type === 'in' ? 'Entrée' : 'Sortie',
            'movement_type' => $movement->movement_type,
            'movement_type_label' => $this->getMovementTypeLabel($movement->movement_type),
            'quantity' => $movement->quantity,
            'reference' => $movement->reference,
            'reason' => $movement->reason,
            'unit_price' => $movement->unit_price,
            'total_price' => $movement->total_price,
            'date' => $movement->date?->format('Y-m-d'),
            'created_at' => $movement->created_at?->toIso8601String(),
            'product_variant' => $movement->productVariant ? [
                'id' => $movement->productVariant->id,
                'sku' => $movement->productVariant->sku,
                'name' => $movement->productVariant->full_name ?? $movement->productVariant->sku,
                'product_name' => $movement->productVariant->product?->name,
                'current_stock' => $movement->productVariant->stock_quantity,
            ] : null,
            'user' => $movement->user ? [
                'id' => $movement->user->id,
                'name' => $movement->user->name,
            ] : null,
            'store' => $movement->store ? [
                'id' => $movement->store->id,
                'name' => $movement->store->name,
            ] : null,
        ];
    }

    /**
     * Obtenir le libellé d'un type de mouvement
     */
    private function getMovementTypeLabel(string $type): string
    {
        return match ($type) {
            'purchase' => 'Achat',
            'sale' => 'Vente',
            'adjustment' => 'Ajustement',
            'transfer' => 'Transfert',
            'return' => 'Retour',
            default => $type,
        };
    }
}

<?php

namespace App\Http\Controllers\Pos;

use App\Exceptions\Pos\CartEmptyException;
use App\Exceptions\Pos\InsufficientPaymentException;
use App\Exceptions\Pos\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Services\Pos\CartService;
use App\Services\Pos\PaymentData;
use App\Services\Pos\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class PosCheckoutController extends Controller
{

    public function __construct(
        private PaymentService $paymentService,
        private CartService $cartService
    ) {}

    /**
     * Créer une vente depuis le POS
     * Utilise l'authentification web (session)
     */
    public function checkout(Request $request): JsonResponse
    {
        try {
            // Validation des données
            $validator = Validator::make($request->all(), [
                'items' => 'required|array|min:1',
                'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
                'items.*.product_id' => 'nullable|integer|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric|min:0',
                'client_id' => 'nullable|integer|exists:clients,id',
                'payment_method' => 'required|string|in:cash,mobile_money,card,bank_transfer',
                'paid_amount' => 'required|numeric|min:0',
                'discount' => 'nullable|numeric|min:0',
                'tax' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:500',
                'store_id' => 'nullable|integer|exists:stores,id',
            ]);

            // Validation personnalisée: chaque item doit avoir variant_id OU product_id
            $validator->after(function ($validator) use ($request) {
                $items = $request->input('items', []);
                foreach ($items as $index => $item) {
                    if (empty($item['variant_id']) && empty($item['product_id'])) {
                        $validator->errors()->add(
                            "items.{$index}",
                            'Chaque item doit avoir un variant_id ou un product_id'
                        );
                    }
                }
            });

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = Auth::user();
            $items = $request->input('items');
            $discount = (float) ($request->input('discount', 0));
            $tax = (float) ($request->input('tax', 0));

            // Déterminer le store_id (requête ou store actuel de l'utilisateur)
            $storeId = $request->input('store_id') ? (int) $request->input('store_id') : null;
            if (!$storeId) {
                $storeId = effective_store_id();
            }

            // Vérifier l'accès au store
            if (!$this->userHasAccessToStore($user, $storeId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas accès à ce magasin',
                ], 403);
            }

            // Formater les items pour le service de paiement
            $formattedItems = $this->formatItems($items);

            // Calculer le total
            $subtotal = collect($formattedItems)->sum(fn($item) => $item['price'] * $item['quantity']);
            $total = $subtotal - $discount + $tax;

            // Valider le stock
            $stockValidation = $this->validateStock($formattedItems);

            // Créer le PaymentData DTO
            $paymentData = new PaymentData(
                userId: $user->id,
                clientId: $request->input('client_id'),
                storeId: $storeId,
                paymentMethod: $request->input('payment_method'),
                items: $formattedItems,
                discount: $discount,
                tax: $tax,
                paidAmount: (float) $request->input('paid_amount'),
                total: $total,
                stockValidation: $stockValidation,
                notes: $request->input('notes') ?? null
            );

            // Traiter le paiement
            $result = $this->paymentService->process($paymentData);

            return response()->json([
                'success' => true,
                'message' => 'Vente créée avec succès',
                'data' => [
                    'sale' => $this->formatSale($result->sale),
                    'invoice' => $this->formatInvoice($result->invoice),
                    'change' => $result->change,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'tax' => $tax,
                    'total' => $total,
                    'paid_amount' => $paymentData->paidAmount,
                    'currency' => current_currency(),
                ],
            ], 201);
        } catch (CartEmptyException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Le panier est vide',
            ], 400);
        } catch (InsufficientPaymentException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Montant payé insuffisant',
                'required' => $e->getRequiredAmount(),
                'provided' => $e->getProvidedAmount(),
            ], 400);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'product' => $e->getProductName(),
                'requested' => $e->getRequestedQuantity(),
                'available' => $e->getAvailableQuantity(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la vente',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Valider un panier avant checkout (vérifier stock, calculer totaux)
     *
     * POST /api/mobile/checkout/validate
     */
    public function validateCart(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'items' => 'required|array|min:1',
                'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
                'items.*.product_id' => 'nullable|integer|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric|min:0',
                'discount' => 'nullable|numeric|min:0',
                'tax' => 'nullable|numeric|min:0',
            ]);

            // Validation personnalisée: chaque item doit avoir variant_id OU product_id
            $validator->after(function ($validator) use ($request) {
                $items = $request->input('items', []);
                foreach ($items as $index => $item) {
                    if (empty($item['variant_id']) && empty($item['product_id'])) {
                        $validator->errors()->add(
                            "items.{$index}",
                            'Chaque item doit avoir un variant_id ou un product_id'
                        );
                    }
                }
            });

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $items = $request->input('items');
            $discount = (float) ($request->input('discount', 0));
            $tax = (float) ($request->input('tax', 0));

            // Formater les items
            $formattedItems = $this->formatItems($items);

            // Calculer les totaux
            $subtotal = collect($formattedItems)->sum(fn($item) => $item['price'] * $item['quantity']);
            $total = $subtotal - $discount + $tax;

            // Valider le stock
            $stockValidation = $this->validateStock($formattedItems);

            // Vérifier les limites de remise
            $discountValidation = $this->validateDiscount($formattedItems, $discount);

            return response()->json([
                'success' => true,
                'data' => [
                    'is_valid' => $stockValidation['valid'] && $discountValidation['valid'],
                    'stock_validation' => $stockValidation,
                    'discount_validation' => $discountValidation,
                    'totals' => [
                        'subtotal' => $subtotal,
                        'discount' => $discount,
                        'tax' => $tax,
                        'total' => $total,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Formater les items pour le service de paiement
     */
    private function formatItems(array $items): array
    {
        return collect($items)->map(function ($item) {
            // Si variant_id est fourni, l'utiliser directement
            if (!empty($item['variant_id'])) {
                $variant = \App\Models\ProductVariant::with('product')->find($item['variant_id']);

                if (!$variant) {
                    throw new \Exception("Variante {$item['variant_id']} introuvable");
                }
            }
            // Sinon, utiliser product_id et récupérer la première variante
            elseif (!empty($item['product_id'])) {
                $product = \App\Models\Product::with('variants')->find($item['product_id']);

                if (!$product) {
                    throw new \Exception("Produit {$item['product_id']} introuvable");
                }

                // Récupérer la première variante disponible
                $variant = $product->variants()->first();

                if (!$variant) {
                    throw new \Exception("Le produit {$product->name} n'a pas de variante disponible");
                }
            } else {
                throw new \Exception("Item invalide: variant_id ou product_id requis");
            }

            return [
                'product_variant_id' => $variant->id,  // ✅ Clé attendue par SaleService
                'variant_id' => $variant->id,           // Pour compatibilité
                'product_id' => $variant->product_id,
                'product_name' => $variant->product->name,
                'quantity' => $item['quantity'],
                'price' => $item['price'] ?? $item['unit_price'] ?? $variant->product->price,
                'unit_price' => $item['unit_price'] ?? $item['price'] ?? $variant->product->price,  // ✅ Pour SaleService
                'original_price' => $variant->product->price,
                'max_discount_amount' => $variant->product->max_discount_amount,
            ];
        })->toArray();
    }

    /**
     * Valider le stock disponible
     */
    private function validateStock(array $items): array
    {
        foreach ($items as $item) {
            $variant = \App\Models\ProductVariant::find($item['variant_id']);

            if (!$variant || $variant->stock_quantity < $item['quantity']) {
                return [
                    'valid' => false,
                    'product_name' => $item['product_name'] ?? 'Produit',
                    'requested' => $item['quantity'],
                    'available' => $variant->stock_quantity ?? 0,
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Valider les limites de remise
     */
    private function validateDiscount(array $items, float $discount): array
    {
        if ($discount == 0) {
            return ['valid' => true];
        }

        $maxAllowedDiscount = 0;
        $hasLimitedProducts = false;

        foreach ($items as $item) {
            $maxDiscountPerUnit = $item['max_discount_amount'] ?? null;
            $quantity = $item['quantity'];
            $price = $item['original_price'] ?? $item['price'];

            if ($maxDiscountPerUnit !== null && $maxDiscountPerUnit > 0) {
                $hasLimitedProducts = true;
                $maxPerUnit = min((float) $maxDiscountPerUnit, (float) $price);
                $maxAllowedDiscount += $maxPerUnit * $quantity;
            } else {
                $maxAllowedDiscount += $price * $quantity;
            }
        }

        // Si au moins un produit a une limite, on vérifie la remise
        if ($hasLimitedProducts && $discount > $maxAllowedDiscount) {
            return [
                'valid' => false,
                'message' => "La remise ne peut pas dépasser " . number_format($maxAllowedDiscount, 0, ',', ' ') . " " . current_currency(),
                'max_allowed' => $maxAllowedDiscount,
                'requested' => $discount,
            ];
        }

        return ['valid' => true];
    }

    /**
     * Vérifier si l'utilisateur a accès au store
     */
    private function userHasAccessToStore($user, ?int $storeId): bool
    {
        if (!$storeId) {
            return true;
        }

        if ($user->isAdmin()) {
            // Admin peut accéder à tous les stores de son organisation
            return \App\Models\Store::where('id', $storeId)
                ->where('organization_id', $user->default_organization_id)
                ->exists();
        }

        // Utilisateur régulier peut accéder seulement à ses stores assignés
        return $user->stores()->where('stores.id', $storeId)->exists();
    }

    /**
     * Formater la vente pour la réponse
     */
    private function formatSale($sale): array
    {
        return [
            'id' => $sale->id,
            'reference' => $sale->reference,
            'total' => $sale->total,
            'discount' => $sale->discount,
            'tax' => $sale->tax,
            'payment_method' => $sale->payment_method,
            'payment_status' => $sale->payment_status,
            'status' => $sale->status,
            'sale_date' => $sale->sale_date->toIso8601String(),
            'items_count' => $sale->items->count(),
            'items' => $sale->items->map(fn($item) => [
                'id' => $item->id,
                'product_name' => $item->productVariant->product->name ?? 'Produit',
                'quantity' => $item->quantity,
                'price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ])->toArray(),
            'client' => $sale->client ? [
                'id' => $sale->client->id,
                'name' => $sale->client->name,
            ] : null,
            'cashier' => [
                'id' => $sale->user->id ?? null,
                'name' => $sale->user->name ?? 'N/A',
            ],
        ];
    }

    /**
     * Formater la facture pour la réponse
     */
    private function formatInvoice($invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date->toIso8601String(),
            'due_date' => $invoice->due_date?->toIso8601String(),
            'status' => $invoice->status,
        ];
    }

    /**
     * Historique des ventes
     *
     * GET /api/mobile/sales
     */
    public function salesHistory(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = (int) ($request->input('per_page', 20));
            $perPage = min(max($perPage, 10), 100);

            $query = \App\Models\Sale::with(['client', 'user', 'store', 'invoice', 'items.productVariant.product'])
                ->orderBy('sale_date', 'desc');

            // Filtrer par store si nécessaire
            $storeId = effective_store_id();
            if ($storeId && !user_can_access_all_stores()) {
                $query->where('store_id', $storeId);
            }

            // Filtrer par date si fournie
            if ($request->has('date_from')) {
                $query->whereDate('sale_date', '>=', $request->input('date_from'));
            }
            if ($request->has('date_to')) {
                $query->whereDate('sale_date', '<=', $request->input('date_to'));
            }

            // Filtrer par méthode de paiement
            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->input('payment_method'));
            }

            // Filtrer par statut
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            $sales = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'sales' => collect($sales->items())->map(fn($sale) => $this->formatSaleDetailed($sale)),
                    'pagination' => [
                        'current_page' => $sales->currentPage(),
                        'last_page' => $sales->lastPage(),
                        'per_page' => $sales->perPage(),
                        'total' => $sales->total(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des ventes',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Détail d'une vente
     *
     * GET /api/mobile/sales/{id}
     */
    public function saleDetail(int $id): JsonResponse
    {
        try {
            $sale = \App\Models\Sale::with([
                'client',
                'user',
                'store',
                'items.productVariant.product',
                'invoice'
            ])->findOrFail($id);

            // Vérifier l'accès
            $user = Auth::user();
            if (!user_can_access_all_stores() && $sale->store_id !== effective_store_id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatSaleDetailed($sale),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vente non trouvée',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la vente',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Formater une vente avec détails complets
     */
    private function formatSaleDetailed($sale): array
    {
        return [
            'id' => $sale->id,
            'reference' => $sale->reference,
            'sale_date' => $sale->sale_date->toIso8601String(),
            'total' => $sale->total,
            'discount' => $sale->discount ?? 0,
            'tax' => $sale->tax ?? 0,
            'payment_method' => $sale->payment_method,
            'payment_status' => $sale->payment_status,
            'status' => $sale->status,
            'notes' => $sale->notes,
            'client' => $sale->client ? [
                'id' => $sale->client->id,
                'name' => $sale->client->name,
                'phone' => $sale->client->phone,
            ] : null,
            'cashier' => [
                'id' => $sale->user->id,
                'name' => $sale->user->name,
            ],
            'store' => $sale->store ? [
                'id' => $sale->store->id,
                'name' => $sale->store->name,
                'code' => $sale->store->code,
            ] : null,
            'items' => $sale->items->map(fn($item) => [
                'id' => $item->id,
                'product_name' => $item->productVariant->product->name ?? 'Produit',
                'variant' => $item->productVariant ? [
                    'size' => $item->productVariant->size,
                    'color' => $item->productVariant->color,
                ] : null,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal,
            ])->toArray(),
            'items_count' => $sale->items->count(),
            'invoice' => $sale->invoice ? [
                'id' => $sale->invoice->id,
                'invoice_number' => $sale->invoice->invoice_number,
                'status' => $sale->invoice->status,
            ] : null,
        ];
    }
}

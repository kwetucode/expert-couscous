<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\StoreProductRequest;
use App\Models\Product;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ProductTypeRepository;
use App\Services\ProductService;
use App\Services\ReferenceGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Controller API Mobile - Gestion des Produits
 *
 * Permet de lister, créer, modifier et supprimer les produits via l'API mobile
 */
class MobileProductController extends Controller
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductService $productService,
        private CategoryRepository $categoryRepository,
        private ProductTypeRepository $productTypeRepository,
        private ReferenceGeneratorService $referenceGenerator,
        private \App\Services\ProductLabelService $labelService,
    ) {}

    /**
     * Liste des produits (paginée)
     *
     * GET /api/mobile/products
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 20);
            $perPage = min(max($perPage, 10), 100);

            $search = $request->input('search');
            $categoryId = $request->input('category_id');
            $status = $request->input('status');
            $stockLevel = $request->input('stock_level'); // Nouveau filtre: in_stock, low_stock, out_of_stock
            $sortField = $request->input('sort_by', 'name');
            $sortDirection = $request->input('sort_dir', 'asc');

            // Valider les champs de tri
            $allowedSortFields = ['name', 'reference', 'price', 'created_at', 'updated_at'];
            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'name';
            }
            $sortDirection = strtolower($sortDirection) === 'desc' ? 'desc' : 'asc';

            $products = $this->productRepository->paginateWithFilters(
                perPage: $perPage,
                search: $search,
                categoryId: $categoryId ? (int) $categoryId : null,
                status: $status,
                stockLevel: $stockLevel,
                sortField: $sortField,
                sortDirection: $sortDirection
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => collect($products->items())->map(fn($p) => $this->formatProduct($p)),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des produits',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Détail d'un produit
     *
     * GET /api/mobile/products/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $product = Product::with([
                'category',
                'productType.attributes',
                'variants.attributeValues.productAttribute',
                'variants.storeStocks.store',
            ])->find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produit non trouvé',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatProductDetailed($product),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du produit',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Créer un nouveau produit
     *
     * POST /api/mobile/products
     *
     * Champs obligatoires:
     * - name: Nom du produit
     * - category_id: Catégorie (utilisée pour générer la référence)
     * - product_type_id: Type de produit
     * - cost_price: Prix d'achat
     * - price: Prix de vente
     * - status: Statut (active/inactive)
     * - stock_alert_threshold: Seuil d'alerte stock
     *
     * Champs optionnels:
     * - description: Description du produit
     * - image: Image du produit
     * - initial_stock: Stock initial
     * - variants: Variantes du produit
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            // Générer automatiquement la référence basée sur la catégorie
            // Format: ABC-000001 (3 premières lettres de la catégorie + numéro séquentiel)
            $data['reference'] = $this->referenceGenerator->generateForProduct((int) $data['category_id']);

            // Générer automatiquement le code-barres (EAN-13 basé sur la référence)
            $data['barcode'] = $this->generateBarcode($data['reference']);

            // Générer automatiquement le QR code (contient la référence du produit)
            $data['qr_code'] = $this->generateQrCode($data['reference']);

            // Vérifier si le type de produit est un service
            $productType = $data['product_type_id']
                ? \App\Models\ProductType::find($data['product_type_id'])
                : null;
            $isServiceProduct = $productType?->is_service ?? false;

            // Appliquer les défauts pour les produits de type service
            if ($isServiceProduct) {
                // Les services n'ont pas de coût d'achat significatif, par défaut 0
                $data['cost_price'] = $data['cost_price'] ?? 0;
                // Stock illimité pour les services
                $data['initial_stock'] = 999999;
                // Pas de seuil d'alerte stock pour les services
                $data['stock_alert_threshold'] = 0;
            }

            // Gérer le stock initial
            $initialStock = $data['initial_stock'] ?? 0;
            unset($data['initial_stock']);

            // Si stock initial fourni et pas de variantes, créer une variante par défaut
            if ($initialStock > 0 && empty($data['variants'])) {
                $data['variants'] = [
                    [
                        'stock_quantity' => $initialStock,
                        'additional_price' => 0,
                    ],
                ];
            }

            $product = $this->productService->createProduct($data);

            return response()->json([
                'success' => true,
                'message' => 'Produit créé avec succès',
                'data' => $this->formatProductDetailed($product->fresh([
                    'category',
                    'variants',
                    'productType',
                ])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => mb_convert_encoding($e->getMessage(), 'UTF-8', 'UTF-8'),
            ], 400);
        }
    }

    /**
     * Modifier un produit
     *
     * PUT /api/mobile/products/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'reference' => 'sometimes|nullable|string|max:100|unique:products,reference,' . $id,
            'barcode' => 'sometimes|nullable|string|max:100|unique:products,barcode,' . $id,
            'category_id' => 'nullable|exists:categories,id',
            'product_type_id' => 'nullable|exists:product_types,id',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
            'stock_alert_threshold' => 'nullable|integer|min:0',
            'attributes' => 'nullable|array',
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
            $product = $this->productService->updateProduct($id, $data);

            return response()->json([
                'success' => true,
                'message' => 'Produit modifié avec succès',
                'data' => $this->formatProductDetailed($product->fresh([
                    'category',
                    'variants',
                    'productType',
                ])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Supprimer un produit
     *
     * DELETE /api/mobile/products/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
            ], 404);
        }

        try {
            $productName = $product->name;
            $this->productService->deleteProduct($id);

            return response()->json([
                'success' => true,
                'message' => "Produit \"{$productName}\" supprimé avec succès",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Archiver un produit (soft delete / désactiver)
     *
     * POST /api/mobile/products/{id}/archive
     */
    public function archive(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
            ], 404);
        }

        try {
            $this->productService->updateProduct($id, ['status' => Product::STATUS_INACTIVE]);

            return response()->json([
                'success' => true,
                'message' => "Produit \"{$product->name}\" archivé avec succès",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Restaurer un produit archivé
     *
     * POST /api/mobile/products/{id}/restore
     */
    public function restore(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
            ], 404);
        }

        try {
            $this->productService->updateProduct($id, ['status' => Product::STATUS_ACTIVE]);

            return response()->json([
                'success' => true,
                'message' => "Produit \"{$product->name}\" restauré avec succès",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Rechercher des produits (autocomplete)
     *
     * GET /api/mobile/products/search
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $search = $request->input('q', '');
            $limit = (int) $request->input('limit', 20);
            $limit = min(max($limit, 5), 50);

            if (strlen($search) < 2) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            $products = Product::with(['category', 'variants'])
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                })
                ->where('status', Product::STATUS_ACTIVE)
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $products->map(fn($p) => $this->formatProductSimple($p)),
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
     * Récupérer les catégories pour sélection
     *
     * GET /api/mobile/products/categories
     *
     * @queryParam product_type_id int Filtrer par type de produit (optionnel)
     */
    public function categories(Request $request): JsonResponse
    {
        try {
            $productTypeId = $request->input('product_type_id');

            if ($productTypeId) {
                // Filtrer les catégories par type de produit (comme ProductModal)
                $categories = $this->categoryRepository->getByProductType((int) $productTypeId);
            } else {
                // Retourner toutes les catégories si aucun type n'est spécifié
                $categories = $this->categoryRepository->all();
            }

            return response()->json([
                'success' => true,
                'data' => $categories->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'parent_id' => $c->parent_id,
                    'product_type_id' => $c->product_type_id,
                    'products_count' => $c->products_count ?? 0,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des catégories',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Récupérer les types de produits pour sélection
     *
     * GET /api/mobile/products/product-types
     *
     * @queryParam with_attributes bool Inclure les attributs du type (optionnel)
     */
    public function productTypes(Request $request): JsonResponse
    {
        try {
            $withAttributes = filter_var($request->input('with_attributes', false), FILTER_VALIDATE_BOOLEAN);

            $productTypes = $this->productTypeRepository->allActive();

            if ($withAttributes) {
                $productTypes->load('attributes');
            }

            return response()->json([
                'success' => true,
                'data' => $productTypes->map(function ($pt) use ($withAttributes) {
                    $data = [
                        'id' => $pt->id,
                        'name' => $pt->name,
                        'slug' => $pt->slug,
                        'description' => $pt->description,
                        'is_service' => (bool) $pt->is_service,
                        'has_variants' => $pt->has_variants,
                        'has_stock_management' => $pt->has_stock_management,
                        'icon' => $pt->icon,
                    ];

                    if ($withAttributes && $pt->relationLoaded('attributes')) {
                        $data['attributes'] = $pt->attributes->map(fn($attr) => [
                            'id' => $attr->id,
                            'name' => $attr->name,
                            'type' => $attr->type,
                            'is_variant' => $attr->is_variant,
                            'is_required' => $attr->is_required,
                            'options' => $attr->options,
                            'default_value' => $attr->default_value,
                        ]);
                    }

                    return $data;
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des types de produits',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Récupérer un type de produit avec ses détails et attributs
     *
     * GET /api/mobile/products/product-types/{id}
     */
    public function productTypeDetails(int $id): JsonResponse
    {
        try {
            $productType = $this->productTypeRepository->findById($id);

            if (!$productType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de produit non trouvé',
                ], 404);
            }

            // Récupérer les catégories associées à ce type
            $categories = $this->categoryRepository->getByProductType($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $productType->id,
                    'name' => $productType->name,
                    'slug' => $productType->slug,
                    'description' => $productType->description,
                    'is_service' => (bool) $productType->is_service,
                    'has_variants' => $productType->has_variants,
                    'has_stock_management' => $productType->has_stock_management,
                    'icon' => $productType->icon,
                    'attributes' => $productType->attributes->map(fn($attr) => [
                        'id' => $attr->id,
                        'name' => $attr->name,
                        'type' => $attr->type,
                        'is_variant' => $attr->is_variant,
                        'is_required' => $attr->is_required,
                        'options' => $attr->options,
                        'default_value' => $attr->default_value,
                        'sort_order' => $attr->sort_order,
                    ]),
                    'categories' => $categories->map(fn($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'slug' => $c->slug,
                    ]),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du type de produit',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Données nécessaires pour créer un produit (form data)
     *
     * GET /api/mobile/products/create-form-data
     *
     * Retourne les types de produits, et optionnellement les catégories filtrées
     */
    public function createFormData(Request $request): JsonResponse
    {
        try {
            $productTypeId = $request->input('product_type_id');
            $organization = current_organization();
            $isServiceOrg = is_service_organization($organization);

            // 1. Récupérer les types de produits filtrés par activité de l'organisation
            $productTypes = $this->productTypeRepository->allActive();
            $productTypes->load('attributes');

            // 2. Récupérer les catégories (filtrées si product_type_id fourni)
            if ($productTypeId) {
                $categories = $this->categoryRepository->getByProductType((int) $productTypeId);
            } else {
                $categories = $this->categoryRepository->all();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'product_types' => $productTypes->map(function ($pt) {
                        return [
                            'id' => $pt->id,
                            'name' => $pt->name,
                            'slug' => $pt->slug,
                            'description' => $pt->description,
                            'is_service' => (bool) $pt->is_service,
                            'has_variants' => $pt->has_variants,
                            'has_stock_management' => $pt->has_stock_management,
                            'icon' => $pt->icon,
                            'attributes' => $pt->attributes->map(fn($attr) => [
                                'id' => $attr->id,
                                'name' => $attr->name,
                                'type' => $attr->type,
                                'is_variant' => $attr->is_variant,
                                'is_required' => $attr->is_required,
                                'options' => $attr->options,
                                'default_value' => $attr->default_value,
                            ]),
                        ];
                    }),
                    'categories' => $categories->map(fn($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'slug' => $c->slug,
                        'product_type_id' => $c->product_type_id,
                    ]),
                    'selected_product_type_id' => $productTypeId ? (int) $productTypeId : null,
                    'business_activity' => [
                        'type' => $organization?->business_activity?->value ?? 'retail',
                        'is_service_organization' => $isServiceOrg,
                        'has_stock_management' => has_stock_management($organization),
                        'defaults' => [
                            'cost_price' => $isServiceOrg ? 0 : null,
                            'stock_quantity' => $isServiceOrg ? 999999 : 0,
                        ],
                        'hidden_fields' => $isServiceOrg ? ['weight', 'dimensions', 'expiry_date', 'variants'] : [],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données du formulaire',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Générer une référence produit
     *
     * GET /api/mobile/products/generate-reference
     */
    public function generateReference(Request $request): JsonResponse
    {
        try {
            $categoryId = $request->input('category_id');
            $reference = $this->referenceGenerator->generateForProduct($categoryId ? (int) $categoryId : null);

            return response()->json([
                'success' => true,
                'data' => [
                    'reference' => $reference,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de la référence',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ==================== MÉTHODES PRIVÉES ====================

    /**
     * Générer un code-barres unique basé sur la référence
     * Format: 13 chiffres (compatible EAN-13)
     */
    private function generateBarcode(string $reference): string
    {
        // Extraire les chiffres de la référence
        $numericPart = preg_replace('/[^0-9]/', '', $reference);

        // Créer un code unique avec timestamp pour garantir l'unicité
        $timestamp = substr((string) time(), -6);
        $baseCode = str_pad($numericPart, 6, '0', STR_PAD_LEFT) . $timestamp;

        // S'assurer qu'on a 12 chiffres (sans le checksum)
        $baseCode = substr(str_pad($baseCode, 12, '0', STR_PAD_LEFT), 0, 12);

        // Calculer le checksum EAN-13
        $checksum = $this->calculateEan13Checksum($baseCode);

        return $baseCode . $checksum;
    }

    /**
     * Calculer le checksum EAN-13
     */
    private function calculateEan13Checksum(string $code): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $code[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }
        $remainder = $sum % 10;

        return $remainder === 0 ? 0 : 10 - $remainder;
    }

    /**
     * Générer un QR code basé sur la référence
     * Retourne une chaîne encodée pour le QR code
     */
    private function generateQrCode(string $reference): string
    {
        // Format: PROD-{reference}-{timestamp}
        // Ce format peut être scanné pour identifier rapidement le produit
        return 'PROD-' . $reference . '-' . substr((string) time(), -8);
    }

    /**
     * Formater un produit pour la liste
     */
    private function formatProduct(Product $product): array
    {
        $isService = $product->productType?->is_service ?? false;
        $totalStock = $isService ? null : $product->variants->sum('stock_quantity');
        $isLowStock = !$isService && $totalStock <= ($product->stock_alert_threshold ?? 10) && $totalStock > 0;
        $isOutOfStock = !$isService && $totalStock <= 0;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'reference' => $product->reference,
            'barcode' => $product->barcode,
            'slug' => $product->slug,
            'price' => $product->price,
            'cost_price' => $product->cost_price,
            'status' => $product->status,
            'image' => $product->image,
            'is_service' => (bool) $isService,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
            ] : null,
            'stock' => $isService ? [
                'total' => null,
                'is_low_stock' => false,
                'is_out_of_stock' => false,
                'alert_threshold' => null,
                'unlimited' => true,
            ] : [
                'total' => $totalStock,
                'is_low_stock' => $isLowStock,
                'is_out_of_stock' => $isOutOfStock,
                'alert_threshold' => $product->stock_alert_threshold,
                'unlimited' => false,
            ],
            'variants_count' => $product->variants->count(),
            'created_at' => $product->created_at?->toIso8601String(),
        ];
    }

    /**
     * Formater un produit pour la recherche simple
     */
    private function formatProductSimple(Product $product): array
    {
        $isService = $product->productType?->is_service ?? false;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'reference' => $product->reference,
            'barcode' => $product->barcode,
            'price' => $product->price,
            'stock' => $isService ? null : $product->variants->sum('stock_quantity'),
            'is_service' => (bool) $isService,
            'category' => $product->category?->name,
            'image' => $product->image,
        ];
    }

    /**
     * Formater un produit avec tous les détails
     */
    private function formatProductDetailed(Product $product): array
    {
        $data = $this->formatProduct($product);

        $data['description'] = $product->description;
        $data['qr_code'] = $product->qr_code;
        $data['product_type'] = $product->productType ? [
            'id' => $product->productType->id,
            'name' => $product->productType->name,
            'slug' => $product->productType->slug,
            'is_service' => (bool) $product->productType->is_service,
            'has_variants' => $product->productType->has_variants,
            'has_stock_management' => $product->productType->has_stock_management,
        ] : null;

        $data['variants'] = $product->variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->full_name ?? $variant->sku,
                'price' => $variant->price,
                'stock_quantity' => $variant->stock_quantity,
                'low_stock_threshold' => $variant->low_stock_threshold,
                'is_low_stock' => $variant->isLowStock(),
                'is_out_of_stock' => $variant->isOutOfStock(),
                'attributes' => $variant->attributeValues?->map(fn($av) => [
                    'attribute_id' => $av->product_attribute_id,
                    'attribute_name' => $av->productAttribute?->name,
                    'value' => $av->value,
                ]) ?? [],
                'store_stocks' => $variant->storeStocks?->map(fn($ss) => [
                    'store_id' => $ss->store_id,
                    'store_name' => $ss->store?->name,
                    'quantity' => $ss->quantity,
                ]) ?? [],
            ];
        });

        $data['updated_at'] = $product->updated_at?->toIso8601String();

        return $data;
    }

    /**
     * Générer des étiquettes PDF pour un produit
     *
     * GET /api/mobile/products/{id}/labels
     */
    public function generateLabels(Request $request, int $id)
    {
        try {
            $product = Product::with(['category', 'variants'])->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'format' => 'nullable|in:small,medium,large',
                'columns' => 'nullable|integer|min:1|max:4',
                'show_price' => 'nullable|boolean',
                'show_qr_code' => 'nullable|boolean',
                'show_barcode' => 'nullable|boolean',
                'include_variants' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $options = [
                'format' => $request->input('format', 'small'),
                'columns' => $request->input('columns', 3),
                'show_price' => $request->boolean('show_price', true),
                'show_qr_code' => $request->boolean('show_qr_code', true),
                'show_barcode' => $request->boolean('show_barcode', true),
                'include_variants' => $request->boolean('include_variants', false),
            ];

            $pdf = $this->labelService->generateProductLabelsPDF($product, $options);

            $filename = 'etiquettes_' . $product->reference . '_' . date('YmdHis') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération des étiquettes',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Générer des étiquettes PDF pour plusieurs produits
     *
     * POST /api/mobile/products/labels/bulk
     */
    public function generateBulkLabels(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_ids' => 'required|array|min:1',
                'product_ids.*' => 'required|integer|exists:products,id',
                'format' => 'nullable|in:small,medium,large',
                'columns' => 'nullable|integer|min:1|max:4',
                'show_price' => 'nullable|boolean',
                'show_qr_code' => 'nullable|boolean',
                'show_barcode' => 'nullable|boolean',
                'include_variants' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $options = [
                'format' => $request->input('format', 'small'),
                'columns' => $request->input('columns', 3),
                'show_price' => $request->boolean('show_price', true),
                'show_qr_code' => $request->boolean('show_qr_code', true),
                'show_barcode' => $request->boolean('show_barcode', true),
                'include_variants' => $request->boolean('include_variants', false),
            ];

            $pdf = $this->labelService->generateBulkLabelsPDF(
                $request->input('product_ids'),
                $options
            );

            $filename = 'etiquettes_produits_' . date('YmdHis') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération des étiquettes',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

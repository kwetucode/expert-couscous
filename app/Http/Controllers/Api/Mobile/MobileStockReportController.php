<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller API Mobile - Rapports de Stock
 *
 * Fournit les endpoints pour les alertes et statistiques de stock
 */
class MobileStockReportController extends Controller
{
    public function __construct(
        private MobileReportService $reportService
    ) {}

    /**
     * Toutes les alertes de stock
     *
     * GET /api/mobile/stock/alerts
     */
    public function alerts(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $data = $this->reportService->getStockAlerts($user);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des alertes',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Résumé du stock (cohérent avec Livewire StockOverview)
     *
     * GET /api/mobile/stock/summary
     * 
     * Retourne les mêmes KPIs que la vue Livewire État du Stock :
     * - total_products : nombre total de variantes
     * - in_stock_count : variantes avec stock > 0
     * - out_of_stock_count : variantes en rupture (stock <= 0)
     * - low_stock_count : variantes en stock faible
     * - total_stock_value : valeur totale du stock (coût)
     * - total_retail_value : valeur de vente potentielle
     * - total_units : nombre total d'unités en stock
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Utiliser StockOverviewService pour cohérence avec Livewire
            $stockOverviewService = app(\App\Services\StockOverviewService::class);
            $kpis = $stockOverviewService->calculateKPIs();

            return response()->json([
                'success' => true,
                'data' => [
                    'kpis' => [
                        'total_products' => $kpis['total_products'],
                        'in_stock_count' => $kpis['in_stock_count'],
                        'out_of_stock_count' => $kpis['out_of_stock_count'],
                        'low_stock_count' => $kpis['low_stock_count'],
                        'total_units' => $kpis['total_units'],
                    ],
                    'value' => [
                        'total_stock_value' => $kpis['total_stock_value'],
                        'total_stock_value_formatted' => number_format($kpis['total_stock_value'], 2, ',', ' '),
                        'total_retail_value' => $kpis['total_retail_value'],
                        'total_retail_value_formatted' => number_format($kpis['total_retail_value'], 2, ',', ' '),
                        'potential_profit' => $kpis['potential_profit'],
                        'potential_profit_formatted' => number_format($kpis['potential_profit'], 2, ',', ' '),
                        'profit_margin_percentage' => $kpis['profit_margin_percentage'],
                    ],
                    'alerts' => [
                        'total' => $kpis['out_of_stock_count'] + $kpis['low_stock_count'],
                        'out_of_stock' => $kpis['out_of_stock_count'],
                        'low_stock' => $kpis['low_stock_count'],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du résumé du stock',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Produits en stock bas
     *
     * GET /api/mobile/stock/low-stock
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $limit = $request->input('limit') ? (int) $request->input('limit') : null;

            // Limiter la valeur si fournie
            if ($limit) {
                $limit = min(max($limit, 10), 100);
            }

            $data = $this->reportService->getLowStockProducts($user, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $data,
                    'count' => count($data),
                    'status' => 'low_stock',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des produits en stock bas',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Produits en rupture de stock
     *
     * GET /api/mobile/stock/out-of-stock
     */
    public function outOfStock(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $limit = $request->input('limit') ? (int) $request->input('limit') : null;

            // Limiter la valeur si fournie
            if ($limit) {
                $limit = min(max($limit, 10), 100);
            }

            $data = $this->reportService->getOutOfStockProducts($user, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $data,
                    'count' => count($data),
                    'status' => 'out_of_stock',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des produits en rupture',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Valeur totale du stock (admin/manager uniquement)
     *
     * GET /api/mobile/stock/value
     */
    public function stockValue(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Seuls admin/manager peuvent voir la valeur du stock
            if (!user_can_access_all_stores() && !$user->hasPermission('reports.stock')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé',
                ], 403);
            }

            $summary = $this->reportService->getStockSummary($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'value' => $summary['value'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la valeur du stock',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Stock par magasin (admin/manager uniquement)
     *
     * GET /api/mobile/stock/by-store
     */
    public function byStore(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!user_can_access_all_stores()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé - Réservé aux administrateurs et managers',
                ], 403);
            }

            $data = $this->reportService->getStockByStore($user);

            // Calculer les totaux
            $totalAlerts = collect($data)->sum('total_alerts');
            $totalValue = collect($data)->sum('stock_value');

            return response()->json([
                'success' => true,
                'data' => [
                    'stores' => $data,
                    'totals' => [
                        'alerts' => $totalAlerts,
                        'value' => $totalValue,
                        'value_formatted' => number_format($totalValue, 2, ',', ' '),
                    ],
                    'chart' => [
                        'type' => 'bar',
                        'labels' => collect($data)->pluck('name')->toArray(),
                        'datasets' => [
                            [
                                'label' => 'Stock bas',
                                'data' => collect($data)->pluck('low_stock_count')->toArray(),
                                'color' => '#F59E0B',
                            ],
                            [
                                'label' => 'Rupture',
                                'data' => collect($data)->pluck('out_of_stock_count')->toArray(),
                                'color' => '#EF4444',
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du stock par magasin',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Statistiques combinées des alertes (pour widget mobile)
     *
     * GET /api/mobile/stock/widget
     */
    public function widget(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $summary = $this->reportService->getStockSummary($user);
            $alerts = $this->reportService->getStockAlerts($user);

            // Récupérer les 5 alertes les plus critiques
            $criticalAlerts = array_merge(
                array_slice($alerts['out_of_stock']['variants']->toArray(), 0, 3),
                array_slice($alerts['low_stock']['variants']->toArray(), 0, 2)
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_alerts' => $summary['alerts']['total'],
                        'critical' => $summary['alerts']['out_of_stock'],
                        'warning' => $summary['alerts']['low_stock'],
                    ],
                    'critical_alerts' => $criticalAlerts,
                    'has_alerts' => $summary['alerts']['total'] > 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du widget',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Vue d'ensemble du stock (cohérent avec Livewire StockOverview)
     *
     * GET /api/mobile/stock/overview
     * 
     * Retourne les KPIs + liste des variantes avec filtres et pagination
     */
    public function overview(Request $request): JsonResponse
    {
        try {
            $stockOverviewService = app(\App\Services\StockOverviewService::class);

            // Récupérer les KPIs
            $kpis = $stockOverviewService->calculateKPIs();

            // Préparer les filtres
            $filters = [
                'search' => $request->input('search'),
                'category_id' => $request->input('category_id'),
                'stock_level' => $request->input('stock_level'), // in_stock, low_stock, out_of_stock
                'sort_field' => $request->input('sort_by', 'stock_quantity'),
                'sort_direction' => $request->input('sort_dir', 'asc'),
            ];

            // Récupérer les variantes avec filtres
            $variants = $stockOverviewService->getInventoryVariants($filters);

            // Pagination manuelle
            $perPage = min(max((int) $request->input('per_page', 20), 10), 100);
            $page = max((int) $request->input('page', 1), 1);
            $total = $variants->count();
            $paginatedVariants = $variants->slice(($page - 1) * $perPage, $perPage)->values();

            // Récupérer les catégories pour les filtres
            $categories = $stockOverviewService->getCategories();

            return response()->json([
                'success' => true,
                'data' => [
                    'kpis' => [
                        'total_stock_value' => $kpis['total_stock_value'],
                        'total_stock_value_formatted' => number_format($kpis['total_stock_value'], 2, ',', ' '),
                        'total_retail_value' => $kpis['total_retail_value'],
                        'total_retail_value_formatted' => number_format($kpis['total_retail_value'], 2, ',', ' '),
                        'potential_profit' => $kpis['potential_profit'],
                        'potential_profit_formatted' => number_format($kpis['potential_profit'], 2, ',', ' '),
                        'profit_margin_percentage' => $kpis['profit_margin_percentage'],
                        'total_products' => $kpis['total_products'],
                        'in_stock_count' => $kpis['in_stock_count'],
                        'out_of_stock_count' => $kpis['out_of_stock_count'],
                        'low_stock_count' => $kpis['low_stock_count'],
                        'total_units' => $kpis['total_units'],
                    ],
                    'variants' => $paginatedVariants->map(fn($variant) => $this->formatVariant($variant)),
                    'categories' => $categories->map(fn($cat) => [
                        'id' => $cat->id,
                        'name' => $cat->name,
                    ]),
                    'pagination' => [
                        'current_page' => $page,
                        'last_page' => (int) ceil($total / $perPage),
                        'per_page' => $perPage,
                        'total' => $total,
                    ],
                    'filters' => $filters,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'aperçu du stock',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Tableau de bord du stock (cohérent avec Livewire StockDashboard)
     *
     * GET /api/mobile/stock/dashboard
     * 
     * Retourne les statistiques de mouvements, produits en alerte et mouvements récents
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $variantRepository = app(\App\Repositories\ProductVariantRepository::class);
            $movementRepository = app(\App\Repositories\StockMovementRepository::class);

            // Dates par défaut : ce mois
            $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
            $dateTo = $request->input('date_to', now()->format('Y-m-d'));

            // Statistiques des mouvements
            $stats = $movementRepository->statistics($dateFrom, $dateTo);

            // Produits en stock bas (limit 5)
            $lowStockQuery = $variantRepository->query()
                ->with('product')
                ->where('stock_quantity', '>', 0)
                ->whereRaw('stock_quantity <= low_stock_threshold');

            if (current_store_id()) {
                $lowStockQuery->whereHas('product', function($q) {
                    $q->where('store_id', current_store_id());
                });
            }

            $lowStockProducts = $lowStockQuery->orderBy('stock_quantity', 'asc')
                ->limit(5)
                ->get();

            // Produits en rupture (limit 5)
            $outOfStockQuery = $variantRepository->query()
                ->with('product')
                ->where('stock_quantity', '<=', 0);

            if (current_store_id()) {
                $outOfStockQuery->whereHas('product', function($q) {
                    $q->where('store_id', current_store_id());
                });
            }

            $outOfStockProducts = $outOfStockQuery->limit(5)->get();

            // Mouvements récents (limit 10)
            $recentMovementsQuery = $movementRepository->query()
                ->with(['productVariant.product', 'user'])
                ->whereBetween('date', [$dateFrom, $dateTo]);

            if (current_store_id()) {
                $recentMovementsQuery->where('store_id', current_store_id());
            }

            $recentMovements = $recentMovementsQuery->orderBy('date', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                    ],
                    'stats' => [
                        'total_in' => $stats['total_in'],
                        'total_out' => $stats['total_out'],
                        'net_movement' => $stats['net_movement'],
                        'total_value_in' => $stats['total_value_in'],
                        'total_value_in_formatted' => number_format($stats['total_value_in'], 2, ',', ' '),
                        'total_value_out' => $stats['total_value_out'],
                        'total_value_out_formatted' => number_format($stats['total_value_out'], 2, ',', ' '),
                        'total_movements' => $stats['total_movements'],
                    ],
                    'low_stock_products' => $lowStockProducts->map(fn($v) => [
                        'id' => $v->id,
                        'product_id' => $v->product_id,
                        'product_name' => $v->product->name ?? 'N/A',
                        'variant_name' => $v->full_name ?? $v->sku,
                        'sku' => $v->sku,
                        'stock_quantity' => $v->stock_quantity,
                        'low_stock_threshold' => $v->low_stock_threshold,
                        'status' => 'low_stock',
                    ]),
                    'out_of_stock_products' => $outOfStockProducts->map(fn($v) => [
                        'id' => $v->id,
                        'product_id' => $v->product_id,
                        'product_name' => $v->product->name ?? 'N/A',
                        'variant_name' => $v->full_name ?? $v->sku,
                        'sku' => $v->sku,
                        'stock_quantity' => $v->stock_quantity,
                        'status' => 'out_of_stock',
                    ]),
                    'recent_movements' => $recentMovements->map(fn($m) => [
                        'id' => $m->id,
                        'type' => $m->type,
                        'type_label' => $m->type === 'in' ? 'Entrée' : 'Sortie',
                        'movement_type' => $m->movement_type,
                        'quantity' => $m->quantity,
                        'reference' => $m->reference,
                        'date' => $m->date?->format('Y-m-d'),
                        'product_variant' => $m->productVariant ? [
                            'id' => $m->productVariant->id,
                            'sku' => $m->productVariant->sku,
                            'product_name' => $m->productVariant->product?->name,
                        ] : null,
                        'user' => $m->user ? [
                            'id' => $m->user->id,
                            'name' => $m->user->name,
                        ] : null,
                    ]),
                    'alerts_summary' => [
                        'low_stock_count' => $lowStockProducts->count(),
                        'out_of_stock_count' => $outOfStockProducts->count(),
                        'total_alerts' => $lowStockProducts->count() + $outOfStockProducts->count(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du tableau de bord stock',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Liste paginée des alertes de stock (cohérent avec Livewire StockAlerts)
     *
     * GET /api/mobile/stock/alerts/list
     * 
     * Retourne la liste paginée des variantes en alerte avec filtres
     */
    public function alertsList(Request $request): JsonResponse
    {
        try {
            $variantRepository = app(\App\Repositories\ProductVariantRepository::class);

            $alertType = $request->input('alert_type', 'all'); // all, out_of_stock, low_stock
            $search = $request->input('search');
            $perPage = min(max((int) $request->input('per_page', 20), 10), 100);

            $query = $variantRepository->query()
                ->with('product');

            // Filtre par store actuel
            if (current_store_id()) {
                $query->whereHas('product', function($q) {
                    $q->where('store_id', current_store_id());
                });
            }

            // Filtre par type d'alerte
            if ($alertType === 'out_of_stock') {
                $query->where('stock_quantity', '<=', 0);
            } elseif ($alertType === 'low_stock') {
                $query->where('stock_quantity', '>', 0)
                      ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
            } else {
                // all - rupture OU stock bas
                $query->where(function($q) {
                    $q->where('stock_quantity', '<=', 0)
                      ->orWhereColumn('stock_quantity', '<=', 'low_stock_threshold');
                });
            }

            // Recherche
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->whereHas('product', function($pq) use ($search) {
                        $pq->where('name', 'like', '%' . $search . '%')
                           ->orWhere('sku', 'like', '%' . $search . '%');
                    })->orWhere('sku', 'like', '%' . $search . '%');
                });
            }

            // Compter par type d'alerte
            $outOfStockCount = (clone $query)->where('stock_quantity', '<=', 0)->count();
            
            // Pour low_stock, il faut reconstruire la condition
            $lowStockCountQuery = $variantRepository->query()
                ->with('product')
                ->where('stock_quantity', '>', 0)
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
            
            if (current_store_id()) {
                $lowStockCountQuery->whereHas('product', function($q) {
                    $q->where('store_id', current_store_id());
                });
            }
            $lowStockCount = $lowStockCountQuery->count();

            // Pagination
            $variants = $query->orderBy('stock_quantity', 'asc')
                              ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'variants' => $variants->map(fn($v) => [
                        'id' => $v->id,
                        'product_id' => $v->product_id,
                        'product_name' => $v->product->name ?? 'N/A',
                        'variant_name' => $v->full_name ?? $v->sku,
                        'sku' => $v->sku,
                        'stock_quantity' => $v->stock_quantity,
                        'low_stock_threshold' => $v->low_stock_threshold,
                        'status' => $v->stock_quantity <= 0 ? 'out_of_stock' : 'low_stock',
                        'status_label' => $v->stock_quantity <= 0 ? 'Rupture' : 'Stock bas',
                        'product' => [
                            'id' => $v->product->id,
                            'name' => $v->product->name,
                            'reference' => $v->product->reference,
                            'category' => $v->product->category?->name,
                        ],
                    ]),
                    'summary' => [
                        'out_of_stock_count' => $outOfStockCount,
                        'low_stock_count' => $lowStockCount,
                        'total_alerts' => $outOfStockCount + $lowStockCount,
                    ],
                    'filters' => [
                        'alert_type' => $alertType,
                        'search' => $search,
                    ],
                    'pagination' => [
                        'current_page' => $variants->currentPage(),
                        'last_page' => $variants->lastPage(),
                        'per_page' => $variants->perPage(),
                        'total' => $variants->total(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des alertes',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Formater une variante pour la réponse API
     */
    private function formatVariant($variant): array
    {
        $currentStoreId = current_store_id();
        $storeQty = $currentStoreId !== null ? $variant->getStoreStock($currentStoreId) : $variant->stock_quantity;

        // Déterminer le statut
        $status = 'in_stock';
        if ($storeQty <= 0) {
            $status = 'out_of_stock';
        } elseif ($storeQty <= $variant->low_stock_threshold) {
            $status = 'low_stock';
        }

        return [
            'id' => $variant->id,
            'product_id' => $variant->product_id,
            'sku' => $variant->sku,
            'barcode' => $variant->barcode,
            'product_name' => $variant->product->name ?? 'N/A',
            'variant_name' => $variant->full_name ?? $variant->sku,
            'category' => $variant->product->category?->name,
            'stock_quantity' => $storeQty,
            'low_stock_threshold' => $variant->low_stock_threshold,
            'status' => $status,
            'status_label' => match($status) {
                'out_of_stock' => 'Rupture',
                'low_stock' => 'Stock bas',
                default => 'En stock',
            },
            'cost_price' => $variant->product->cost_price ?? 0,
            'price' => $variant->product->price ?? 0,
            'stock_value' => $storeQty * ($variant->product->cost_price ?? 0),
            'retail_value' => $storeQty * ($variant->product->price ?? 0),
        ];
    }
}

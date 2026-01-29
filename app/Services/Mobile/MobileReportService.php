<?php

declare(strict_types=1);

namespace App\Services\Mobile;

use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Repositories\DashboardRepository;
use App\Services\DashboardService;
use App\Services\Pos\ReportService;
use App\Services\StockAlertService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service pour les rapports mobile
 * Orchestre les repositories et services existants pour l'API mobile
 */
class MobileReportService
{
    public function __construct(
        private DashboardRepository $dashboardRepository,
        private DashboardService $dashboardService,
        private StockAlertService $stockAlertService,
        private ReportService $posReportService,
    ) {}

    /**
     * Détermine le scope de données selon le rôle de l'utilisateur
     */
    public function getDataScope(User $user): array
    {
        if (user_can_access_all_stores()) {
            return [
                'type' => 'organization',
                'organization_id' => $user->default_organization_id,
                'stores' => $user->defaultOrganization?->stores->pluck('id')->toArray() ?? [],
            ];
        }

        return [
            'type' => 'store',
            'store_id' => effective_store_id(),
        ];
    }

    /**
     * Récupère le contexte utilisateur (info connexion)
     */
    public function getUserContext(User $user): array
    {
        // Use effective_store_id() which takes into account request store_id parameter
        $effectiveStoreId = effective_store_id();
        $currentStore = $effectiveStoreId ? \App\Models\Store::find($effectiveStoreId) : null;
        $organization = $user->defaultOrganization;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => user_role_in_current_store(),
            'organization' => $organization ? [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ] : null,
            'current_store' => $currentStore ? [
                'id' => $currentStore->id,
                'name' => $currentStore->name,
                'code' => $currentStore->code,
            ] : null,
            'can_access_all_stores' => user_can_access_all_stores(),
            'is_cashier_or_staff' => user_is_cashier_or_staff(),
            'accessible_stores' => $this->getAccessibleStores($user),
        ];
    }

    /**
     * Récupère les stores accessibles par l'utilisateur
     */
    public function getAccessibleStores(User $user): array
    {
        if (user_can_access_all_stores()) {
            $stores = $user->defaultOrganization?->stores ?? collect();
        } else {
            $stores = $user->stores ?? collect();
        }

        return $stores->map(fn($store) => [
            'id' => $store->id,
            'name' => $store->name,
            'code' => $store->code,
            'is_current' => $store->id === effective_store_id(),
        ])->toArray();
    }

    /**
     * Dashboard principal - données complètes
     */
    public function getDashboardData(User $user): array
    {
        $cacheKey = $this->getCacheKey('dashboard', $user);

        return Cache::remember($cacheKey, 300, function () use ($user) {
            // Utilise DashboardService existant
            $dashboardStats = $this->dashboardService->getDashboardStats();

            // Utilise StockAlertService existant
            $stockAlerts = $this->stockAlertService->getAlertsSummary();

            return [
                'user' => $this->getUserContext($user),
                'kpis' => [
                    'total_products' => $dashboardStats['kpis']['total_products'],
                    'total_clients' => $dashboardStats['kpis']['total_clients'],
                    'total_suppliers' => $dashboardStats['kpis']['total_suppliers'],
                ],
                'sales' => [
                    'today' => $dashboardStats['sales']['today_sales'],
                    'month' => $dashboardStats['sales']['month_sales'],
                    'total_count' => $dashboardStats['sales']['total_sales'],
                    'growth_percent' => round($dashboardStats['sales']['sales_growth'], 2),
                ],
                'stock_alerts' => [
                    'total' => $stockAlerts['total_alerts'],
                    'low_stock' => $stockAlerts['low_stock']['count'],
                    'out_of_stock' => $stockAlerts['out_of_stock']['count'],
                ],
                'chart' => $this->formatChartData($dashboardStats['sales']['chart_data']),
                'stores_performance' => $this->getStoresPerformance($user),
                'top_products' => $this->formatTopProducts($dashboardStats['recent_data']['top_products']),
                'recent_sales' => $this->formatRecentSales($dashboardStats['recent_data']['recent_sales']),
            ];
        });
    }

    /**
     * Résumé des ventes
     */
    public function getSalesSummary(User $user): array
    {
        $cacheKey = $this->getCacheKey('sales_summary', $user);

        return Cache::remember($cacheKey, 300, function () {
            $salesStats = $this->dashboardService->getSalesStats();

            $todaySalesCount = $this->dashboardRepository->getTotalSalesCount();
            $avgTicket = $todaySalesCount > 0 ? $salesStats['today_sales'] / $todaySalesCount : 0;

            return [
                'today' => [
                    'total' => $salesStats['today_sales'],
                    'count' => $todaySalesCount,
                    'average_ticket' => round($avgTicket, 2),
                ],
                'month' => [
                    'total' => $salesStats['month_sales'],
                    'growth_percent' => round($salesStats['sales_growth'], 2),
                ],
                'year' => [
                    'total' => $this->getYearSales(),
                ],
            ];
        });
    }

    /**
     * Rapport de ventes par période
     */
    public function getSalesReport(User $user, string $period): array
    {
        $cacheKey = $this->getCacheKey("sales_{$period}", $user);

        return Cache::remember($cacheKey, 600, function () use ($period) {
            return match ($period) {
                'daily' => $this->posReportService->getDailyStats(),
                'weekly' => $this->posReportService->getWeeklyStats(),
                'monthly' => $this->posReportService->getMonthlyStats(now()->year, now()->month),
                default => $this->posReportService->getDailyStats(),
            };
        });
    }

    /**
     * Données pour graphique de ventes
     */
    public function getSalesChart(User $user, string $period = 'week', ?int $days = null): array
    {
        $cacheKey = $this->getCacheKey("chart_{$period}", $user);

        return Cache::remember($cacheKey, 600, function () use ($period, $days) {
            $chartDays = $days ?? match ($period) {
                'week' => 7,
                'month' => 30,
                'quarter' => 90,
                'year' => 365,
                default => 7,
            };

            $chartData = $this->dashboardService->getSalesChartData($chartDays);

            return $this->formatChartData($chartData, $period);
        });
    }

    /**
     * Top produits vendus
     */
    public function getTopProducts(User $user, int $limit = 10, int $days = 30): array
    {
        $cacheKey = $this->getCacheKey("top_products_{$limit}_{$days}", $user);

        return Cache::remember($cacheKey, 600, function () use ($limit, $days) {
            $topProducts = $this->dashboardRepository->getTopSellingProducts($limit, $days);
            return $this->formatTopProducts($topProducts);
        });
    }

    /**
     * Alertes de stock
     */
    public function getStockAlerts(User $user): array
    {
        $cacheKey = $this->getCacheKey('stock_alerts', $user);

        return Cache::remember($cacheKey, 300, function () {
            return $this->stockAlertService->getAlertsSummary();
        });
    }

    /**
     * Résumé du stock
     */
    public function getStockSummary(User $user): array
    {
        $cacheKey = $this->getCacheKey('stock_summary', $user);

        return Cache::remember($cacheKey, 300, function () {
            $stockStats = $this->dashboardService->getStockStats();

            return [
                'alerts' => [
                    'low_stock' => $stockStats['low_stock_alerts'],
                    'out_of_stock' => $stockStats['out_of_stock_alerts'],
                    'total' => $stockStats['low_stock_alerts'] + $stockStats['out_of_stock_alerts'],
                ],
                'value' => [
                    'total' => (float) $stockStats['total_stock_value'],
                    'formatted' => number_format((float) $stockStats['total_stock_value'], 2, ',', ' '),
                ],
            ];
        });
    }

    /**
     * Produits en stock bas
     */
    public function getLowStockProducts(User $user, ?int $limit = null): array
    {
        $cacheKey = $this->getCacheKey('low_stock', $user);

        return Cache::remember($cacheKey, 300, function () use ($limit) {
            $storeId = effective_store_id();
            $products = $this->dashboardRepository->getLowStockProducts($limit);

            return $products->map(function($variant) use ($storeId) {
                $currentStock = $storeId !== null ? $variant->getStoreStock($storeId) : $variant->stock_quantity;

                return [
                    'id' => $variant->id,
                    'product_id' => $variant->product_id,
                    'product_name' => $variant->product->name ?? 'N/A',
                    'variant_name' => $variant->full_name ?? $variant->sku,
                    'sku' => $variant->sku,
                    'current_stock' => $currentStock,
                    'threshold' => $variant->low_stock_threshold,
                    'status' => 'low_stock',
                    'severity' => 'warning',
                ];
            })->values()->toArray();
        });
    }

    /**
     * Produits en rupture de stock
     */
    public function getOutOfStockProducts(User $user, ?int $limit = null): array
    {
        $cacheKey = $this->getCacheKey('out_of_stock', $user);

        return Cache::remember($cacheKey, 300, function () use ($limit) {
            $products = $this->dashboardRepository->getOutOfStockProducts($limit);

            return $products->map(fn($variant) => [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'product_name' => $variant->product->name ?? 'N/A',
                'variant_name' => $variant->full_name ?? $variant->sku,
                'sku' => $variant->sku,
                'current_stock' => 0,
                'status' => 'out_of_stock',
                'severity' => 'critical',
            ])->values()->toArray();
        });
    }

    /**
     * Performance par store (admin/manager uniquement)
     */
    public function getStoresPerformance(User $user): ?array
    {
        if (!user_can_access_all_stores()) {
            return null;
        }

        $stores = $user->defaultOrganization?->stores ?? collect();

        if ($stores->isEmpty()) {
            return [];
        }

        $storeIds = $stores->pluck('id');

        // Single query for all stores sales - optimized
        $salesByStore = Sale::whereIn('store_id', $storeIds)
            ->whereDate('sale_date', today())
            ->where('status', 'completed')
            ->groupBy('store_id')
            ->selectRaw('store_id, SUM(total) as total_sales')
            ->pluck('total_sales', 'store_id');

        // Single query for all stores alerts - optimized
        $alertsByStore = DB::table('product_variants')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->whereIn('products.store_id', $storeIds)
            ->where(function ($query) {
                $query->whereRaw('product_variants.stock_quantity <= product_variants.low_stock_threshold')
                    ->orWhere('product_variants.stock_quantity', '<=', 0);
            })
            ->groupBy('products.store_id')
            ->selectRaw('products.store_id, COUNT(*) as alerts_count')
            ->pluck('alerts_count', 'store_id');

        return $stores->map(function ($store) use ($salesByStore, $alertsByStore) {
            $todaySales = (float) ($salesByStore[$store->id] ?? 0);
            return [
                'id' => $store->id,
                'name' => $store->name,
                'code' => $store->code,
                'today_sales' => $todaySales,
                'today_sales_formatted' => number_format($todaySales, 2, ',', ' '),
                'alerts_count' => $alertsByStore[$store->id] ?? 0,
            ];
        })->toArray();
    }

    /**
     * Ventes par store (comparaison)
     */
    public function getSalesByStore(User $user, string $period = 'month'): ?array
    {
        if (!user_can_access_all_stores()) {
            return null;
        }

        $stores = $user->defaultOrganization?->stores ?? collect();

        if ($stores->isEmpty()) {
            return [];
        }

        $dateRange = $this->getDateRange($period);

        $storesData = $stores->map(function ($store) use ($dateRange) {
            $sales = Sale::where('store_id', $store->id)
                ->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
                ->where('status', 'completed')
                ->sum('total');

            $salesCount = Sale::where('store_id', $store->id)
                ->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
                ->where('status', 'completed')
                ->count();

            return [
                'id' => $store->id,
                'name' => $store->name,
                'total_sales' => $sales,
                'sales_count' => $salesCount,
            ];
        });

        $totalSales = $storesData->sum('total_sales');

        return $storesData->map(function ($store) use ($totalSales) {
            $store['percentage'] = $totalSales > 0
                ? round(($store['total_sales'] / $totalSales) * 100, 1)
                : 0;
            return $store;
        })->toArray();
    }

    /**
     * Stock par store (comparaison)
     */
    public function getStockByStore(User $user): ?array
    {
        if (!user_can_access_all_stores()) {
            return null;
        }

        $stores = $user->defaultOrganization?->stores ?? collect();

        if ($stores->isEmpty()) {
            return [];
        }

        return $stores->map(function ($store) {
            $lowStock = $this->getLowStockCountForStore($store->id);
            $outOfStock = $this->getOutOfStockCountForStore($store->id);
            $stockValue = $this->getStockValueForStore($store->id);

            return [
                'id' => $store->id,
                'name' => $store->name,
                'low_stock_count' => $lowStock,
                'out_of_stock_count' => $outOfStock,
                'total_alerts' => $lowStock + $outOfStock,
                'stock_value' => $stockValue,
            ];
        })->toArray();
    }

    /**
     * Invalide le cache pour un utilisateur
     */
    public function invalidateCache(User $user): void
    {
        $prefix = $this->getCachePrefix($user);
        // Note: En production, utiliser Cache::tags() avec Redis
        Cache::forget("{$prefix}_dashboard");
        Cache::forget("{$prefix}_sales_summary");
        Cache::forget("{$prefix}_stock_alerts");
        Cache::forget("{$prefix}_stock_summary");
    }

    // ==================== MÉTHODES PRIVÉES ====================

    private function getCacheKey(string $key, User $user): string
    {
        return $this->getCachePrefix($user) . "_{$key}";
    }

    private function getCachePrefix(User $user): string
    {
        $storeId = effective_store_id() ?? 'all';
        $orgId = $user->default_organization_id ?? 'none';
        return "mobile_report_{$orgId}_{$storeId}";
    }

    private function getSalesForStore(int $storeId): float
    {
        return Sale::where('store_id', $storeId)
            ->whereDate('sale_date', today())
            ->where('status', 'completed')
            ->sum('total') ?? 0;
    }

    private function getAlertsCountForStore(int $storeId): int
    {
        return DB::table('product_variants')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('products.store_id', $storeId)
            ->where(function ($query) {
                $query->whereRaw('product_variants.stock_quantity <= product_variants.low_stock_threshold')
                    ->orWhere('product_variants.stock_quantity', '<=', 0);
            })
            ->count();
    }

    private function getLowStockCountForStore(int $storeId): int
    {
        return DB::table('product_variants')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('products.store_id', $storeId)
            ->whereRaw('product_variants.stock_quantity <= product_variants.low_stock_threshold')
            ->where('product_variants.stock_quantity', '>', 0)
            ->count();
    }

    private function getOutOfStockCountForStore(int $storeId): int
    {
        return DB::table('product_variants')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('products.store_id', $storeId)
            ->where('product_variants.stock_quantity', '<=', 0)
            ->count();
    }

    private function getStockValueForStore(int $storeId): float
    {
        return DB::table('product_variants')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('products.store_id', $storeId)
            ->sum(DB::raw('product_variants.stock_quantity * COALESCE(products.cost_price, 0)')) ?? 0;
    }

    private function getYearSales(): float
    {
        $query = Sale::whereYear('sale_date', now()->year);

        if (!user_can_access_all_stores() && effective_store_id()) {
            $query->where('store_id', effective_store_id());
        }

        return $query->sum('total') ?? 0;
    }

    private function getDateRange(string $period): array
    {
        return match ($period) {
            'day' => ['start' => today(), 'end' => today()],
            'week' => ['start' => now()->startOfWeek(), 'end' => now()->endOfWeek()],
            'month' => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
            'quarter' => ['start' => now()->startOfQuarter(), 'end' => now()->endOfQuarter()],
            'year' => ['start' => now()->startOfYear(), 'end' => now()->endOfYear()],
            default => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
        };
    }

    private function formatChartData($chartData, string $period = 'week'): array
    {
        if ($chartData instanceof Collection) {
            $labels = $chartData->map(fn($item) => Carbon::parse($item->day)->format('d/m'))->toArray();
            $values = $chartData->pluck('total')->toArray();
        } else {
            $labels = [];
            $values = [];
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'total' => array_sum($values),
            'average' => count($values) > 0 ? round(array_sum($values) / count($values), 2) : 0,
        ];
    }

    private function formatTopProducts($topProducts): array
    {
        if ($topProducts instanceof Collection) {
            return $topProducts->map(fn($product) => [
                'name' => $product->name,
                'quantity' => $product->total_quantity,
                'revenue' => $product->total_revenue,
            ])->toArray();
        }

        return [];
    }

    private function formatRecentSales($recentSales): array
    {
        if ($recentSales instanceof Collection) {
            return $recentSales->map(fn($sale) => [
                'id' => $sale->id,
                'reference' => $sale->sale_number,
                'total' => $sale->total,
                'client' => $sale->client?->name ?? 'Client anonyme',
                'date' => $sale->sale_date->format('Y-m-d H:i'),
                'status' => $sale->status,
            ])->toArray();
        }

        return [];
    }
}

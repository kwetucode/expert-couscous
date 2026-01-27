<?php

namespace App\Repositories;

use App\Models\Client;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardRepository
{
    /**
     * Apply store filter to a query builder
     * Uses effective_store_id() which takes into account request store_id parameter
     */
    private function applyStoreFilter($query, string $storeColumn = 'store_id')
    {
        $storeId = effective_store_id();

        if ($storeId) {
            $query->where($storeColumn, $storeId);
        }

        return $query;
    }

    /**
     * Apply store filter to a query with product relationship
     * Uses effective_store_id() which takes into account request store_id parameter
     */
    private function applyStoreFilterViaProduct($query)
    {
        $storeId = effective_store_id();

        if ($storeId) {
            // Use storeStocks relation instead of product.store_id
            $query->whereHas('storeStocks', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            });
        }

        return $query;
    }

    /**
     * Get total number of products
     */
    public function getTotalProducts(): int
    {
        $query = Product::query();
        
        $storeId = effective_store_id();
        if ($storeId) {
            // Filter products that have stock in the selected store
            $query->whereHas('storeStock', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            });
        }
        
        return $query->count();
    }

    /**
     * Get total number of clients
     */
    public function getTotalClients(): int
    {
        return Client::count();
    }

    /**
     * Get total number of suppliers
     */
    public function getTotalSuppliers(): int
    {
        return Supplier::count();
    }

    /**
     * Get today's sales total
     */
    public function getTodaySales(): float
    {
        $query = Sale::whereDate('sale_date', today());
        $this->applyStoreFilter($query);
        return $query->sum('total') ?? 0;
    }

    /**
     * Get sales total for a specific month
     */
    public function getMonthSales(Carbon $date): float
    {
        $query = Sale::whereMonth('sale_date', $date->month)
            ->whereYear('sale_date', $date->year);
        $this->applyStoreFilter($query);
        return $query->sum('total') ?? 0;
    }

    /**
     * Get total number of sales
     */
    public function getTotalSalesCount(): int
    {
        $query = Sale::query();
        $this->applyStoreFilter($query);
        return $query->count();
    }

    /**
     * Get sales total for a specific date
     */
    public function getSalesByDate(string $date): float
    {
        $query = Sale::whereDate('sale_date', $date);
        $this->applyStoreFilter($query);
        return $query->sum('total') ?? 0;
    }

    /**
     * Get sales between two dates
     */
    public function getSalesBetweenDates(Carbon $startDate, Carbon $endDate): float
    {
        $query = Sale::whereBetween('sale_date', [$startDate, $endDate]);
        $this->applyStoreFilter($query);
        return $query->sum('total') ?? 0;
    }

    /**
     * Get count of low stock products
     */
    public function getLowStockCount(): int
    {
        $query = ProductVariant::whereRaw('stock_quantity <= low_stock_threshold')
            ->where('stock_quantity', '>', 0);
        $this->applyStoreFilterViaProduct($query);
        return $query->count();
    }

    /**
     * Get count of out of stock products
     */
    public function getOutOfStockCount(): int
    {
        $query = ProductVariant::where('stock_quantity', 0);
        $this->applyStoreFilterViaProduct($query);
        return $query->count();
    }

    /**
     * Get total stock value
     */
    public function getTotalStockValue(): float
    {
        $query = ProductVariant::query();
        $this->applyStoreFilterViaProduct($query);

        return $query->sum(
            DB::raw('stock_quantity * COALESCE((SELECT cost_price FROM products WHERE products.id = product_variants.product_id), 0)')
        ) ?? 0;
    }

    /**
     * Get recent sales with client information
     */
    public function getRecentSales(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $query = Sale::with('client')
            ->whereBetween('sale_date', [now()->subDays(6), now()]);
        $this->applyStoreFilter($query);

        return $query->orderBy('sale_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top selling products for a specific period
     */
    public function getTopSellingProducts(int $limit = 5, int $days = 30): \Illuminate\Support\Collection
    {
        $storeId = effective_store_id();

        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('product_variants', 'sale_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select(
                'products.name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->whereBetween('sales.sale_date', [now()->subDays($days), now()])
            ->whereNull('sales.deleted_at');

        // Filter by current store if user is not admin
        if (!user_can_access_all_stores()) {
            if ($storeId) {
                $query->where('sales.store_id', $storeId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent stock movements
     */
    public function getRecentStockMovements(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        $query = StockMovement::with('productVariant.product');
        $this->applyStoreFilter($query);

        return $query->orderBy('date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get sales grouped by date range
     */
    public function getSalesGroupedByDate(Carbon $startDate, Carbon $endDate, string $groupBy = 'day'): \Illuminate\Support\Collection
    {
        $dateFormat = match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m-%d'
        };

        $query = Sale::selectRaw("DATE_FORMAT(sale_date, '{$dateFormat}') as period, SUM(total) as total")
            ->whereBetween('sale_date', [$startDate, $endDate]);
        $this->applyStoreFilter($query);

        return $query->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    /**
     * Get low stock products with details
     */
    public function getLowStockProducts(int $limit = null): \Illuminate\Database\Eloquent\Collection
    {
        $storeId = effective_store_id();

        $query = ProductVariant::with(['product', 'storeStocks']);
        $this->applyStoreFilterViaProduct($query);

        $query->orderBy('stock_quantity', 'asc');

        // Don't limit here, filter first then limit
        $variants = $query->get();

        // Filter by store-specific stock quantities
        $filtered = $variants->filter(function($variant) use ($storeId) {
            $storeQty = $storeId !== null ? $variant->getStoreStock($storeId) : $variant->stock_quantity;
            return $storeQty > 0 && $storeQty <= $variant->low_stock_threshold;
        });

        // Apply limit after filtering
        if ($limit) {
            return $filtered->take($limit);
        }

        return $filtered;
    }

    /**
     * Get out of stock products with details
     */
    public function getOutOfStockProducts(int $limit = null): \Illuminate\Database\Eloquent\Collection
    {
        $storeId = effective_store_id();

        $query = ProductVariant::with(['product', 'storeStocks']);
        $this->applyStoreFilterViaProduct($query);

        $query->orderBy('updated_at', 'desc');

        // Don't limit here, filter first then limit
        $variants = $query->get();

        // Filter by store-specific stock quantities
        $filtered = $variants->filter(function($variant) use ($storeId) {
            $storeQty = $storeId !== null ? $variant->getStoreStock($storeId) : $variant->stock_quantity;
            return $storeQty <= 0;
        });

        // Apply limit after filtering
        if ($limit) {
            return $filtered->take($limit);
        }

        return $filtered;
    }
}

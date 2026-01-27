<?php

namespace App\Services;

use App\Repositories\ProductVariantRepository;
use App\Repositories\CategoryRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockOverviewService
{
    public function __construct(
        private ProductVariantRepository $variantRepository,
        private CategoryRepository $categoryRepository
    ) {}

    /**
     * Calculate all KPIs for stock overview dashboard.
     */
    public function calculateKPIs(): array
    {
        $currentStoreId = current_store_id();

        $query = $this->variantRepository->query()
            ->with(['product', 'storeStocks']);

        // Filter by current store using storeStocks relation
        if ($currentStoreId !== null) {
            $query->whereHas('storeStocks', function($q) use ($currentStoreId) {
                $q->where('store_id', $currentStoreId)
                  ->where('quantity', '>', 0);
            });
        }

        $allVariants = $query->get();

        // Get store-specific stock quantities
        $inStock = $allVariants->filter(function($v) use ($currentStoreId) {
            $storeQty = $currentStoreId !== null ? $v->getStoreStock($currentStoreId) : $v->stock_quantity;
            return $storeQty > 0;
        });

        $outOfStock = $allVariants->filter(function($v) use ($currentStoreId) {
            $storeQty = $currentStoreId !== null ? $v->getStoreStock($currentStoreId) : $v->stock_quantity;
            return $storeQty <= 0;
        });

        $lowStock = $allVariants->filter(function($v) use ($currentStoreId) {
            $storeQty = $currentStoreId !== null ? $v->getStoreStock($currentStoreId) : $v->stock_quantity;
            return $storeQty > 0 && $storeQty <= $v->low_stock_threshold;
        });

        // Calculate total stock value (cost) - use store-specific quantities
        $totalStockValue = $inStock->sum(function ($variant) use ($currentStoreId) {
            $storeQty = $currentStoreId !== null ? $variant->getStoreStock($currentStoreId) : $variant->stock_quantity;
            return $storeQty * ($variant->product->cost_price ?? 0);
        });

        // Calculate total retail value - use store-specific quantities
        $totalRetailValue = $inStock->sum(function ($variant) use ($currentStoreId) {
            $storeQty = $currentStoreId !== null ? $variant->getStoreStock($currentStoreId) : $variant->stock_quantity;
            return $storeQty * ($variant->product->price ?? 0);
        });

        // Calculate potential profit
        $potentialProfit = $totalRetailValue - $totalStockValue;

        // Calculate total units - use store-specific quantities
        $totalUnits = $inStock->sum(function ($variant) use ($currentStoreId) {
            return $currentStoreId !== null ? $variant->getStoreStock($currentStoreId) : $variant->stock_quantity;
        });

        return [
            'total_stock_value' => round($totalStockValue, 2),
            'total_retail_value' => round($totalRetailValue, 2),
            'potential_profit' => round($potentialProfit, 2),
            'profit_margin_percentage' => $totalRetailValue > 0
                ? round(($potentialProfit / $totalRetailValue) * 100, 2)
                : 0,
            'total_products' => $allVariants->count(),
            'in_stock_count' => $inStock->count(),
            'out_of_stock_count' => $outOfStock->count(),
            'low_stock_count' => $lowStock->count(),
            'total_units' => $totalUnits,
        ];
    }

    /**
     * Get variants for inventory overview with filters.
     */
    public function getInventoryVariants(array $filters = []): Collection
    {
        $currentStoreId = current_store_id();

        $query = $this->variantRepository->query()
            ->with(['product.category', 'storeStocks']);

        // Filter by current store using storeStocks relation
        if ($currentStoreId !== null) {
            $query->whereHas('storeStocks', function($q) use ($currentStoreId) {
                $q->where('store_id', $currentStoreId);
            });
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            })->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('barcode', 'like', "%{$search}%");
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        // Stock level filter
        if (!empty($filters['stock_level'])) {
            switch ($filters['stock_level']) {
                case 'in_stock':
                    $query->where('stock_quantity', '>', 0)
                          ->whereColumn('stock_quantity', '>', 'low_stock_threshold');
                    break;
                case 'low_stock':
                    $query->where('stock_quantity', '>', 0)
                          ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
                    break;
                case 'out_of_stock':
                    $query->where('stock_quantity', '<=', 0);
                    break;
            }
        }

        // Sorting
        $sortField = $filters['sort_field'] ?? 'stock_quantity';
        $sortDirection = $filters['sort_direction'] ?? 'asc';

        switch ($sortField) {
            case 'name':
                $query->join('products', 'product_variants.product_id', '=', 'products.id')
                      ->orderBy('products.name', $sortDirection)
                      ->select('product_variants.*');
                break;
            case 'value':
                // Sort by stock value (quantity * cost_price)
                $query->leftJoin('products', 'product_variants.product_id', '=', 'products.id')
                      ->orderByRaw("(product_variants.stock_quantity * COALESCE(products.cost_price, 0)) {$sortDirection}")
                      ->select('product_variants.*');
                break;
            case 'stock_quantity':
            default:
                $query->orderBy('stock_quantity', $sortDirection);
                break;
        }

        return $query->get();
    }

    /**
     * Get stock value by category for breakdown.
     */
    public function getStockValueByCategory(): Collection
    {
        $query = $this->variantRepository->query()
            ->with('product.category')
            ->where('stock_quantity', '>', 0);

        // Filter by current store
        if (current_store_id()) {
            $query->whereHas('product', function($q) {
                $q->where('store_id', current_store_id());
            });
        }

        $variants = $query->get();

        return $variants->groupBy(function ($variant) {
            return $variant->product->category?->name ?? 'Sans catégorie';
        })->map(function ($categoryVariants, $categoryName) {
            $totalValue = $categoryVariants->sum(function ($variant) {
                return $variant->stock_quantity * ($variant->product->cost_price ?? 0);
            });

            $totalUnits = $categoryVariants->sum('stock_quantity');

            return [
                'category' => $categoryName,
                'variants_count' => $categoryVariants->count(),
                'total_units' => $totalUnits,
                'total_value' => round($totalValue, 2),
            ];
        })->sortByDesc('total_value')->values();
    }

    /**
     * Get top products by stock value.
     */
    public function getTopProductsByValue(int $limit = 10): Collection
    {
        $query = $this->variantRepository->query()
            ->with('product')
            ->where('stock_quantity', '>', 0);

        // Filter by current store
        if (current_store_id()) {
            $query->whereHas('product', function($q) {
                $q->where('store_id', current_store_id());
            });
        }

        return $query->get()
            ->map(function ($variant) {
                $value = $variant->stock_quantity * ($variant->product->cost_price ?? 0);
                return [
                    'variant' => $variant,
                    'value' => $value,
                ];
            })
            ->sortByDesc('value')
            ->take($limit)
            ->values();
    }

    /**
     * Get variants that need restocking based on threshold.
     */
    public function getVariantsNeedingRestock(): Collection
    {
        $query = $this->variantRepository->query()
            ->with('product')
            ->where(function ($query) {
                $query->where('stock_quantity', '<=', 0)
                      ->orWhereColumn('stock_quantity', '<=', 'low_stock_threshold');
            });

        // Filter by current store
        if (current_store_id()) {
            $query->whereHas('product', function($q) {
                $q->where('store_id', current_store_id());
            });
        }

        return $query->orderBy('stock_quantity', 'asc')
            ->get();
    }

    /**
     * Get all categories for filter dropdown.
     */
    public function getCategories(): Collection
    {
        return $this->categoryRepository->all();
    }

    /**
     * Calculate stock metrics for a specific variant.
     */
    public function getVariantMetrics(int $variantId): array
    {
        $variant = $this->variantRepository->find($variantId);

        if (!$variant) {
            return [];
        }

        $stockValue = $variant->stock_quantity * ($variant->product->cost_price ?? 0);
        $retailValue = $variant->stock_quantity * ($variant->product->price ?? 0);
        $potentialProfit = $retailValue - $stockValue;

        return [
            'stock_quantity' => $variant->stock_quantity,
            'stock_value' => round($stockValue, 2),
            'retail_value' => round($retailValue, 2),
            'potential_profit' => round($potentialProfit, 2),
            'stock_status' => $variant->stock_status,
            'stock_percentage' => $variant->stock_level_percentage,
            'low_threshold' => $variant->low_stock_threshold,
            'min_threshold' => $variant->min_stock_threshold,
        ];
    }
}

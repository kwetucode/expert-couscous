<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\StoreStock;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository
{
    /**
     * Apply store filter to a query builder
     * Shows products that either belong to the store OR have stock in the store
     */
    private function applyStoreFilter($query, string $storeColumn = 'store_id')
    {
        $storeId = current_store_id();

        if ($storeId) {
            $query->where(function ($q) use ($storeId, $storeColumn) {
                // Products that belong to this store
                $q->where($storeColumn, $storeId)
                    // OR products that have stock in this store (via variants)
                    ->orWhereHas('variants', function ($variantQuery) use ($storeId) {
                        $variantQuery->whereHas('storeStocks', function ($stockQuery) use ($storeId) {
                            $stockQuery->where('store_id', $storeId)
                                ->where('quantity', '>', 0);
                        });
                    });
            });
        }

        return $query;
    }

    /**
     * Count all products.
     */
    public function count(): int
    {
        $query = Product::query();
        $this->applyStoreFilter($query);
        return $query->count();
    }

    /**
     * Count products with low stock alerts.
     */
    public function countLowStockAlerts(): int
    {
        $query = Product::where('status', 'active')
            ->whereHas('variants', function ($q) {
                $q->whereColumn('stock_quantity', '<=', 'products.stock_alert_threshold');
            });
        $this->applyStoreFilter($query);
        return $query->count();
    }

    /**
     * Get all products.
     */
    public function all(): Collection
    {
        $query = Product::with('category');
        $this->applyStoreFilter($query);
        return $query->orderBy('name')->get();
    }

    /**
     * Get paginated products.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::with('category', 'variants');
        $this->applyStoreFilter($query);
        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Find product by ID.
     */
    public function find(int $id): ?Product
    {
        return Product::with('category', 'variants')->find($id);
    }

    /**
     * Find product by reference.
     */
    public function findByReference(string $reference): ?Product
    {
        return Product::where('reference', $reference)->first();
    }

    /**
     * Create a new product.
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * Update a product.
     */
    public function update(Product $product, array $data): bool
    {
        return $product->update($data);
    }

    /**
     * Delete a product.
     */
    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    /**
     * Get active products only.
     */
    public function active(): Collection
    {
        $query = Product::active()->with('category', 'variants');
        $this->applyStoreFilter($query);
        return $query->orderBy('name')->get();
    }

    /**
     * Get products by category.
     */
    public function byCategory(int $categoryId): Collection
    {
        $query = Product::where('category_id', $categoryId)->with('variants');
        $this->applyStoreFilter($query);
        return $query->orderBy('name')->get();
    }

    /**
     * Search products by name or reference.
     */
    public function search(string $query): Collection
    {
        $builder = Product::where(function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('reference', 'like', "%{$query}%");
        });
        $this->applyStoreFilter($builder);

        return $builder->with('category', 'variants')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get products for report with optional filters.
     */
    public function forReport(?int $categoryId = null, ?string $status = null): Collection
    {
        $query = Product::with(['category', 'variants'])
            ->orderBy('name');
        $this->applyStoreFilter($query);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Get low stock products.
     */
    public function lowStock(int $threshold = 10): Collection
    {
        $query = Product::whereHas('variants', function ($q) use ($threshold) {
            $q->where('stock_quantity', '<=', $threshold);
        })->with('variants');
        $this->applyStoreFilter($query);
        return $query->get();
    }

    /**
     * Get products with total stock.
     */
    public function withStock(): Collection
    {
        $query = Product::with(['variants' => function ($q) {
            $q->select('product_id', 'stock_quantity');
        }]);
        $this->applyStoreFilter($query);
        return $query->get();
    }

    /**
     * Get paginated products with filters and sorting.
     */
    public function paginateWithFilters(
        int $perPage = 15,
        ?string $search = null,
        ?int $categoryId = null,
        ?string $status = null,
        ?string $stockLevel = null,
        string $sortField = 'name',
        string $sortDirection = 'asc'
    ): LengthAwarePaginator {
        $query = Product::with(['category', 'variants', 'productType']);

        // Filter by current store if user is not admin
        // Show products that belong to this store OR have stock in this store
        if (!user_can_access_all_stores() && effective_store_id()) {
            $storeId = effective_store_id();
            $query->where(function ($q) use ($storeId) {
                $q->where('store_id', $storeId)
                    ->orWhereHas('variants', function ($variantQuery) use ($storeId) {
                        $variantQuery->whereHas('storeStocks', function ($stockQuery) use ($storeId) {
                            $stockQuery->where('store_id', $storeId)
                                ->where('quantity', '>', 0);
                        });
                    });
            });
        }

        // Apply search
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('reference', 'like', '%' . $search . '%');
            });
        }

        // Apply category filter
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // Apply status filter
        if ($status) {
            $query->where('status', $status);
        }

        // Apply stock level filter
        if ($stockLevel) {
            $query->whereIn('id', function($subQuery) use ($stockLevel) {
                $subQuery->select('product_id')
                    ->from('product_variants')
                    ->groupBy('product_id');

                switch ($stockLevel) {
                    case 'low':
                        // Stock faible: 0-10
                        $subQuery->havingRaw('SUM(stock_quantity) <= 10');
                        break;
                    case 'medium':
                        // Stock moyen: 11-50
                        $subQuery->havingRaw('SUM(stock_quantity) > 10 AND SUM(stock_quantity) <= 50');
                        break;
                    case 'high':
                        // Stock élevé: > 50
                        $subQuery->havingRaw('SUM(stock_quantity) > 50');
                        break;
                }
            });
        }

        // Apply sorting
        if ($sortField === 'category') {
            $query->join('categories', 'products.category_id', '=', 'categories.id')
                  ->orderBy('categories.name', $sortDirection)
                  ->select('products.*');
        } elseif ($sortField === 'stock') {
            // Sort by total stock from variants
            $query->leftJoin('product_variants', 'products.id', '=', 'product_variants.product_id')
                  ->selectRaw('products.*, COALESCE(SUM(product_variants.stock_quantity), 0) as total_stock')
                  ->groupBy('products.id')
                  ->orderBy('total_stock', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get query builder for products (internal use only).
     */
    public function query()
    {
        return Product::query();
    }

    /**
     * Get all products with filters (no pagination) for exports.
     */
    public function getAllWithFilters(
        ?string $search = null,
        ?int $categoryId = null,
        ?string $status = null,
        ?string $stockLevel = null,
        string $sortField = 'name',
        string $sortDirection = 'asc'
    ): Collection {
        $query = Product::with(['category', 'variants']);

        // Apply search
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('reference', 'like', '%' . $search . '%');
            });
        }

        // Apply category filter
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // Apply status filter
        if ($status) {
            $query->where('status', $status);
        }

        // Apply stock level filter
        if ($stockLevel) {
            $query->whereIn('id', function($subQuery) use ($stockLevel) {
                $subQuery->select('product_id')
                    ->from('product_variants')
                    ->groupBy('product_id');

                switch ($stockLevel) {
                    case 'low':
                        $subQuery->havingRaw('SUM(stock_quantity) <= 10');
                        break;
                    case 'medium':
                        $subQuery->havingRaw('SUM(stock_quantity) > 10 AND SUM(stock_quantity) <= 50');
                        break;
                    case 'high':
                        $subQuery->havingRaw('SUM(stock_quantity) > 50');
                        break;
                }
            });
        }

        // Apply sorting
        if ($sortField === 'category') {
            $query->join('categories', 'products.category_id', '=', 'categories.id')
                  ->orderBy('categories.name', $sortDirection)
                  ->select('products.*');
        } elseif ($sortField === 'stock') {
            $query->leftJoin('product_variants', 'products.id', '=', 'product_variants.product_id')
                  ->selectRaw('products.*, COALESCE(SUM(product_variants.stock_quantity), 0) as total_stock')
                  ->groupBy('products.id')
                  ->orderBy('total_stock', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        return $query->get();
    }
}

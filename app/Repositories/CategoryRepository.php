<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    /**
     * Get all categories filtered by organization type.
     * Service organizations see only service-related categories.
     */
    public function all(): Collection
    {
        return Category::withoutOrganizationScope()
            ->forCurrentOrganization()
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all categories globally (without organization filtering).
     * Shows all active categories regardless of organization.
     * Used for product forms where categories are managed by super admin.
     */
    public function allGlobal(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get categories for a specific product type.
     */
    public function getByProductType(int $productTypeId): Collection
    {
        return Category::withoutOrganizationScope()
            ->forCurrentOrganization()
            ->where('product_type_id', $productTypeId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get categories for a specific product type globally.
     * Shows all active categories for the given type regardless of organization.
     * Used for product forms where categories are managed by super admin.
     */
    public function getByProductTypeGlobal(int $productTypeId): Collection
    {
        return Category::query()
            ->where('product_type_id', $productTypeId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get categories for current organization only.
     * Utilisé quand on veut filtrer par l'organisation courante.
     */
    public function allForCurrentOrganization(): Collection
    {
        return Category::forCurrentOrganization()->orderBy('name')->get();
    }

    /**
     * Find category by ID (toutes les organisations).
     */
    public function find(int $id): ?Category
    {
        return Category::withoutOrganizationScope()->find($id);
    }

    /**
     * Find category by ID or fail (toutes les organisations).
     */
    public function findOrFail(int $id): Category
    {
        return Category::withoutOrganizationScope()->findOrFail($id);
    }

    /**
     * Find category by slug.
     */
    public function findBySlug(string $slug): ?Category
    {
        return Category::where('slug', $slug)->first();
    }

    /**
     * Create a new category.
     */
    public function create(array $data): Category
    {
        return Category::create($data);
    }

    /**
     * Update a category.
     */
    public function update(Category $category, array $data): bool
    {
        return $category->update($data);
    }

    /**
     * Delete a category.
     */
    public function delete(Category $category): bool
    {
        return $category->delete();
    }

    /**
     * Get categories with product count.
     */
    public function withProductCount(): Collection
    {
        return Category::withCount('products')->orderBy('name')->get();
    }

    /**
     * Get categories with active products only.
     */
    public function withActiveProducts(): Collection
    {
        return Category::with(['products' => function ($query) {
            $query->where('status', 'active');
        }])->orderBy('name')->get();
    }

    /**
     * Get paginated categories with optional search and product type filter.
     * Filtered by organization type (service orgs see service categories).
     */
    public function paginate(?string $search = null, int $perPage = 10, ?int $productTypeId = null)
    {
        return Category::query()
            ->withoutOrganizationScope()
            ->forCurrentOrganization() // Filtrer par type d'organisation
            ->withCount('products')
            ->with(['organization', 'productType'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->when($productTypeId, function ($query, $productTypeId) {
                $query->where('product_type_id', $productTypeId);
            })
            ->orderBy('name')
            ->paginate($perPage);
    }
}

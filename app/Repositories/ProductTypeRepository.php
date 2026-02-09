<?php

namespace App\Repositories;

use App\Models\ProductType;
use Illuminate\Database\Eloquent\Collection;

class ProductTypeRepository
{
    /**
     * Get all product types
     */
    public function all(): Collection
    {
        return ProductType::forCurrentOrganization()
            ->withCount('products')
            ->with('organization')
            ->orderBy('display_order')
            ->get();
    }

    /**
     * Get all active product types
     */
    public function allActive(): Collection
    {
        return ProductType::forCurrentOrganization()->active()->ordered()->get();
    }

    /**
     * Get all active product types globally (without organization filtering).
     * Only filters by is_service based on current organization type.
     * Used for product forms where types are managed by super admin.
     */
    public function allActiveGlobal(): Collection
    {
        $isServiceOrg = is_service_organization();
        
        return ProductType::query()
            ->whereNull('organization_id') // Only global types managed by super admin
            ->where('is_active', true)
            ->where('is_service', $isServiceOrg)
            ->ordered()
            ->get();
    }

    /**
     * Get product type by ID with relationships
     */
    public function findById(int $id): ?ProductType
    {
        return ProductType::with(['attributes', 'categories', 'products'])->find($id);
    }

    /**
     * Get product type by slug
     */
    public function findBySlug(string $slug): ?ProductType
    {
        return ProductType::where('slug', $slug)->first();
    }

    /**
     * Create a new product type
     */
    public function create(array $data): ProductType
    {
        return ProductType::create($data);
    }

    /**
     * Update a product type
     */
    public function update(ProductType $productType, array $data): bool
    {
        return $productType->update($data);
    }

    /**
     * Delete a product type
     */
    public function delete(ProductType $productType): bool
    {
        // Check if it has products or categories
        if ($productType->products()->exists() || $productType->categories()->exists()) {
            return false;
        }

        return $productType->delete();
    }

    /**
     * Get product types with their attributes count
     */
    public function withAttributesCount(): Collection
    {
        return ProductType::withCount('attributes')->ordered()->get();
    }

    /**
     * Get product types with their products count
     */
    public function withProductsCount(): Collection
    {
        return ProductType::withCount('products')->ordered()->get();
    }

    /**
     * Search product types by name
     */
    public function search(string $term): Collection
    {
        return ProductType::where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%")
            ->ordered()
            ->get();
    }

    /**
     * Reorder product types
     */
    public function reorder(array $orderData): void
    {
        foreach ($orderData as $id => $order) {
            ProductType::where('id', $id)->update(['display_order' => $order]);
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive(ProductType $productType): bool
    {
        return $productType->update(['is_active' => !$productType->is_active]);
    }
}

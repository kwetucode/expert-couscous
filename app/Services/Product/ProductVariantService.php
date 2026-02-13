<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductAttributeValue;
use App\Repositories\ProductVariantRepository;
use App\Services\SkuGeneratorService;
use App\Services\VariantGeneratorService;

/**
 * Service responsible for managing product variants
 * Handles variant creation, updates, and attribute management
 */
class ProductVariantService
{
    public function __construct(
        private ProductVariantRepository $variantRepository,
        private SkuGeneratorService $skuGenerator,
        private VariantGeneratorService $variantGeneratorService
    ) {}

    /**
     * Handle variants creation for a product
     * Can auto-generate variants from attributes or create manual variants
     */
    public function handleProductVariants(
        Product $product,
        array $manualVariants,
        array $attributes,
        ?int $productTypeId
    ): void {
        // Try auto-generation first if product type with attributes
        if (!empty($attributes) && $productTypeId) {
            $hasVariants = $this->variantGeneratorService->generateVariants($product, $attributes);
            if ($hasVariants) {
                return; // Auto-generated variants successfully
            }
        }

        // Create manual variants or default variant
        if (!empty($manualVariants) && is_array($manualVariants)) {
            foreach ($manualVariants as $variantData) {
                $variant = $this->createVariant($product->id, $variantData);

                // Save non-variant attributes to each variant
                if (!empty($attributes)) {
                    $this->saveAttributeValues($variant->id, $attributes, false);
                }
            }
        } else {
            // Create a default variant for stock management
            $variant = $this->createDefaultVariant($product);

            // Save attributes to the default variant
            if (!empty($attributes)) {
                $this->saveAttributeValues($variant->id, $attributes, false);
            }
        }
    }

    /**
     * Create a product variant
     */
    public function createVariant(int $productId, array $data): ProductVariant
    {
        // Generate SKU if not provided
        if (!isset($data['sku']) || empty($data['sku'])) {
            $product = Product::find($productId);
            $data['sku'] = $product
                ? $this->skuGenerator->generateDefault($product)
                : 'SKU-' . strtoupper(substr(md5(uniqid()), 0, 8));
        }

        $data['product_id'] = $productId;

        // Extract attributes
        $attributes = $data['attributes'] ?? [];
        unset($data['attributes']);

        $variant = $this->variantRepository->create($data);

        // Save variant attributes
        if (!empty($attributes)) {
            $this->saveAttributeValues($variant->id, $attributes, true);
        }

        return $variant;
    }

    /**
     * Create a default variant for a product
     * Used when no specific variants are defined
     */
    public function createDefaultVariant(Product $product): ProductVariant
    {
        return $this->createVariant($product->id, [
            'variant_name' => 'Default',
            'sku' => $this->skuGenerator->generateDefault($product),
            'additional_price' => 0,
            'stock_quantity' => 0,
            'low_stock_threshold' => $product->stock_alert_threshold ?? 10,
            'min_stock_threshold' => 0,
        ]);
    }

    /**
     * Update a product variant
     */
    public function updateVariant(int $variantId, array $data): ProductVariant
    {
        $variant = $this->variantRepository->find($variantId);

        if (!$variant) {
            throw new \Exception("Variant not found");
        }

        // Extract attributes
        $attributes = $data['attributes'] ?? [];
        unset($data['attributes']);

        $this->variantRepository->update($variant, $data);

        // Update attributes if provided
        if (!empty($attributes)) {
            $this->saveAttributeValues($variant->id, $attributes, true);
        }

        return $variant->fresh('attributeValues.attribute');
    }

    /**
     * Delete a product variant
     */
    public function deleteVariant(int $variantId): bool
    {
        $variant = $this->variantRepository->find($variantId);

        if (!$variant) {
            throw new \Exception("Variant not found");
        }

        // Prevent deleting the last variant
        $product = $variant->product;
        if ($product && $product->variants()->count() <= 1) {
            throw new \Exception(
                "Cannot delete the last variant. A product must have at least one variant."
            );
        }

        // Delete associated attribute values
        ProductAttributeValue::where('product_variant_id', $variantId)->delete();

        return $this->variantRepository->delete($variant);
    }

    /**
     * Save attribute values for a variant
     *
     * @param int $variantId
     * @param array $attributes Attribute ID => value pairs
     * @param bool $onlyVariantAttributes Only save variant-specific attributes
     */
    public function saveAttributeValues(
        int $variantId,
        array $attributes,
        bool $onlyVariantAttributes = false
    ): void {
        foreach ($attributes as $attributeId => $value) {
            if (empty($value)) {
                continue;
            }

            // Filter by variant attributes if requested
            if ($onlyVariantAttributes) {
                $attribute = \App\Models\ProductAttribute::find($attributeId);
                if (!$attribute || !$attribute->is_variant_attribute) {
                    continue;
                }
            }

            ProductAttributeValue::updateOrCreate(
                [
                    'product_variant_id' => $variantId,
                    'product_attribute_id' => $attributeId,
                ],
                [
                    'value' => is_array($value) ? json_encode($value) : $value,
                ]
            );
        }
    }
}

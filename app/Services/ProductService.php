<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Repositories\ProductRepository;
use App\Repositories\ProductVariantRepository;
use App\Services\SkuGeneratorService;
use App\Services\QRCodeGeneratorService;
use App\Services\VariantGeneratorService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductVariantRepository $variantRepository,
        private SkuGeneratorService $skuGenerator,
        private QRCodeGeneratorService $qrCodeGenerator,
        private VariantGeneratorService $variantGeneratorService
    ) {}

    /**
     * Create a product with variants.
     */
    public function createProduct(array $data): Product
    {
        // Vérifier la limite de produits du plan
        $this->checkProductLimit();

        // Get current organization
        $currentOrganization = app()->bound('current_organization') ? app('current_organization') : null;

        // Ensure store_id is set from current user's store
        if (!isset($data['store_id']) || empty($data['store_id'])) {
            $currentStoreId = auth()->user()?->current_store_id;

            if ($currentStoreId) {
                // Verify the store belongs to the current organization
                $store = \App\Models\Store::find($currentStoreId);
                if ($store && (!$currentOrganization || $store->organization_id === $currentOrganization->id)) {
                    $data['store_id'] = $currentStoreId;
                } else {
                    $currentStoreId = null; // Reset if store doesn't match organization
                }
            }

            if (!$currentStoreId) {
                // Get the main store of the current organization
                $storeQuery = \App\Models\Store::where('is_active', true);

                if ($currentOrganization) {
                    $storeQuery->where('organization_id', $currentOrganization->id);
                }

                // Try to get the main store first
                $mainStore = (clone $storeQuery)->where('is_main', true)->first();

                if ($mainStore) {
                    $data['store_id'] = $mainStore->id;
                } else {
                    // If no main store, get the first active store
                    $firstStore = $storeQuery->first();
                    if ($firstStore) {
                        $data['store_id'] = $firstStore->id;
                    } else {
                        throw new \Exception('Aucun magasin disponible dans cette organisation. Veuillez créer un magasin ou sélectionner un magasin actuel.');
                    }
                }
            }
        }

        // Generate unique slug if not provided
        if (!isset($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        // Generate unique QR code if not provided
        if (!isset($data['qr_code']) || empty($data['qr_code'])) {
            $data['qr_code'] = $this->qrCodeGenerator->generateForProduct();
        }

        // Final check: Ensure reference is unique
        if (isset($data['reference'])) {
            $referenceExists = Product::where('reference', $data['reference'])->exists();
            if ($referenceExists) {
                throw new \Exception("La référence {$data['reference']} existe déjà. Veuillez en choisir une autre.");
            }
        }

        // Final check: Ensure barcode is unique (if provided)
        if (isset($data['barcode']) && !empty($data['barcode'])) {
            $barcodeExists = Product::where('barcode', $data['barcode'])->exists();
            if ($barcodeExists) {
                throw new \Exception("Le code-barres {$data['barcode']} existe déjà. Veuillez en générer un autre.");
            }
        }

        // Extract variants and attributes before creating product
        $variants = $data['variants'] ?? [];
        $attributes = $data['attributes'] ?? [];
        unset($data['variants'], $data['attributes']);

        DB::beginTransaction();
        try {
            $product = $this->productRepository->create($data);

            // Handle dynamic attributes and auto-generate variants if needed
            if (!empty($attributes) && isset($data['product_type_id'])) {
                $hasVariants = $this->variantGeneratorService->generateVariants($product, $attributes);

                // If variants were auto-generated, we're done
                if ($hasVariants) {
                    DB::commit();
                    return $product->load('variants', 'productType.attributes');
                }
            }

            // Create manual variants if provided, otherwise create a default variant
            if (!empty($variants) && is_array($variants)) {
                foreach ($variants as $variantData) {
                    $variant = $this->createVariant($product->id, $variantData);

                    // Save non-variant attributes to the default variant
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

            DB::commit();
            return $product->load('variants', 'productType.attributes');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Save attribute values for a product variant
     */
    private function saveAttributeValues(int $variantId, array $attributes, bool $onlyVariantAttributes = false): void
    {
        foreach ($attributes as $attributeId => $value) {
            if (empty($value)) {
                continue;
            }

            // If we only want variant attributes, check the attribute
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

    /**
     * Generate a unique slug for a product.
     */
    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        // Check if slug exists
        while (true) {
            $query = Product::where('slug', $slug);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Update a product.
     */
    public function updateProduct(int $productId, array $data): Product
    {
        $product = $this->productRepository->find($productId);

        if (!$product) {
            throw new \Exception("Product not found");
        }

        // Extract attributes before updating
        $attributes = $data['attributes'] ?? [];
        unset($data['attributes'], $data['variants']);

        DB::beginTransaction();
        try {
            // Generate unique QR code if product doesn't have one
            if (empty($product->qr_code)) {
                $data['qr_code'] = $this->qrCodeGenerator->generateForProduct();
            }

            $this->productRepository->update($product, $data);

            // Update attributes if provided
            if (!empty($attributes) && $product->variants->isNotEmpty()) {
                $defaultVariant = $product->variants->first();
                $this->saveAttributeValues($defaultVariant->id, $attributes, false);
            }

            DB::commit();
            return $product->fresh(['variants', 'productType.attributes']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a product.
     */
    public function deleteProduct(int $productId): bool
    {
        $product = $this->productRepository->find($productId);

        if (!$product) {
            throw new \Exception("Product not found");
        }

        // Check if product has sales
        $hasSales = $product->variants()
            ->whereHas('saleItems')
            ->exists();

        if ($hasSales) {
            throw new \Exception("Cannot delete product with existing sales. Please archive it instead by setting status to 'inactive'.");
        }

        // Delete all variants first (even though cascade should handle it)
        $product->variants()->delete();

        // Delete the product
        return $this->productRepository->delete($product);
    }

    /**
     * Create a product variant.
     */
    public function createVariant(int $productId, array $data): \App\Models\ProductVariant
    {
        $data['product_id'] = $productId;

        // Generate SKU if not provided or empty
        if (empty($data['sku'])) {
            $product = $this->productRepository->find($productId);
            $data['sku'] = $this->skuGenerator->generateForVariant($product, $data);
        }

        return $this->variantRepository->create($data);
    }

    /**
     * Update a product variant.
     */
    public function updateVariant(int $variantId, array $data): \App\Models\ProductVariant
    {
        $variant = $this->variantRepository->find($variantId);

        if (!$variant) {
            throw new \Exception("Variant not found");
        }

        $this->variantRepository->update($variant, $data);

        return $variant->fresh();
    }

    /**
     * Delete a product variant.
     */
    public function deleteVariant(int $variantId): bool
    {
        $variant = $this->variantRepository->find($variantId);

        if (!$variant) {
            throw new \Exception("Variant not found");
        }

        // Check if variant has sales
        if ($variant->saleItems()->count() > 0) {
            throw new \Exception("Cannot delete variant with existing sales history.");
        }

        // Check if it's the last variant of the product
        if ($variant->product->variants()->count() === 1) {
            throw new \Exception("Cannot delete the last variant of a product. Delete the product instead.");
        }

        return $this->variantRepository->delete($variant);
    }

    /**
     * Create a default variant for a product without variants.
     * This ensures all products can be managed in stock.
     */
    public function createDefaultVariant(Product $product): \App\Models\ProductVariant
    {
        return $this->variantRepository->create([
            'product_id' => $product->id,
            'sku' => $this->skuGenerator->generateDefault($product),
            'size' => null,
            'color' => null,
            'stock_quantity' => 0,
            'low_stock_threshold' => $product->stock_alert_threshold ?? 10,
            'min_stock_threshold' => 0,
            'additional_price' => 0,
        ]);
    }

    /**
     * Ensure all products have at least one variant.
     * Useful for migrating existing products without variants.
     */
    public function ensureAllProductsHaveVariants(): int
    {
        $productsWithoutVariants = Product::doesntHave('variants')->get();
        $count = 0;

        foreach ($productsWithoutVariants as $product) {
            $this->createDefaultVariant($product);
            $count++;
        }

        return $count;
    }

    /**
     * Get low stock products.
     */
    public function getLowStockProducts(int $threshold = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->productRepository->lowStock($threshold);
    }

    /**
     * Search products.
     */
    public function searchProducts(string $query): \Illuminate\Database\Eloquent\Collection
    {
        return $this->productRepository->search($query);
    }

    /**
     * Vérifie si l'organisation peut ajouter un produit selon son plan
     * @throws \Exception si la limite est atteinte
     */
    protected function checkProductLimit(): void
    {
        $user = auth()->user();

        if (!$user) {
            return; // Pas d'utilisateur connecté, on laisse passer (sera géré par auth)
        }

        $organizationId = session('current_organization_id') ?? $user->default_organization_id;
        $organization = \App\Models\Organization::find($organizationId);

        if (!$organization) {
            return; // Pas d'organisation, on laisse passer
        }

        if (!$organization->canAddProduct()) {
            $usage = $organization->getProductsUsage();
            throw new \Exception(
                "Limite de produits atteinte ({$usage['current']}/{$usage['max']}). " .
                "Passez à un plan supérieur pour ajouter plus de produits."
            );
        }
    }
}

<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\CategoryRepository;
use Illuminate\Support\Str;

class ReferenceGeneratorService
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {}

    /**
     * Generate a unique reference for a product based on category.
     */
    public function generateForProduct(int $categoryId): string
    {
        $category = $this->categoryRepository->find($categoryId);

        if (!$category) {
            throw new \Exception("Category not found");
        }

        // Generate prefix from category name (first 3 letters, uppercase)
        // Use Str::ascii() to transliterate accented chars (è→e, é→e, etc.)
        // to avoid breaking multi-byte UTF-8 characters with substr()
        $prefix = strtoupper(substr(Str::ascii($category->name), 0, 3));

        // Count existing products in this category (all organizations for unique reference)
        $counter = Product::withoutGlobalScope('organization')
            ->where('category_id', $categoryId)
            ->count() + 1;

        // Generate reference and ensure uniqueness
        $attempts = 0;
        $maxAttempts = 100;

        do {
            // Add random component for better uniqueness
            $randomSuffix = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 2));
            $reference = $prefix . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT) . $randomSuffix;

            $exists = Product::withoutGlobalScope('organization')
                ->where('reference', $reference)
                ->exists();

            if ($exists) {
                $counter++;
                $attempts++;

                // If too many attempts, use timestamp-based approach
                if ($attempts >= $maxAttempts) {
                    $reference = $prefix . '-' . strtoupper(substr(md5(microtime()), 0, 8));
                    $exists = Product::withoutGlobalScope('organization')
                        ->where('reference', $reference)
                        ->exists();
                }
            }
        } while ($exists && $attempts < ($maxAttempts + 10));

        if ($exists) {
            throw new \Exception("Unable to generate unique reference after multiple attempts");
        }

        return $reference;
    }

    /**
     * Check if a reference is unique.
     */
    public function isUnique(string $reference, ?int $excludeProductId = null): bool
    {
        $query = Product::where('reference', $reference);

        if ($excludeProductId) {
            $query->where('id', '!=', $excludeProductId);
        }

        return !$query->exists();
    }

    /**
     * Validate reference format.
     */
    public function validateFormat(string $reference): bool
    {
        // Reference format: ABC-0001XX or ABC-XXXXXXXX (flexible format)
        return (bool) preg_match('/^[A-Z]{3}-[A-Z0-9]{4,10}$/', $reference);
    }
}

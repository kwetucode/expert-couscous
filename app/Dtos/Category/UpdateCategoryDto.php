<?php

namespace App\Dtos\Category;

readonly class UpdateCategoryDto
{
    public function __construct(
        public ?int $product_type_id = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $slug = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            product_type_id: $data['product_type_id'] ?? null,
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            slug: $data['slug'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'product_type_id' => $this->product_type_id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
        ], fn($value) => $value !== null);
    }
}

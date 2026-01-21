<?php

namespace App\Dtos\Category;

readonly class CreateCategoryDto
{
    public function __construct(
        public string $name,
        public ?int $product_type_id = null,
        public ?string $description = null,
        public ?string $slug = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            product_type_id: $data['product_type_id'] ?? null,
            description: $data['description'] ?? null,
            slug: $data['slug'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'product_type_id' => $this->product_type_id,
            'description' => $this->description,
            'slug' => $this->slug,
        ], fn($value) => $value !== null);
    }
}

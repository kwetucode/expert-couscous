<?php

namespace App\Livewire\Forms;

use App\Models\ProductType;
use Livewire\Form;

class ProductTypeForm extends Form
{
    public string $name = '';
    public ?string $slug = '';
    public string $icon = '📦';
    public ?string $description = '';
    public bool $has_variants = false;
    public bool $has_expiry_date = false;
    public bool $has_weight = false;
    public bool $has_dimensions = false;
    public bool $has_serial_number = false;
    public bool $is_active = true;
    public bool $is_service = false;
    public int $display_order = 0;

    protected function rules(): array
    {
        

        return [
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'has_variants' => 'boolean',
            'has_expiry_date' => 'boolean',
            'has_weight' => 'boolean',
            'has_dimensions' => 'boolean',
            'has_serial_number' => 'boolean',
            'is_active' => 'boolean',
            'is_service' => 'boolean',
            'display_order' => 'nullable|integer',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'slug.unique' => 'Ce slug est déjà utilisé.',
        ];
    }

    public function setProductType(ProductType $productType): void
    {
        $this->name = $productType->name;
        $this->icon = $productType->icon ?? '📦';
        $this->description = $productType->description ?? '';
        $this->has_variants = $productType->has_variants ?? false;
        $this->has_expiry_date = $productType->has_expiry_date ?? false;
        $this->has_weight = $productType->has_weight ?? false;
        $this->has_dimensions = $productType->has_dimensions ?? false;
        $this->has_serial_number = $productType->has_serial_number ?? false;
        $this->is_active = $productType->is_active ?? true;
        $this->is_service = $productType->is_service ?? false;
        $this->display_order = $productType->display_order ?? 0;
    }



    public function reset(...$properties): void
    {
        if (empty($properties)) {
            // Reset complet
            $this->name = '';
            $this->icon = '📦';
            $this->description = '';
            $this->has_variants = false;
            $this->has_expiry_date = false;
            $this->has_weight = false;
            $this->has_dimensions = false;
            $this->has_serial_number = false;
            $this->is_active = true;
            $this->is_service = false;
            $this->display_order = 0;
        } else {
            // Reset partiel
            parent::reset(...$properties);
        }
        
        $this->resetValidation();
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'icon' => $this->icon ?: '📦',
            'description' => $this->description ?: null,
            'has_variants' => $this->has_variants,
            'has_expiry_date' => $this->has_expiry_date,
            'has_weight' => $this->has_weight,
            'has_dimensions' => $this->has_dimensions,
            'has_serial_number' => $this->has_serial_number,
            'is_active' => $this->is_active,
            'is_service' => $this->is_service,
            'display_order' => $this->display_order,
        ];
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductType;
use App\Models\ProductAttribute;

class ProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create "Vêtements" product type (for backward compatibility)
        $vetements = ProductType::firstOrCreate(
            ['slug' => 'vetements'],
            [
                'name' => 'Vêtements',
                'icon' => '👕',
                'description' => 'Vêtements et accessoires de mode',
                'has_variants' => true,
                'has_expiry_date' => false,
                'has_weight' => false,
                'has_dimensions' => false,
                'has_serial_number' => false,
                'is_active' => true,
                'display_order' => 1,
            ]
        );

        // Create attributes for "Vêtements"
        $this->createAttribute($vetements->id, 'size', [
            'name' => 'Taille',
            'type' => 'select',
            'options' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
            'is_required' => true,
            'is_variant_attribute' => true,
            'is_filterable' => true,
            'is_visible' => true,
            'display_order' => 1,
        ]);

        $this->createAttribute($vetements->id, 'color', [
            'name' => 'Couleur',
            'type' => 'color',
            'options' => ['Noir', 'Blanc', 'Rouge', 'Bleu', 'Vert', 'Jaune', 'Rose', 'Gris', 'Marron', 'Orange'],
            'is_required' => true,
            'is_variant_attribute' => true,
            'is_filterable' => true,
            'is_visible' => true,
            'display_order' => 2,
        ]);

        $this->createAttribute($vetements->id, 'material', [
            'name' => 'Matière',
            'type' => 'select',
            'options' => ['Coton', 'Polyester', 'Lin', 'Soie', 'Laine', 'Cuir', 'Jean', 'Viscose'],
            'is_required' => false,
            'is_variant_attribute' => false,
            'is_filterable' => true,
            'is_visible' => true,
            'display_order' => 3,
        ]);

        $this->createAttribute($vetements->id, 'gender', [
            'name' => 'Genre',
            'type' => 'select',
            'options' => ['Homme', 'Femme', 'Mixte', 'Enfant'],
            'is_required' => false,
            'is_variant_attribute' => false,
            'is_filterable' => true,
            'is_visible' => true,
            'display_order' => 4,
        ]);

        // Create "Alimentaire" product type
        $alimentaire = ProductType::firstOrCreate(
            ['slug' => 'alimentaire'],
            [
                'name' => 'Alimentaire',
                'icon' => '🍎',
                'description' => 'Produits alimentaires et boissons',
                'has_variants' => false,
                'has_expiry_date' => true,
                'has_weight' => true,
                'has_dimensions' => false,
                'has_serial_number' => false,
                'is_active' => true,
                'display_order' => 2,
            ]
        );

        $this->createAttribute($alimentaire->id, 'net_weight', [
            'name' => 'Poids Net',
            'type' => 'number',
            'options' => null,
            'unit' => 'g',
            'is_required' => true,
            'is_variant_attribute' => false,
            'is_filterable' => false,
            'is_visible' => true,
            'display_order' => 1,
        ]);

        $this->createAttribute($alimentaire->id, 'allergens', [
            'name' => 'Allergènes',
            'type' => 'select',
            'options' => ['Gluten', 'Lactose', 'Arachides', 'Fruits à coque', 'Œufs', 'Soja', 'Poisson', 'Crustacés', 'Aucun'],
            'default_value' => 'Aucun',
            'is_required' => true,
            'is_variant_attribute' => false,
            'is_filterable' => true,
            'is_visible' => true,
            'display_order' => 2,
        ]);

        $this->createAttribute($alimentaire->id, 'is_organic', [
            'name' => 'Bio',
            'type' => 'boolean',
            'options' => null,
            'default_value' => 'false',
            'is_required' => false,
            'is_variant_attribute' => false,
            'is_filterable' => true,
            'is_visible' => true,
            'display_order' => 3,
        ]);

        $this->createAttribute($alimentaire->id, 'origin', [
            'name' => 'Origine',
            'type' => 'text',
            'options' => null,
            'is_required' => false,
            'is_variant_attribute' => false,
            'is_filterable' => true,
            'is_visible' => true,
            'display_order' => 4,
        ]);

        // Create "Électronique" product type
        $electronique = ProductType::firstOrCreate(
            ['slug' => 'electronique'],
            [
                'name' => 'Électronique',
                'icon' => '📱',
                'description' => 'Appareils électroniques et accessoires',
                'has_variants' => true,
                'has_expiry_date' => false,
                'has_weight' => false,
                'has_dimensions' => true,
                'has_serial_number' => true,
                'is_active' => true,
                'display_order' => 3,
            ]
        );

        $this->createAttribute($electronique->id, 'storage_capacity', [
            'name' => 'Capacité de stockage',
            'type' => 'select',
            'options' => ['16GB', '32GB', '64GB', '128GB', '256GB', '512GB', '1TB', '2TB'],
            'is_required' => false,
            'is_variant_attribute' => true,
            'is_filterable' => true,
            'is_visible' => true,
            'display_order' => 1,
        ]);

        $this->createAttribute($electronique->id, 'elec_color', [
            'name' => 'Couleur',
            'type' => 'select',
            'options' => ['Noir', 'Blanc', 'Argent', 'Or', 'Bleu', 'Rouge', 'Vert', 'Rose'],
            'is_required' => false,
            'is_variant_attribute' => true,
            'is_filterable' => true,
            'is_visible' => true,
            'display_order' => 2,
        ]);

        $this->createAttribute($electronique->id, 'ram', [
            'name' => 'RAM',
            'type' => 'select',
            'options' => ['2GB', '4GB', '6GB', '8GB', '12GB', '16GB', '32GB'],
            'is_required' => false,
            'is_variant_attribute' => true,
            'is_filterable' => true,
            'is_visible' => true,
            'display_order' => 3,
        ]);

        $this->createAttribute($electronique->id, 'warranty', [
            'name' => 'Garantie',
            'type' => 'select',
            'options' => ['6 mois', '1 an', '2 ans', '3 ans'],
            'default_value' => '1 an',
            'is_required' => true,
            'is_variant_attribute' => false,
            'is_filterable' => false,
            'is_visible' => true,
            'display_order' => 4,
        ]);

        $this->createAttribute($electronique->id, 'voltage', [
            'name' => 'Tension d\'alimentation',
            'type' => 'select',
            'options' => ['110V', '220V', '110-240V'],
            'default_value' => '220V',
            'is_required' => false,
            'is_variant_attribute' => false,
            'is_filterable' => false,
            'is_visible' => true,
            'display_order' => 5,
        ]);

        $this->command->info('Product types and attributes seeded successfully!');
    }

    /**
     * Create an attribute if it doesn't exist
     */
    private function createAttribute(int $productTypeId, string $code, array $data): void
    {
        ProductAttribute::firstOrCreate(
            [
                'product_type_id' => $productTypeId,
                'code' => $code,
            ],
            array_merge($data, [
                'unit' => $data['unit'] ?? null,
                'default_value' => $data['default_value'] ?? null,
            ])
        );
    }
}

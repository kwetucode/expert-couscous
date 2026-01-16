<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Hommes',
                'description' => 'Vêtements et accessoires pour hommes',
                'slug' => 'hommes',
            ],
            [
                'name' => 'Femmes',
                'description' => 'Vêtements et accessoires pour femmes',
                'slug' => 'femmes',
            ],
            [
                'name' => 'Enfants',
                'description' => 'Vêtements pour enfants et bébés',
                'slug' => 'enfants',
            ],
            [
                'name' => 'Chaussures',
                'description' => 'Chaussures pour tous',
                'slug' => 'chaussures',
            ],
            [
                'name' => 'Accessoires',
                'description' => 'Sacs, ceintures, bijoux, montres',
                'slug' => 'accessoires',
            ],
            [
                'name' => 'Sport',
                'description' => 'Vêtements et équipements sportifs',
                'slug' => 'sport',
            ],
            [
                'name' => 'Lingerie',
                'description' => 'Sous-vêtements et lingerie',
                'slug' => 'lingerie',
            ],
            [
                'name' => 'Jeans',
                'description' => 'Pantalons en denim et jeans',
                'slug' => 'jeans',
            ],
            [
                'name' => 'Vestes & Manteaux',
                'description' => 'Vestes, manteaux, blousons',
                'slug' => 'vestes-manteaux',
            ],
            [
                'name' => 'Grandes Tailles',
                'description' => 'Vêtements grandes tailles',
                'slug' => 'grandes-tailles',
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}

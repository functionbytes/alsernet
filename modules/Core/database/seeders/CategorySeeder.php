<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the categories table with main product categories.
     * Categories are used for:
     * - Product organization in returns system
     * - Return policies by category
     * - Supplier categorization
     * - Inventory management
     *
     * Supports hierarchical structure (parent/child categories).
     */
    public function run(): void
    {
        // Main categories
        $mainCategories = [
            [
                'uid' => Str::ulid(),
                'name' => 'Calzado',
                'slug' => 'calzado',
                'description' => 'Zapatos, botas, zapatillas y otros tipos de calzado',
                'parent_id' => null,
                'position' => 1,
                'active' => true,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Ropa',
                'slug' => 'ropa',
                'description' => 'Prendas de vestir, camisetas, pantalones, etc.',
                'parent_id' => null,
                'position' => 2,
                'active' => true,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Accesorios',
                'slug' => 'accesorios',
                'description' => 'Complementos, mochilas, cinturones, etc.',
                'parent_id' => null,
                'position' => 3,
                'active' => true,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Equipamiento',
                'slug' => 'equipamiento',
                'description' => 'Equipo deportivo y de entrenamiento',
                'parent_id' => null,
                'position' => 4,
                'active' => true,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Electrónica',
                'slug' => 'electronica',
                'description' => 'Dispositivos electrónicos y accesorios',
                'parent_id' => null,
                'position' => 5,
                'active' => true,
            ],
        ];

        // Create main categories
        $createdCategories = [];
        foreach ($mainCategories as $category) {
            $created = Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
            $createdCategories[$category['slug']] = $created;
        }

        // Sub-categories
        $subCategories = [
            'calzado' => [
                [
                    'uid' => Str::ulid(),
                    'name' => 'Zapatillas',
                    'slug' => 'zapatillas',
                    'description' => 'Zapatillas deportivas y casual',
                    'position' => 1,
                ],
                [
                    'uid' => Str::ulid(),
                    'name' => 'Botas',
                    'slug' => 'botas',
                    'description' => 'Botas de montaña, trabajo y casuales',
                    'position' => 2,
                ],
                [
                    'uid' => Str::ulid(),
                    'name' => 'Zapatos',
                    'slug' => 'zapatos',
                    'description' => 'Zapatos formales y casuales',
                    'position' => 3,
                ],
            ],
            'ropa' => [
                [
                    'uid' => Str::ulid(),
                    'name' => 'Camisetas',
                    'slug' => 'camisetas',
                    'description' => 'Camisetas de manga corta y larga',
                    'position' => 1,
                ],
                [
                    'uid' => Str::ulid(),
                    'name' => 'Pantalones',
                    'slug' => 'pantalones',
                    'description' => 'Pantalones cortos, largos y jeans',
                    'position' => 2,
                ],
                [
                    'uid' => Str::ulid(),
                    'name' => 'Abrigos',
                    'slug' => 'abrigos',
                    'description' => 'Chaquetas, suéteres y abrigos',
                    'position' => 3,
                ],
            ],
            'accesorios' => [
                [
                    'uid' => Str::ulid(),
                    'name' => 'Mochilas',
                    'slug' => 'mochilas',
                    'description' => 'Mochilas deportivas y escolares',
                    'position' => 1,
                ],
                [
                    'uid' => Str::ulid(),
                    'name' => 'Gorras',
                    'slug' => 'gorras',
                    'description' => 'Gorras, sombreros y sombrillas',
                    'position' => 2,
                ],
            ],
        ];

        // Create sub-categories
        foreach ($subCategories as $parentSlug => $children) {
            $parentId = $createdCategories[$parentSlug]->id;
            foreach ($children as $subCategory) {
                Category::firstOrCreate(
                    ['slug' => $subCategory['slug']],
                    array_merge($subCategory, ['parent_id' => $parentId, 'active' => true])
                );
            }
        }

        $this->command->info('✅ Categories seeded successfully');
    }
}

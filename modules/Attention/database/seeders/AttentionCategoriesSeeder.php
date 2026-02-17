<?php

namespace Modules\Attention\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttentionCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Atención al Cliente',
                'description' => 'Temas relacionados con servicio al cliente',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'name' => 'Facturación',
                'description' => 'Consultas y reclamos sobre facturas',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'name' => 'Productos y Servicios',
                'description' => 'Información sobre productos y servicios ofrecidos',
                'is_active' => true,
                'order' => 3,
            ],
            [
                'name' => 'Quejas de Calidad',
                'description' => 'Problemas con la calidad de productos o servicios',
                'is_active' => true,
                'order' => 4,
            ],
            [
                'name' => 'Otros',
                'description' => 'Otros temas no categorizados',
                'is_active' => true,
                'order' => 99,
            ],
        ];

        foreach ($categories as $category) {
            DB::table('attention_categories')->insertOrIgnore([
                ...$category,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

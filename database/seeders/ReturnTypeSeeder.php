<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReturnTypeSeeder extends Seeder
{
    public function run()
    {
        $returnTypes = [
            ['id_return_type' => 1, 'name' => 'Reembolso', 'day' => 30, 'color' => '#FF6B6B'],
            ['id_return_type' => 2, 'name' => 'Reemplazo', 'day' => 30, 'color' => '#4ECDC4'],
            ['id_return_type' => 3, 'name' => 'Reparación', 'day' => 30, 'color' => '#45B7D1'],
        ];

        foreach ($returnTypes as $type) {
            DB::table('return_types')->insert([
                'id_return_type' => $type['id_return_type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insertar traducciones para idioma español (id_lang = 1)
            DB::table('return_type_lang')->insert([
                'id_return_type' => $type['id_return_type'],
                'name' => $type['name'],
                'day' => $type['day'],
                'id_shop' => 1,
                'id_lang' => 1,
                'return_color' => $type['color'],
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

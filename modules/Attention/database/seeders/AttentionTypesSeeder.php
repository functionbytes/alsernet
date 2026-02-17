<?php

namespace Modules\Attention\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttentionTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'code' => 'P',
                'name' => 'Petición',
                'description' => 'Solicitud de información, documentos o servicios',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'code' => 'Q',
                'name' => 'Queja',
                'description' => 'Expresión de insatisfacción con un servicio o producto',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'code' => 'R',
                'name' => 'Reclamo',
                'description' => 'Exigencia de solución a un problema o inconformidad',
                'is_active' => true,
                'order' => 3,
            ],
            [
                'code' => 'S',
                'name' => 'Sugerencia',
                'description' => 'Propuesta de mejora o idea para la organización',
                'is_active' => true,
                'order' => 4,
            ],
            [
                'code' => 'F',
                'name' => 'Felicitación',
                'description' => 'Reconocimiento por un servicio o atención excepcional',
                'is_active' => true,
                'order' => 5,
            ],
        ];

        foreach ($types as $type) {
            DB::table('attention_types')->insertOrIgnore([
                ...$type,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

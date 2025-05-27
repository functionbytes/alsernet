<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReturnReasonSeeder extends Seeder
{
    public function run()
    {
        $reasons = [
            // Motivos para Reembolso
            [
                'id' => 1,
                'return_type' => 'refund',
                'active' => true,
                'translations' => [
                    'es' => 'Producto defectuoso',
                    'en' => 'Defective product'
                ]
            ],
            [
                'id' => 2,
                'return_type' => 'refund',
                'active' => true,
                'translations' => [
                    'es' => 'No es lo que esperaba',
                    'en' => 'Not what I expected'
                ]
            ],
            [
                'id' => 3,
                'return_type' => 'refund',
                'active' => true,
                'translations' => [
                    'es' => 'Producto dañado en el envío',
                    'en' => 'Product damaged in shipping'
                ]
            ],
            [
                'id' => 4,
                'return_type' => 'refund',
                'active' => true,
                'translations' => [
                    'es' => 'Talla incorrecta',
                    'en' => 'Wrong size'
                ]
            ],
            [
                'id' => 5,
                'return_type' => 'refund',
                'active' => true,
                'translations' => [
                    'es' => 'Color incorrecto',
                    'en' => 'Wrong color'
                ]
            ],
            [
                'id' => 6,
                'return_type' => 'refund',
                'active' => true,
                'translations' => [
                    'es' => 'Cambié de opinión',
                    'en' => 'Changed my mind'
                ]
            ],

            // Motivos para Reemplazo
            [
                'id' => 7,
                'return_type' => 'replacement',
                'active' => true,
                'translations' => [
                    'es' => 'Producto defectuoso - Quiero reemplazo',
                    'en' => 'Defective product - Want replacement'
                ]
            ],
            [
                'id' => 8,
                'return_type' => 'replacement',
                'active' => true,
                'translations' => [
                    'es' => 'Talla incorrecta - Cambiar talla',
                    'en' => 'Wrong size - Change size'
                ]
            ],
            [
                'id' => 9,
                'return_type' => 'replacement',
                'active' => true,
                'translations' => [
                    'es' => 'Color incorrecto - Cambiar color',
                    'en' => 'Wrong color - Change color'
                ]
            ],
            [
                'id' => 10,
                'return_type' => 'replacement',
                'active' => true,
                'translations' => [
                    'es' => 'Producto incompleto',
                    'en' => 'Incomplete product'
                ]
            ],

            // Motivos para Reparación
            [
                'id' => 11,
                'return_type' => 'repair',
                'active' => true,
                'translations' => [
                    'es' => 'Fallo de funcionamiento',
                    'en' => 'Malfunction'
                ]
            ],
            [
                'id' => 12,
                'return_type' => 'repair',
                'active' => true,
                'translations' => [
                    'es' => 'Problema de software',
                    'en' => 'Software issue'
                ]
            ],
            [
                'id' => 13,
                'return_type' => 'repair',
                'active' => true,
                'translations' => [
                    'es' => 'Daño físico menor',
                    'en' => 'Minor physical damage'
                ]
            ],
            [
                'id' => 14,
                'return_type' => 'repair',
                'active' => true,
                'translations' => [
                    'es' => 'Garantía - Reparación',
                    'en' => 'Warranty - Repair'
                ]
            ],

            // Motivos generales (para todos los tipos)
            [
                'id' => 15,
                'return_type' => 'all',
                'active' => true,
                'translations' => [
                    'es' => 'Producto no coincide con la descripción',
                    'en' => 'Product does not match description'
                ]
            ],
            [
                'id' => 16,
                'return_type' => 'all',
                'active' => true,
                'translations' => [
                    'es' => 'Llegó muy tarde',
                    'en' => 'Arrived too late'
                ]
            ],
            [
                'id' => 17,
                'return_type' => 'all',
                'active' => true,
                'translations' => [
                    'es' => 'Pedido duplicado por error',
                    'en' => 'Duplicate order by mistake'
                ]
            ],
            [
                'id' => 18,
                'return_type' => 'all',
                'active' => true,
                'translations' => [
                    'es' => 'Encontré mejor precio en otro lugar',
                    'en' => 'Found better price elsewhere'
                ]
            ],
            [
                'id' => 19,
                'return_type' => 'all',
                'active' => true,
                'translations' => [
                    'es' => 'Ya no lo necesito',
                    'en' => 'No longer needed'
                ]
            ],
            [
                'id' => 20,
                'return_type' => 'all',
                'active' => true,
                'translations' => [
                    'es' => 'Otro motivo',
                    'en' => 'Other reason'
                ]
            ]
        ];

        foreach ($reasons as $reason) {
            // Insertar motivo
            DB::table('return_reasons')->insert([
                'id' => $reason['id'],
                'return_type' => $reason['return_type'],
                'active' => $reason['active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insertar traducciones
            foreach ($reason['translations'] as $lang => $translation) {
                $langId = $lang === 'es' ? 1 : 2; // Asumir 1=español, 2=inglés

                DB::table('return_reason_lang')->insert([
                    'id' => $reason['id'],
                    'id_lang' => $langId,
                    'id_shop' => 1,
                    'name' => $translation,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

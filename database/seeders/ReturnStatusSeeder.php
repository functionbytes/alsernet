<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReturnStatusSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            // Estado: Nuevo (New)
            [
                'status_id' => 1,
                'state_id' => 1,
                'color' => '#17a2b8',
                'send_email' => true,
                'is_pickup' => false,
                'is_received' => false,
                'is_refunded' => false,
                'shown_to_customer' => true,
                'active' => true,
                'translations' => [
                    'es' => 'Solicitud Recibida',
                    'en' => 'Request Received'
                ]
            ],
            // Estado: Verificación (Verification)
            [
                'status_id' => 2,
                'state_id' => 2,
                'color' => '#ffc107',
                'send_email' => true,
                'is_pickup' => false,
                'is_received' => false,
                'is_refunded' => false,
                'shown_to_customer' => true,
                'active' => true,
                'translations' => [
                    'es' => 'En Verificación',
                    'en' => 'Under Verification'
                ]
            ],
            // Estado: Esperando Paquete
            [
                'status_id' => 3,
                'state_id' => 2,
                'color' => '#fd7e14',
                'send_email' => true,
                'is_pickup' => false,
                'is_received' => false,
                'is_refunded' => false,
                'shown_to_customer' => true,
                'active' => true,
                'translations' => [
                    'es' => 'Esperando Paquete',
                    'en' => 'Waiting for Package'
                ]
            ],
            // Estado: Paquete Recibido
            [
                'status_id' => 4,
                'state_id' => 2,
                'color' => '#6f42c1',
                'send_email' => true,
                'is_pickup' => false,
                'is_received' => true,
                'is_refunded' => false,
                'shown_to_customer' => true,
                'active' => true,
                'translations' => [
                    'es' => 'Paquete Recibido',
                    'en' => 'Package Received'
                ]
            ],
            // Estado: Negociación
            [
                'status_id' => 5,
                'state_id' => 3,
                'color' => '#e83e8c',
                'send_email' => false,
                'is_pickup' => false,
                'is_received' => false,
                'is_refunded' => false,
                'shown_to_customer' => true,
                'active' => true,
                'translations' => [
                    'es' => 'En Negociación',
                    'en' => 'Under Negotiation'
                ]
            ],
            // Estado: Rechazado
            [
                'status_id' => 6,
                'state_id' => 5,
                'color' => '#dc3545',
                'send_email' => true,
                'is_pickup' => false,
                'is_received' => false,
                'is_refunded' => false,
                'shown_to_customer' => true,
                'active' => true,
                'translations' => [
                    'es' => 'Rechazado',
                    'en' => 'Declined'
                ]
            ],
            // Estado: Completado
            [
                'status_id' => 7,
                'state_id' => 5,
                'color' => '#28a745',
                'send_email' => true,
                'is_pickup' => false,
                'is_received' => true,
                'is_refunded' => true,
                'shown_to_customer' => true,
                'active' => true,
                'translations' => [
                    'es' => 'Completado',
                    'en' => 'Completed'
                ]
            ],
            // Estado: Recogida Programada
            [
                'status_id' => 8,
                'state_id' => 2,
                'color' => '#20c997',
                'send_email' => true,
                'is_pickup' => true,
                'is_received' => false,
                'is_refunded' => false,
                'shown_to_customer' => true,
                'active' => true,
                'translations' => [
                    'es' => 'Recogida Programada',
                    'en' => 'Pickup Scheduled'
                ]
            ],
            // Estado: Pendiente
            [
                'status_id' => 9,
                'state_id' => 1,
                'color' => '#6c757d',
                'send_email' => false,
                'is_pickup' => false,
                'is_received' => false,
                'is_refunded' => false,
                'shown_to_customer' => true,
                'active' => true,
                'translations' => [
                    'es' => 'Pendiente',
                    'en' => 'Pending'
                ]
            ],
            // Estado: Reemplazado
            [
                'status_id' => 10,
                'state_id' => 4,
                'color' => '#007bff',
                'send_email' => true,
                'is_pickup' => false,
                'is_received' => true,
                'is_refunded' => false,
                'shown_to_customer' => true,
                'active' => true,
                'translations' => [
                    'es' => 'Reemplazado',
                    'en' => 'Replaced'
                ]
            ],
            // Estado: Reparado
            [
                'status_id' => 11,
                'state_id' => 4,
                'color' => '#17a2b8',
                'send_email' => true,
                'is_pickup' => false,
                'is_received' => true,
                'is_refunded' => false,
                'shown_to_customer' => true,
                'active' => true,
                'translations' => [
                    'es' => 'Reparado',
                    'en' => 'Repaired'
                ]
            ]
        ];

        foreach ($statuses as $status) {
            // Insertar estado
            DB::table('return_status')->insert([
                'status_id' => $status['status_id'],
                'state_id' => $status['state_id'],
                'color' => $status['color'],
                'send_email' => $status['send_email'],
                'is_pickup' => $status['is_pickup'],
                'is_received' => $status['is_received'],
                'is_refunded' => $status['is_refunded'],
                'shown_to_customer' => $status['shown_to_customer'],
                'active' => $status['active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insertar traducciones
            foreach ($status['translations'] as $lang => $translation) {
                $langId = $lang === 'es' ? 1 : 2; // Asumir 1=español, 2=inglés

                DB::table('return_status_lang')->insert([
                    'status_id' => $status['status_id'],
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

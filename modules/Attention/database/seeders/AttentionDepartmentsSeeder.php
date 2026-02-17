<?php

namespace Modules\Attention\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttentionDepartmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Atención al Ciudadano',
                'description' => 'Departamento encargado de la atención directa a ciudadanos y gestión de PQRSF',
                'email' => 'atencion@inoqualab.com',
                'responsible_name' => null,
                'responsible_email' => null,
                'phone' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Jurídica',
                'description' => 'Departamento legal para revisión y validación de documentos y solicitudes legales',
                'email' => 'juridica@inoqualab.com',
                'responsible_name' => null,
                'responsible_email' => null,
                'phone' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Contabilidad',
                'description' => 'Departamento contable y financiero para gestión de presupuesto y pagos',
                'email' => 'contabilidad@inoqualab.com',
                'responsible_name' => null,
                'responsible_email' => null,
                'phone' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Talento Humano',
                'description' => 'Departamento de recursos humanos y gestión del personal',
                'email' => 'talentohumano@inoqualab.com',
                'responsible_name' => null,
                'responsible_email' => null,
                'phone' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Sistemas',
                'description' => 'Departamento de tecnología y sistemas de información',
                'email' => 'sistemas@inoqualab.com',
                'responsible_name' => null,
                'responsible_email' => null,
                'phone' => null,
                'is_active' => true,
            ],
        ];

        foreach ($departments as $dept) {
            DB::table('attention_departments')->insertOrIgnore([
                ...$dept,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

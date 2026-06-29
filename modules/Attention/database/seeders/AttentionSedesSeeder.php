<?php

namespace Modules\Attention\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttentionSedesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sedes = [
            [
                'name' => 'Sede Principal',
                'address' => 'Calle Principal #123',
                'city' => 'Bogotá',
                'phone' => '+57 1 234 5678',
                'email' => 'principal@inoqualab.com',
                'is_active' => true,
            ],
            [
                'name' => 'Portal Web',
                'address' => null,
                'city' => 'Online',
                'phone' => null,
                'email' => 'web@inoqualab.com',
                'is_active' => true,
            ],
        ];

        foreach ($sedes as $sede) {
            DB::table('attention_sedes')->insertOrIgnore([
                ...$sede,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

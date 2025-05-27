<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReturnStatesSeeder extends Seeder
{
    public function run()
    {
        $states = [
            ['id_return_state' => 1, 'name' => 'Nuevo'],
            ['id_return_state' => 2, 'name' => 'Verificación'],
            ['id_return_state' => 3, 'name' => 'Negociación'],
            ['id_return_state' => 4, 'name' => 'Resuelto'],
            ['id_return_state' => 5, 'name' => 'Cerrado'],
        ];

        foreach ($states as $state) {
            DB::table('return_states')->insert([
                'id_return_state' => $state['id_return_state'],
                'name' => $state['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

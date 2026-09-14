<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HelpdeskDemoPreChatFormSeeder extends Seeder
{
    public function run(): void
    {
        if (! DB::connection('helpdesk')->getSchemaBuilder()->hasTable('helpdesk_pre_chat_forms')) {
            $this->command->warn('Tabla helpdesk_pre_chat_forms no existe — saltando');

            return;
        }

        $form = [
            'name' => 'Form pre-chat por defecto',
            'inbox_id' => null,
            'fields' => json_encode([
                ['key' => 'name', 'label' => 'Tu nombre', 'type' => 'text', 'required' => true, 'placeholder' => 'Juan Pérez'],
                ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'placeholder' => 'tu@email.com'],
                ['key' => 'phone', 'label' => 'Teléfono', 'type' => 'tel', 'required' => false, 'placeholder' => '+34 600 000 000'],
                ['key' => 'reason', 'label' => '¿En qué podemos ayudarte?', 'type' => 'select', 'required' => true, 'options' => [
                    ['value' => 'sales', 'label' => 'Información comercial'],
                    ['value' => 'support', 'label' => 'Soporte técnico'],
                    ['value' => 'billing', 'label' => 'Facturación'],
                    ['value' => 'other', 'label' => 'Otro'],
                ]],
            ]),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::connection('helpdesk')->table('helpdesk_pre_chat_forms')->updateOrInsert(
            ['name' => $form['name']],
            $form
        );

        $this->command->info('Pre-chat form demo creado');
    }
}

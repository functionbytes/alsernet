<?php

namespace Modules\Forms\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormField;

class FormsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $form = Form::firstOrCreate(
            ['slug' => 'contacto'],
            [
                'name' => 'Formulario de Contacto',
                'is_active' => true,
                'success_message' => 'Gracias por tu mensaje. Te responderemos pronto.',
            ]
        );

        if (! $form->wasRecentlyCreated) {
            $this->command->info('Formulario de contacto ya existe, se omiten los campos.');

            return;
        }

        $fields = [
            [
                'key' => 'nombre',
                'label' => 'Nombre completo',
                'type' => 'text',
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'email',
                'label' => 'Correo electrónico',
                'type' => 'email',
                'is_required' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'telefono',
                'label' => 'Teléfono',
                'type' => 'tel',
                'is_required' => false,
                'sort_order' => 3,
            ],
            [
                'key' => 'asunto',
                'label' => 'Asunto',
                'type' => 'text',
                'is_required' => true,
                'sort_order' => 4,
            ],
            [
                'key' => 'mensaje',
                'label' => 'Mensaje',
                'type' => 'textarea',
                'is_required' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($fields as $field) {
            FormField::create(array_merge($field, ['form_id' => $form->id]));
        }

        $this->command->info('Formulario de contacto creado con '.count($fields).' campos.');
    }
}

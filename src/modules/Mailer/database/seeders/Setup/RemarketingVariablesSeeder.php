<?php

namespace Modules\Mailer\Database\Seeders\Setup;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Mailer\Models\MailerVariable;

class RemarketingVariablesSeeder extends Seeder
{
    public function run(): void
    {
        $variables = [
            ['key' => 'customer_name',     'name' => 'CUSTOMER_NAME',     'description' => 'Nombre del cliente',          'example_value' => 'Juan',              'category' => 'customer', 'is_system' => true],
            ['key' => 'customer_lastname', 'name' => 'CUSTOMER_LASTNAME', 'description' => 'Apellido del cliente',        'example_value' => 'García',            'category' => 'customer', 'is_system' => true],
            ['key' => 'customer_email',    'name' => 'CUSTOMER_EMAIL',    'description' => 'Email del cliente',           'example_value' => 'juan@example.com',  'category' => 'customer', 'is_system' => true],
            ['key' => 'store_name',        'name' => 'STORE_NAME',        'description' => 'Nombre de la tienda',         'example_value' => 'Mi Tienda',         'category' => 'store',    'is_system' => true],
            ['key' => 'store_domain',      'name' => 'STORE_DOMAIN',      'description' => 'Dominio de la tienda',        'example_value' => 'demo.example.com',  'category' => 'store',    'is_system' => true],
            ['key' => 'unsubscribe_url',   'name' => 'UNSUBSCRIBE_URL',   'description' => 'URL de baja de suscripción',  'example_value' => 'https://example.com/unsubscribe', 'category' => 'links', 'is_system' => true],
            ['key' => 'campaign_name',     'name' => 'CAMPAIGN_NAME',     'description' => 'Nombre de la campaña',        'example_value' => 'Oferta de verano',  'category' => 'campaign', 'is_system' => true],
        ];

        $count = 0;
        foreach ($variables as $variable) {
            MailerVariable::updateOrCreate(
                ['key' => $variable['key'], 'module' => 'remarketing'],
                [
                    'uid' => (string) Str::uuid(),
                    'name' => $variable['name'],
                    'description' => $variable['description'],
                    'example_value' => $variable['example_value'],
                    'category' => $variable['category'],
                    'module' => 'remarketing',
                    'is_system' => $variable['is_system'],
                    'is_enabled' => true,
                ]
            );
            $count++;
        }

        $this->command->info("✓ Remarketing variables seeded ({$count} variables)");
    }
}

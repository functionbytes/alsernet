<?php

namespace Modules\Faqs\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class FaqsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            ['name' => 'faqs.view', 'description' => 'Ver FAQs', 'guard_name' => 'web'],
            ['name' => 'faqs.create', 'description' => 'Crear FAQs', 'guard_name' => 'web'],
            ['name' => 'faqs.update', 'description' => 'Editar FAQs', 'guard_name' => 'web'],
            ['name' => 'faqs.delete', 'description' => 'Eliminar FAQs', 'guard_name' => 'web'],
            ['name' => 'faqs.categories.view', 'description' => 'Ver categorías FAQ', 'guard_name' => 'web'],
            ['name' => 'faqs.categories.create', 'description' => 'Crear categorías FAQ', 'guard_name' => 'web'],
            ['name' => 'faqs.categories.update', 'description' => 'Editar categorías FAQ', 'guard_name' => 'web'],
            ['name' => 'faqs.categories.delete', 'description' => 'Eliminar categorías FAQ', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                ['description' => $permission['description']]
            );
        }
    }
}

<?php

namespace Modules\Supplier\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SupplierUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 29-sep-2026: crea usuarios de prueba. Nunca en producción.
        if (app()->isProduction()) {
            $this->command?->warn('SupplierUserSeeder: omitido en producción (crea usuarios de prueba).');

            return;
        }

        $allPermissions = [
            'modules.view.suppliers',
            'can_sync_suppliers',
            'suppliers.view',
            'suppliers.view.detail',
            'suppliers.view.products',
            'suppliers.view.categories',
            'suppliers.view.sources',
            'suppliers.edit',
            'suppliers.toggle',
            'suppliers.sources.manage',
            'suppliers.categories.manage',
            'suppliers.prompts.manage',
            'suppliers.templates.manage',
            'suppliers.resources.delete',
            'suppliers.sync.trigger',
            'suppliers.sync.erp',
            'suppliers.sync.config',
            'suppliers.sync.retry',
            'suppliers.sync.delete-failures',
            'suppliers.sync.status',
            'suppliers.sync.failures',
            'suppliers.view.content',
            'suppliers.content.publish',
            'suppliers.content.translate',
            'suppliers.content.manage',
            'suppliers.view.automation',
            'suppliers.automation.run',
            'suppliers.automation.manage',
            'suppliers.monitoring.view',
            'suppliers.monitoring.manage',
            'suppliers.ai.premium',
            'suppliers.configure',
            'suppliers.import',
            'suppliers.route.operational',
            'suppliers.route.settings',
        ];

        $users = [
            [
                'email' => 'supplier_manager@alsernet.test',
                'firstname' => 'Supplier',
                'lastname' => 'Manager',
                'role' => 'manager',
                'permissions' => [
                    'modules.view.suppliers',
                    'suppliers.view',
                    'suppliers.view.detail',
                    'suppliers.view.products',
                    'suppliers.view.categories',
                    'suppliers.view.sources',
                    'suppliers.edit',
                    'suppliers.toggle',
                    'suppliers.sources.manage',
                    'suppliers.categories.manage',
                    'suppliers.sync.trigger',
                    'suppliers.sync.status',
                    'suppliers.sync.failures',
                    'suppliers.route.operational',
                ],
            ],
            [
                'email' => 'supplier_viewer@alsernet.test',
                'firstname' => 'Supplier',
                'lastname' => 'Viewer',
                'role' => 'administrative',
                'permissions' => [
                    'modules.view.suppliers',
                    'modules.view.settings',
                    'suppliers.view',
                    'suppliers.view.detail',
                    'suppliers.view.products',
                    'suppliers.view.categories',
                    'suppliers.view.sources',
                    'suppliers.view.content',
                    'suppliers.view.automation',
                    'suppliers.sync.status',
                    'suppliers.sync.failures',
                    'suppliers.prompts.manage',
                    'suppliers.templates.manage',
                    'suppliers.configure',
                    'suppliers.import',
                    'suppliers.route.operational',
                    'suppliers.route.settings',
                ],
            ],
            [
                'email' => 'supplier_admin@alsernet.test',
                'firstname' => 'Supplier',
                'lastname' => 'Admin',
                'role' => 'manager',
                'permissions' => $allPermissions,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'firstname' => $userData['firstname'],
                    'lastname' => $userData['lastname'],
                    'password' => Hash::make(Str::random(32)),
                    'available' => true,
                ]
            );

            if ($user->wasRecentlyCreated) {
                $user->forceFill(['must_change_password' => true])->save();
            }

            $user->syncRoles([$userData['role']]);
            $user->syncPermissions($userData['permissions']);
        }

        $this->command->info('Usuarios de prueba de proveedores creados exitosamente.');
    }
}

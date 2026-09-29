<?php

namespace seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndUsersSeeder extends Seeder
{
    /**
     * Define los roles y usuarios necesarios
     */
    private const ROLES = [
        'super-settings',
        'administrative',
        'return',
        'shop',
        'license',
        'accounting',
        'warehouse',
        'callcenter',
    ];

    public function run(): void
    {
        // 29-sep-2026: seeder de usuarios de prueba ({rol}@alsernet.test, antes
        // con contraseña fija y uno de ellos super-admin). Nunca en producción.
        if (app()->isProduction()) {
            $this->command?->warn('RolesAndUsersSeeder: omitido en producción (crea usuarios de prueba).');

            return;
        }

        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Deshabilitar eventos de Eloquent (activity logging)
        User::withoutEvents(function () {
            // Crear roles y usuarios correspondientes
            foreach (self::ROLES as $roleName) {
                $this->createRoleAndUser($roleName);
            }
        });

        $this->command->info('✅ Roles y usuarios creados exitosamente');
    }

    /**
     * Crear rol y usuario con el mismo nombre
     */
    private function createRoleAndUser(string $roleName): void
    {
        // Crear rol si no existe
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['guard_name' => 'web']
        );

        // Crear usuario con el mismo nombre del rol
        $user = User::firstOrCreate(
            ['email' => "{$roleName}@alsernet.test"],
            [
                'firstname' => $roleName,
                'lastname' => $roleName,
                'password' => Hash::make(Str::random(32)),
                'available' => true,
            ]
        );

        if ($user->wasRecentlyCreated) {
            $user->forceFill(['must_change_password' => true])->save();
        }

        // Asignar rol al usuario si no lo tiene
        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        $this->command->line("  ✓ Rol '{$roleName}' y usuario '{$roleName}' listos");
    }
}

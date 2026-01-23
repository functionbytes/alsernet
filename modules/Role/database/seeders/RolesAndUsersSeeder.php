<?php

namespace seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndUsersSeeder extends Seeder
{
    /**
     * Define los roles y usuarios necesarios
     */
    private const ROLES = [
        'super-admin',
        'administrative',
        'return',
        'shop',
        'license',
        'accounting',
        'warehouse',
        'callcenter',
    ];

    private const PASSWORD = 'secret';

    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

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
                'password' => Hash::make(self::PASSWORD),
                'available' => true,
            ]
        );

        // Asignar rol al usuario si no lo tiene
        if (!$user->hasRole($role)) {
            $user->assignRole($role);
        }

        $this->command->line("  ✓ Rol '{$roleName}' y usuario '{$roleName}' listos");
    }
}

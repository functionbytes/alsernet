<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class EcommercePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = $this->createPermissions();
        $this->createRoles($permissions);
    }

    protected function createPermissions(): array
    {
        $permissions = [
            // Product permissions
            ['name' => 'ecommerce.product.view', 'description' => 'Ver productos', 'guard_name' => 'web'],
            ['name' => 'ecommerce.product.create', 'description' => 'Crear productos', 'guard_name' => 'web'],
            ['name' => 'ecommerce.product.update', 'description' => 'Editar productos', 'guard_name' => 'web'],
            ['name' => 'ecommerce.product.delete', 'description' => 'Eliminar productos', 'guard_name' => 'web'],

            // Category permissions
            ['name' => 'ecommerce.category.view', 'description' => 'Ver categorias', 'guard_name' => 'web'],
            ['name' => 'ecommerce.category.create', 'description' => 'Crear categorias', 'guard_name' => 'web'],
            ['name' => 'ecommerce.category.update', 'description' => 'Editar categorias', 'guard_name' => 'web'],
            ['name' => 'ecommerce.category.delete', 'description' => 'Eliminar categorias', 'guard_name' => 'web'],

            // Brand permissions
            ['name' => 'ecommerce.brand.view', 'description' => 'Ver marcas', 'guard_name' => 'web'],
            ['name' => 'ecommerce.brand.create', 'description' => 'Crear marcas', 'guard_name' => 'web'],
            ['name' => 'ecommerce.brand.update', 'description' => 'Editar marcas', 'guard_name' => 'web'],
            ['name' => 'ecommerce.brand.delete', 'description' => 'Eliminar marcas', 'guard_name' => 'web'],

            // Customer permissions
            ['name' => 'ecommerce.customer.view', 'description' => 'Ver clientes', 'guard_name' => 'web'],
            ['name' => 'ecommerce.customer.create', 'description' => 'Crear clientes', 'guard_name' => 'web'],
            ['name' => 'ecommerce.customer.update', 'description' => 'Editar clientes', 'guard_name' => 'web'],
            ['name' => 'ecommerce.customer.delete', 'description' => 'Eliminar clientes', 'guard_name' => 'web'],

            // Order permissions
            ['name' => 'ecommerce.order.view', 'description' => 'Ver ordenes', 'guard_name' => 'web'],
            ['name' => 'ecommerce.order.create', 'description' => 'Crear ordenes', 'guard_name' => 'web'],
            ['name' => 'ecommerce.order.update', 'description' => 'Editar ordenes', 'guard_name' => 'web'],
            ['name' => 'ecommerce.order.delete', 'description' => 'Eliminar ordenes', 'guard_name' => 'web'],

            // Discount permissions
            ['name' => 'ecommerce.discount.view', 'description' => 'Ver descuentos', 'guard_name' => 'web'],
            ['name' => 'ecommerce.discount.create', 'description' => 'Crear descuentos', 'guard_name' => 'web'],
            ['name' => 'ecommerce.discount.update', 'description' => 'Editar descuentos', 'guard_name' => 'web'],
            ['name' => 'ecommerce.discount.delete', 'description' => 'Eliminar descuentos', 'guard_name' => 'web'],

            // Review permissions
            ['name' => 'ecommerce.review.moderate', 'description' => 'Moderar resenas', 'guard_name' => 'web'],

            // Settings permission
            ['name' => 'ecommerce.settings', 'description' => 'Configuracion ecommerce', 'guard_name' => 'web'],

            // Payment permissions
            ['name' => 'ecommerce.payment.view', 'description' => 'Ver pagos', 'guard_name' => 'web'],
            ['name' => 'ecommerce.payment.refund', 'description' => 'Reembolsar pagos', 'guard_name' => 'web'],
        ];

        $createdPermissions = [];

        foreach ($permissions as $permission) {
            $createdPermissions[] = Permission::firstOrCreate([
                'name' => $permission['name'],
                'guard_name' => $permission['guard_name'],
            ]);

            $this->command->info("Permiso creado: {$permission['name']}");
        }

        return $createdPermissions;
    }

    protected function createRoles(array $permissions): void
    {
        $ecommerceAdmin = Role::firstOrCreate(['name' => 'ecommerce-admin', 'guard_name' => 'web']);
        $ecommerceAdmin->givePermissionTo([
            'ecommerce.product.view', 'ecommerce.product.create', 'ecommerce.product.update', 'ecommerce.product.delete',
            'ecommerce.category.view', 'ecommerce.category.create', 'ecommerce.category.update', 'ecommerce.category.delete',
            'ecommerce.brand.view', 'ecommerce.brand.create', 'ecommerce.brand.update', 'ecommerce.brand.delete',
            'ecommerce.customer.view', 'ecommerce.customer.create', 'ecommerce.customer.update', 'ecommerce.customer.delete',
            'ecommerce.order.view', 'ecommerce.order.create', 'ecommerce.order.update', 'ecommerce.order.delete',
            'ecommerce.discount.view', 'ecommerce.discount.create', 'ecommerce.discount.update', 'ecommerce.discount.delete',
            'ecommerce.review.moderate',
            'ecommerce.settings',
            'ecommerce.payment.view',
            'ecommerce.payment.refund',
        ]);
        $this->command->info('Rol creado: ecommerce-admin');

        $ecommerceManager = Role::firstOrCreate(['name' => 'ecommerce-manager', 'guard_name' => 'web']);
        $ecommerceManager->givePermissionTo([
            'ecommerce.product.view', 'ecommerce.product.create', 'ecommerce.product.update',
            'ecommerce.category.view', 'ecommerce.category.create', 'ecommerce.category.update',
            'ecommerce.brand.view', 'ecommerce.brand.create', 'ecommerce.brand.update',
            'ecommerce.customer.view', 'ecommerce.customer.create', 'ecommerce.customer.update',
            'ecommerce.order.view', 'ecommerce.order.create', 'ecommerce.order.update',
            'ecommerce.discount.view', 'ecommerce.discount.create', 'ecommerce.discount.update',
            'ecommerce.review.moderate',
            'ecommerce.payment.view',
        ]);
        $this->command->info('Rol creado: ecommerce-manager');

        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'ecommerce.product.view', 'ecommerce.product.create', 'ecommerce.product.update', 'ecommerce.product.delete',
                'ecommerce.category.view', 'ecommerce.category.create', 'ecommerce.category.update', 'ecommerce.category.delete',
                'ecommerce.brand.view', 'ecommerce.brand.create', 'ecommerce.brand.update', 'ecommerce.brand.delete',
                'ecommerce.customer.view', 'ecommerce.customer.create', 'ecommerce.customer.update', 'ecommerce.customer.delete',
                'ecommerce.order.view', 'ecommerce.order.create', 'ecommerce.order.update', 'ecommerce.order.delete',
                'ecommerce.discount.view', 'ecommerce.discount.create', 'ecommerce.discount.update', 'ecommerce.discount.delete',
                'ecommerce.review.moderate',
                'ecommerce.settings',
                'ecommerce.payment.view',
                'ecommerce.payment.refund',
            ]);
            $this->command->info('Permisos de ecommerce asignados al rol: admin');
        }

        $this->command->info('');
        $this->command->info('=================================================');
        $this->command->info('Permisos y roles de ecommerce creados exitosamente!');
        $this->command->info('=================================================');
        $this->command->info('');
        $this->command->info('Roles creados:');
        $this->command->info('  - ecommerce-admin: Acceso completo al modulo');
        $this->command->info('  - ecommerce-manager: Gestion sin eliminar');
        $this->command->info('');
    }
}

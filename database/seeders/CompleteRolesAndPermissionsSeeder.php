<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CompleteRolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear todos los permisos
        $permissions = $this->getAllPermissions();

        foreach ($permissions as $permission => $description) {
            Permission::findOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Crear roles y asignar permisos
        $this->createRoles();

    }

    private function getAllPermissions()
    {
        return [
            // Dashboard y estadísticas
            'dashboard.view' => 'Ver dashboard',
            'dashboard.statistics' => 'Ver estadísticas',
            'dashboard.reports' => 'Generar reportes',

            // Gestión de usuarios
            'users.view' => 'Ver usuarios',
            'users.create' => 'Crear usuarios',
            'users.update' => 'Actualizar usuarios',
            'users.delete' => 'Eliminar usuarios',
            'users.roles.assign' => 'Asignar roles',
            'users.permissions.assign' => 'Asignar permisos',

            // Tiendas
            'shops.view' => 'Ver tiendas',
            'shops.create' => 'Crear tiendas',
            'shops.update' => 'Actualizar tiendas',
            'shops.delete' => 'Eliminar tiendas',
            'shops.locations.manage' => 'Gestionar ubicaciones',

            // Productos
            'products.view' => 'Ver productos',
            'products.create' => 'Crear productos',
            'products.update' => 'Actualizar productos',
            'products.delete' => 'Eliminar productos',
            'products.barcode' => 'Generar códigos de barras',
            'products.reports' => 'Generar reportes de productos',

            // Inventarios
            'inventory.view' => 'Ver inventarios',
            'inventory.create' => 'Crear inventarios',
            'inventory.update' => 'Actualizar inventarios',
            'inventory.delete' => 'Eliminar inventarios',
            'inventory.close' => 'Cerrar inventarios',
            'inventory.reports' => 'Generar reportes de inventario',

            // Tickets
            'tickets.view.own' => 'Ver tickets propios',
            'tickets.view.assigned' => 'Ver tickets asignados',
            'tickets.view.all' => 'Ver todos los tickets',
            'tickets.create' => 'Crear tickets',
            'tickets.update' => 'Actualizar tickets',
            'tickets.delete' => 'Eliminar tickets',
            'tickets.assign' => 'Asignar tickets',
            'tickets.close' => 'Cerrar tickets',
            'tickets.reopen' => 'Reabrir tickets',
            'tickets.priority.change' => 'Cambiar prioridad',
            'tickets.mass.delete' => 'Eliminación masiva',
            'tickets.comments.manage' => 'Gestionar comentarios',
            'tickets.categories.manage' => 'Gestionar categorías',
            'tickets.status.manage' => 'Gestionar estados',
            'tickets.priorities.manage' => 'Gestionar prioridades',
            'tickets.groups.manage' => 'Gestionar grupos',
            'tickets.canneds.manage' => 'Gestionar respuestas predefinidas',

            'manager.permissions',
            'manager.permissions.create',
            'manager.permissions.store',
            'manager.permissions.edit',
            'manager.permissions.update',
            'manager.permissions.destroy',

            'manager.langs',
            'manager.langs.create',
            'manager.langs.store',
            'manager.langs.update',
            'manager.langs.edit',
            'manager.langs.view',
            'manager.langs.destroy',
            'manager.langs.categories',


            // FAQs
            'faqs.view' => 'Ver FAQs',
            'faqs.create' => 'Crear FAQs',
            'faqs.update' => 'Actualizar FAQs',
            'faqs.delete' => 'Eliminar FAQs',
            'faqs.categories.manage' => 'Gestionar categorías de FAQs',

            // Suscriptores
            'subscribers.view' => 'Ver suscriptores',
            'subscribers.create' => 'Crear suscriptores',
            'subscribers.update' => 'Actualizar suscriptores',
            'subscribers.delete' => 'Eliminar suscriptores',
            'subscribers.import' => 'Importar suscriptores',
            'subscribers.export' => 'Exportar suscriptores',
            'subscribers.lists.manage' => 'Gestionar listas',
            'subscribers.conditions.manage' => 'Gestionar condiciones',

            // Campañas
            'campaigns.view' => 'Ver campañas',
            'campaigns.create' => 'Crear campañas',
            'campaigns.update' => 'Actualizar campañas',
            'campaigns.delete' => 'Eliminar campañas',
            'campaigns.send' => 'Enviar campañas',
            'campaigns.pause' => 'Pausar campañas',
            'campaigns.restart' => 'Reiniciar campañas',
            'campaigns.statistics' => 'Ver estadísticas',
            'campaigns.templates.manage' => 'Gestionar plantillas',

            // Automatizaciones
            'automations.view' => 'Ver automatizaciones',
            'automations.create' => 'Crear automatizaciones',
            'automations.update' => 'Actualizar automatizaciones',
            'automations.delete' => 'Eliminar automatizaciones',
            'automations.enable' => 'Habilitar automatizaciones',
            'automations.disable' => 'Deshabilitar automatizaciones',

            // Live Chat
            'livechat.view' => 'Ver chat en vivo',
            'livechat.engage' => 'Participar en chats',
            'livechat.settings' => 'Configurar chat',
            'livechat.operators.manage' => 'Gestionar operadores',

            // Documentos administrativos
            'documents.view' => 'Ver documentos',
            'documents.create' => 'Crear documentos',
            'documents.update' => 'Actualizar documentos',
            'documents.delete' => 'Eliminar documentos',
            'documents.files.manage' => 'Gestionar archivos',

            // Devoluciones (Returns)
            'returns.view.own' => 'Ver sus propias devoluciones',
            'returns.view.assigned' => 'Ver devoluciones asignadas',
            'returns.view.all' => 'Ver todas las devoluciones',
            'returns.create' => 'Crear devoluciones',
            'returns.update' => 'Actualizar devoluciones',
            'returns.delete' => 'Eliminar devoluciones',
            'returns.status.update' => 'Cambiar estado',
            'returns.status.approve' => 'Aprobar devoluciones',
            'returns.status.reject' => 'Rechazar devoluciones',
            'returns.assign' => 'Asignar devoluciones',
            'returns.export' => 'Exportar devoluciones',

            // Configuración del sistema
            'system.settings.manage' => 'Gestionar configuración',
            'system.maintenance' => 'Modo mantenimiento',
            'system.logs.view' => 'Ver logs del sistema',
            'system.api.manage' => 'Gestionar API tokens',
            'system.emails.manage' => 'Configurar emails',
            'system.hours.manage' => 'Configurar horarios',
        ];
    }

    private function createRoles()
    {
        // 1. Super Admin - Acceso total
        $superAdminRole = Role::create(['name' => 'super-admin']);
        $superAdminRole->givePermissionTo(Permission::all());

        // 2. Admin - Casi todo excepto configuración crítica
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all()->reject(function ($permission) {
            return in_array($permission->name, [
                'system.maintenance',
                'system.api.manage',
            ]);
        }));

        // 3. Manager - Gestión general sin configuración
        $managerRole = Role::create(['name' => 'manager']);
        $managerRole->givePermissionTo([
            'dashboard.view',
            'dashboard.statistics',
            'dashboard.reports',
            'users.view',
            'shops.view',
            'shops.create',
            'shops.update',
            'products.view',
            'products.create',
            'products.update',
            'products.reports',
            'inventory.view',
            'inventory.create',
            'inventory.update',
            'tickets.view.all',
            'tickets.create',
            'tickets.update',
            'tickets.assign',
            'subscribers.view',
            'subscribers.create',
            'subscribers.update',
            'subscribers.lists.manage',
            'campaigns.view',
            'campaigns.create',
            'campaigns.update',
            'campaigns.send',
            'automations.view',
            'automations.create',
            'automations.update',
            'returns.view.all',
            'returns.update',
            'returns.status.update',
        ]);

        // 4. Call Center Manager
        $callCenterManagerRole = Role::create(['name' => 'callcenter-manager']);
        $callCenterManagerRole->givePermissionTo([
            'dashboard.view',
            'tickets.view.all',
            'tickets.create',
            'tickets.update',
            'tickets.delete',
            'tickets.assign',
            'tickets.close',
            'tickets.reopen',
            'tickets.priority.change',
            'tickets.comments.manage',
            'tickets.categories.manage',
            'tickets.status.manage',
            'tickets.priorities.manage',
            'tickets.groups.manage',
            'tickets.canneds.manage',
            'faqs.view',
            'faqs.create',
            'faqs.update',
            'faqs.delete',
            'faqs.categories.manage',
            'livechat.view',
            'livechat.engage',
            'livechat.settings',
            'livechat.operators.manage',
        ]);

        // 5. Call Center Agent
        $callCenterAgentRole = Role::create(['name' => 'callcenter-agent']);
        $callCenterAgentRole->givePermissionTo([
            'dashboard.view',
            'tickets.view.assigned',
            'tickets.create',
            'tickets.update',
            'tickets.close',
            'tickets.comments.manage',
            'faqs.view',
            'livechat.view',
            'livechat.engage',
        ]);

        // 6. Inventory Manager
        $inventoryManagerRole = Role::create(['name' => 'inventory-manager']);
        $inventoryManagerRole->givePermissionTo([
            'dashboard.view',
            'shops.view',
            'shops.locations.manage',
            'products.view',
            'products.create',
            'products.update',
            'products.barcode',
            'products.reports',
            'inventory.view',
            'inventory.create',
            'inventory.update',
            'inventory.close',
            'inventory.reports',
        ]);

        // 7. Inventory Staff
        $inventoryStaffRole = Role::create(['name' => 'inventory-staff']);
        $inventoryStaffRole->givePermissionTo([
            'dashboard.view',
            'shops.view',
            'products.view',
            'products.barcode',
            'inventory.view',
            'inventory.update',
        ]);

        // 8. Shop Manager
        $shopManagerRole = Role::create(['name' => 'shop-manager']);
        $shopManagerRole->givePermissionTo([
            'dashboard.view',
            'shops.view',
            'shops.update',
            'subscribers.view',
            'subscribers.create',
            'subscribers.update',
            'subscribers.lists.manage',
        ]);

        // 9. Shop Staff
        $shopStaffRole = Role::create(['name' => 'shop-staff']);
        $shopStaffRole->givePermissionTo([
            'dashboard.view',
            'subscribers.view',
            'subscribers.create',
        ]);

        // 10. Administrative
        $administrativeRole = Role::create(['name' => 'administrative']);
        $administrativeRole->givePermissionTo([
            'dashboard.view',
            'documents.view',
            'documents.create',
            'documents.update',
            'documents.files.manage',
            'returns.view.all',
            'returns.create',
            'returns.update',
        ]);

        // 11. Customer
        $customerRole = Role::create(['name' => 'customer']);
        $customerRole->givePermissionTo([
            'returns.view.own',
            'returns.create',
            'tickets.view.own',
            'tickets.create',
        ]);
    }

}

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
            Permission::findOrCreate($permission, 'web');
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

            'manager.permissions' => 'Gestionar permisos del manager', // Added description
            'manager.permissions.create' => 'Crear permisos del manager', // Added description
            'manager.permissions.store' => 'Almacenar permisos del manager', // Added description
            'manager.permissions.edit' => 'Editar permisos del manager', // Added description
            'manager.permissions.update' => 'Actualizar permisos del manager', // Added description
            'manager.permissions.destroy' => 'Eliminar permisos del manager', // Added description

            'manager.langs' => 'Gestionar idiomas del manager', // Added description
            'manager.langs.create' => 'Crear idiomas del manager', // Added description
            'manager.langs.store' => 'Almacenar idiomas del manager', // Added description
            'manager.langs.update' => 'Actualizar idiomas del manager', // Added description
            'manager.langs.edit' => 'Editar idiomas del manager', // Added description
            'manager.langs.view' => 'Ver idiomas del manager', // Added description
            'manager.langs.destroy' => 'Eliminar idiomas del manager', // Added description
            'manager.langs.categories' => 'Gestionar categorías de idiomas del manager', // Added description

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
            // Devoluciones (Returns)
            'returns.view.own' => 'Ver sus propias devoluciones',
            'returns.view.assigned' => 'Ver devoluciones asignadas',
            'returns.view.all' => 'Ver todas las devoluciones',
            'returns.create' => 'Crear devoluciones',
            'returns.update' => 'Actualizar devoluciones',
            'returns.delete' => 'Eliminar devoluciones',
            'returns.status.update' => 'Actualizar el estado de las devoluciones',
            'returns.approve' => 'Aprobar devoluciones',
            'returns.reject' => 'Rechazar devoluciones',
            'returns.assign' => 'Asignar devoluciones',
            'returns.export' => 'Exportar devoluciones',
            'returns.validate' => 'Validar devoluciones',
            'returns.generate' => 'Generar devoluciones',
            'returns.show' => 'Ver detalle de devolución',
            'returns.edit' => 'Editar devolución',
            'returns.payments.view' => 'Ver pagos de devoluciones',
            'returns.payments.add' => 'Añadir pagos a devoluciones',
            'returns.pdf.download' => 'Descargar PDF de devolución',
            'returns.bulk.update' => 'Actualización masiva de devoluciones',
            'returns.products.validate' => 'Validar productos de devolución',
            'returns.cancel' => 'Cancelar devolución',
            'returns.order.products' => 'Obtener productos de una orden (para devolución)',
            'returns.carrier.timeslots.view' => 'Ver franjas horarias de transportistas',
            'returns.inpost.lockers.view' => 'Ver taquillas InPost cercanas',
            'returns.inpost.locker.details.view' => 'Ver detalles de taquilla InPost',
            'returns.available.stores.view' => 'Ver tiendas disponibles para devolución',
            'returns.tracking.status.view' => 'Ver estado de seguimiento de devolución',
            'returns.pickup.cancel' => 'Cancelar recogida de devolución',
            'returns.documents.download' => 'Descargar documento de devolución',
            'returns.barcode.scan' => 'Escanear código de barras de devolución',
            'returns.discussion.add' => 'Añadir discusión a devolución',
            'returns.attachment.upload' => 'Subir adjunto a devolución',
            'returns.review' => 'Revisar devolución',
            'returns.confirm' => 'Confirmar devolución',
            'returns.success' => 'Ver confirmación de devolución',

            // Configuración del sistema
            'system.settings.manage' => 'Gestionar configuración del sistema',
            'system.maintenance' => 'Modo mantenimiento del sistema',
            'system.logs.view' => 'Ver logs del sistema',
            'system.api.manage' => 'Gestionar API tokens del sistema',
            'system.emails.manage' => 'Configurar emails del sistema',
            'system.hours.manage' => 'Configurar horarios del sistema',
        ];
    }

    private function createRoles()
    {
        // 1. Super Admin - Acceso total
        $superAdminRole = Role::findOrCreate('super-admin', 'web'); // Use findOrCreate
        $superAdminRole->givePermissionTo(Permission::all());

        // 2. Admin - Casi todo excepto configuración crítica
        $adminRole = Role::findOrCreate('admin', 'web'); // Use findOrCreate
        $adminRole->givePermissionTo(Permission::all()->reject(function ($permission) {
            return in_array($permission->name, [
                'system.maintenance',
                'system.api.manage',
            ]);
        }));

        // 3. Manager - Gestión general sin configuración
        $managerRole = Role::findOrCreate('manager', 'web'); // Use findOrCreate
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
            'returns.approve',
            'returns.reject',
            'returns.assign',
            'returns.export',
            'returns.pdf.download',
            'returns.bulk.update',
            'returns.validate',
            'returns.generate',
            'returns.show',
            'returns.edit',
            'returns.payments.view',
            'returns.payments.add',
            'returns.products.validate',
            'returns.cancel',
            'returns.order.products',
            'returns.carrier.timeslots.view',
            'returns.inpost.lockers.view',
            'returns.inpost.locker.details.view',
            'returns.available.stores.view',
            'returns.tracking.status.view',
            'returns.pickup.cancel',
            'returns.documents.download',
            'returns.barcode.scan',
            'returns.discussion.add',
            'returns.attachment.upload',
        ]);

        // 4. Call Center Manager
        $callCenterManagerRole = Role::findOrCreate('callcenter-manager', 'web'); // Use findOrCreate
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
            'returns.view.all',
            'returns.create',
            'returns.update',
            'returns.status.update',
            'returns.approve',
            'returns.reject',
            'returns.assign',
            'returns.cancel',
            'returns.order.products',
            'returns.carrier.timeslots.view',
            'returns.inpost.lockers.view',
            'returns.inpost.locker.details.view',
            'returns.available.stores.view',
            'returns.tracking.status.view',
            'returns.pickup.cancel',
            'returns.discussion.add',
            'returns.attachment.upload',
        ]);

        // 5. Call Center Agent
        $callCenterAgentRole = Role::findOrCreate('callcenter-agent', 'web'); // Use findOrCreate
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
            'returns.view.assigned',
            'returns.create',
            'returns.update',
            'returns.status.update',
            'returns.cancel',
            'returns.order.products',
            'returns.carrier.timeslots.view',
            'returns.inpost.lockers.view',
            'returns.inpost.locker.details.view',
            'returns.available.stores.view',
            'returns.generate',
            'returns.validate',

            'returns.tracking.status.view',
            'returns.pickup.cancel',
            'returns.discussion.add',
            'returns.attachment.upload',
        ]);

        // 6. Inventory Manager
        $inventoryManagerRole = Role::findOrCreate('inventory-manager', 'web'); // Use findOrCreate
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
            'returns.view.all',
            'returns.update',
            'returns.status.update',
            'returns.barcode.scan',
            'returns.products.validate',
        ]);

        // 7. Inventory Staff
        $inventoryStaffRole = Role::findOrCreate('inventory-staff', 'web'); // Use findOrCreate
        $inventoryStaffRole->givePermissionTo([
            'dashboard.view',
            'shops.view',
            'products.view',
            'products.barcode',
            'inventory.view',
            'inventory.update',
            'returns.view.all',
            'returns.barcode.scan',
            'returns.products.validate',
        ]);

        // 8. Shop Manager
        $shopManagerRole = Role::findOrCreate('shop-manager', 'web'); // Use findOrCreate
        $shopManagerRole->givePermissionTo([
            'dashboard.view',
            'shops.view',
            'shops.update',
            'subscribers.view',
            'subscribers.create',
            'subscribers.update',
            'subscribers.lists.manage',
            'returns.view.all',
            'returns.create',
            'returns.update',
            'returns.status.update',
            'returns.cancel',
            'returns.available.stores.view',
            'returns.barcode.scan',
        ]);

        // 9. Shop Staff
        $shopStaffRole = Role::findOrCreate('shop-staff', 'web'); // Use findOrCreate
        $shopStaffRole->givePermissionTo([
            'dashboard.view',
            'subscribers.view',
            'subscribers.create',
            'returns.view.all',
            'returns.create',
            'returns.update',
            'returns.barcode.scan',
        ]);

        // 10. Administrative
        $administrativeRole = Role::findOrCreate('administrative', 'web'); // Use findOrCreate
        $administrativeRole->givePermissionTo([
            'dashboard.view',
            'documents.view',
            'documents.create',
            'documents.update',
            'documents.files.manage',
            'returns.view.all',
            'returns.create',
            'returns.update',
            'returns.status.update',
            'returns.approve',
            'returns.reject',
            'returns.export',
            'returns.pdf.download',
            'returns.bulk.update',
            'returns.payments.view',
            'returns.payments.add',
            'returns.discussion.add',
            'returns.attachment.upload',
        ]);

        // 11. Customer
        $customerRole = Role::findOrCreate('customer', 'web'); // Use findOrCreate
        $customerRole->givePermissionTo([
            'returns.view.own',
            'returns.create',
            'tickets.view.own',
            'tickets.create',
            'returns.cancel',
            'returns.tracking.status.view',
            'returns.pdf.download',
            'returns.discussion.add',
            'returns.attachment.upload',
        ]);
    }

}

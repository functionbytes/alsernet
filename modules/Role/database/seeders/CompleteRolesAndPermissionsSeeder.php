<?php

namespace seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
            'inventaries.view' => 'Ver productos',
            'inventaries.create' => 'Crear productos',
            'inventaries.update' => 'Actualizar productos',
            'inventaries.delete' => 'Eliminar productos',
            'inventaries.barcode' => 'Generar códigos de barras',
            'inventaries.reports' => 'Generar reportes de productos',

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

            // Roles y Permisos
            'roles.view' => 'Ver roles',
            'roles.create' => 'Crear roles',
            'roles.edit' => 'Editar roles',
            'roles.delete' => 'Eliminar roles',
            'roles.show.permissions' => 'Ver permisos del rol',
            'roles.update.permissions' => 'Actualizar permisos del rol',
            'roles.show.users' => 'Ver usuarios del rol',
            'roles.assign.users' => 'Asignar usuarios al rol',

            'permissions.view' => 'Ver permisos',
            'permissions.create' => 'Crear permisos',
            'permissions.edit' => 'Editar permisos',
            'permissions.delete' => 'Eliminar permisos',

            'backups.langs' => 'Gestionar idiomas del manager', // Added description
            'backups.langs.create' => 'Crear idiomas del manager', // Added description
            'backups.langs.store' => 'Almacenar idiomas del manager', // Added description
            'backups.langs.update' => 'Actualizar idiomas del manager', // Added description
            'backups.langs.edit' => 'Editar idiomas del manager', // Added description
            'backups.langs.view' => 'Ver idiomas del manager', // Added description
            'backups.langs.destroy' => 'Eliminar idiomas del manager', // Added description
            'backups.langs.categories' => 'Gestionar categorías de idiomas del manager', // Added description

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
            'livechat.backups' => 'Configurar chat',
            'livechat.operators.manage' => 'Gestionar operadores',

            // Documentos administrativos
            'documents.view' => 'Ver documentos',
            'documents.create' => 'Crear documentos',
            'documents.update' => 'Actualizar documentos',
            'documents.delete' => 'Eliminar documentos',
            'documents.files.manage' => 'Gestionar archivos',

            // Devoluciones (Return)
            // Devoluciones (Return)
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
            'returns.inventaries.validate' => 'Validar productos de devolución',
            'returns.cancel' => 'Cancelar devolución',
            'returns.order.inventaries' => 'Obtener productos de una orden (para devolución)',
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
            'system.backups.manage' => 'Gestionar configuración del sistema',
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
            'inventaries.view',
            'inventaries.create',
            'inventaries.update',
            'inventaries.reports',
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
            'returns.inventaries.validate',
            'returns.cancel',
            'returns.order.inventaries',
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

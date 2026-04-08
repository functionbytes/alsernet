<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class TicketPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all ticket-related permissions
        $permissions = $this->getTicketPermissions();

        foreach ($permissions as $permission => $description) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->command->info('Created '.count($permissions).' ticket permissions');
    }

    /**
     * Get all ticket-related permissions
     */
    private function getTicketPermissions(): array
    {
        return [
            // Core CRUD permissions
            'manager.helpdesk.tickets.index' => 'Ver lista de tickets',
            'manager.helpdesk.tickets.show' => 'Ver detalles de ticket',
            'manager.helpdesk.tickets.show.all' => 'Ver todos los tickets',
            'manager.helpdesk.tickets.show.assigned' => 'Ver tickets asignados a mí',
            'manager.helpdesk.tickets.show.group' => 'Ver tickets de mi grupo',
            'manager.helpdesk.tickets.create' => 'Crear tickets',
            'manager.helpdesk.tickets.store' => 'Guardar tickets',
            'manager.helpdesk.tickets.edit' => 'Editar tickets',
            'manager.helpdesk.tickets.update' => 'Actualizar tickets',
            'manager.helpdesk.tickets.update.all' => 'Actualizar todos los tickets',
            'manager.helpdesk.tickets.update.assigned' => 'Actualizar tickets asignados a mí',
            'manager.helpdesk.tickets.update.group' => 'Actualizar tickets de mi grupo',
            'manager.helpdesk.tickets.delete' => 'Eliminar tickets',
            'manager.helpdesk.tickets.destroy' => 'Destruir tickets',
            'manager.helpdesk.tickets.restore' => 'Restaurar tickets eliminados',
            'manager.helpdesk.tickets.force-delete' => 'Eliminar tickets permanentemente',

            // Action permissions
            'manager.helpdesk.tickets.close' => 'Cerrar tickets',
            'manager.helpdesk.tickets.close.unresolved' => 'Cerrar tickets sin resolver',
            'manager.helpdesk.tickets.resolve' => 'Marcar tickets como resueltos',
            'manager.helpdesk.tickets.reopen' => 'Reabrir tickets cerrados',
            'manager.helpdesk.tickets.archive' => 'Archivar tickets',
            'manager.helpdesk.tickets.assign' => 'Asignar tickets',
            'manager.helpdesk.tickets.priority.change' => 'Cambiar prioridad de tickets',

            // Messages and communication
            'manager.helpdesk.tickets.messages.store' => 'Enviar mensajes en tickets',
            'manager.helpdesk.tickets.messages.internal' => 'Enviar notas internas',
            'manager.helpdesk.tickets.attachments.upload' => 'Subir archivos adjuntos',

            // Categories management
            'manager.helpdesk.backups.tickets.categories.index' => 'Ver categorías de tickets',
            'manager.helpdesk.backups.tickets.categories.show' => 'Ver detalles de categoría',
            'manager.helpdesk.backups.tickets.categories.create' => 'Crear categorías',
            'manager.helpdesk.backups.tickets.categories.store' => 'Guardar categorías',
            'manager.helpdesk.backups.tickets.categories.edit' => 'Editar categorías',
            'manager.helpdesk.backups.tickets.categories.update' => 'Actualizar categorías',
            'manager.helpdesk.backups.tickets.categories.delete' => 'Eliminar categorías',
            'manager.helpdesk.backups.tickets.categories.destroy' => 'Destruir categorías',

            // Groups management
            'manager.helpdesk.backups.tickets.groups.index' => 'Ver grupos de tickets',
            'manager.helpdesk.backups.tickets.groups.show' => 'Ver detalles de grupo',
            'manager.helpdesk.backups.tickets.groups.create' => 'Crear grupos',
            'manager.helpdesk.backups.tickets.groups.store' => 'Guardar grupos',
            'manager.helpdesk.backups.tickets.groups.edit' => 'Editar grupos',
            'manager.helpdesk.backups.tickets.groups.update' => 'Actualizar grupos',
            'manager.helpdesk.backups.tickets.groups.delete' => 'Eliminar grupos',
            'manager.helpdesk.backups.tickets.groups.destroy' => 'Destruir grupos',

            // Canned replies management
            'manager.helpdesk.backups.tickets.canned-replies.index' => 'Ver respuestas enlatadas',
            'manager.helpdesk.backups.tickets.canned-replies.show' => 'Ver detalles de respuesta',
            'manager.helpdesk.backups.tickets.canned-replies.create' => 'Crear respuestas',
            'manager.helpdesk.backups.tickets.canned-replies.store' => 'Guardar respuestas',
            'manager.helpdesk.backups.tickets.canned-replies.edit' => 'Editar respuestas',
            'manager.helpdesk.backups.tickets.canned-replies.update' => 'Actualizar respuestas',
            'manager.helpdesk.backups.tickets.canned-replies.delete' => 'Eliminar respuestas',
            'manager.helpdesk.backups.tickets.canned-replies.destroy' => 'Destruir respuestas',

            // SLA management
            'manager.helpdesk.backups.tickets.sla-policies.index' => 'Ver políticas SLA',
            'manager.helpdesk.backups.tickets.sla-policies.show' => 'Ver detalles de política SLA',
            'manager.helpdesk.backups.tickets.sla-policies.create' => 'Crear políticas SLA',
            'manager.helpdesk.backups.tickets.sla-policies.store' => 'Guardar políticas SLA',
            'manager.helpdesk.backups.tickets.sla-policies.edit' => 'Editar políticas SLA',
            'manager.helpdesk.backups.tickets.sla-policies.update' => 'Actualizar políticas SLA',
            'manager.helpdesk.backups.tickets.sla-policies.delete' => 'Eliminar políticas SLA',
            'manager.helpdesk.backups.tickets.sla-policies.destroy' => 'Destruir políticas SLA',

            // Status management
            'manager.helpdesk.backups.tickets.statuses.index' => 'Ver estados de tickets',
            'manager.helpdesk.backups.tickets.statuses.show' => 'Ver detalles de estado',
            'manager.helpdesk.backups.tickets.statuses.create' => 'Crear estados',
            'manager.helpdesk.backups.tickets.statuses.store' => 'Guardar estados',
            'manager.helpdesk.backups.tickets.statuses.edit' => 'Editar estados',
            'manager.helpdesk.backups.tickets.statuses.update' => 'Actualizar estados',
            'manager.helpdesk.backups.tickets.statuses.delete' => 'Eliminar estados',
            'manager.helpdesk.backups.tickets.statuses.destroy' => 'Destruir estados',

            // Views management
            'manager.helpdesk.backups.tickets.views.index' => 'Ver vistas guardadas',
            'manager.helpdesk.backups.tickets.views.show' => 'Ver detalles de vista',
            'manager.helpdesk.backups.tickets.views.create' => 'Crear vistas',
            'manager.helpdesk.backups.tickets.views.store' => 'Guardar vistas',
            'manager.helpdesk.backups.tickets.views.edit' => 'Editar vistas',
            'manager.helpdesk.backups.tickets.views.update' => 'Actualizar vistas',
            'manager.helpdesk.backups.tickets.views.delete' => 'Eliminar vistas',
            'manager.helpdesk.backups.tickets.views.destroy' => 'Destruir vistas',

            // Reports and analytics
            'manager.helpdesk.tickets.reports' => 'Ver reportes de tickets',
            'manager.helpdesk.tickets.reports.sla' => 'Ver reportes de SLA',
            'manager.helpdesk.tickets.reports.performance' => 'Ver reportes de rendimiento',
            'manager.helpdesk.tickets.export' => 'Exportar tickets',

            // Advanced permissions
            'manager.helpdesk.tickets.bulk-actions' => 'Acciones masivas en tickets',
            'manager.helpdesk.tickets.merge' => 'Combinar tickets',
            'manager.helpdesk.tickets.watchers.manage' => 'Gestionar observadores',
        ];
    }
}

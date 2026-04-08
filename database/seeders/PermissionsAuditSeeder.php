<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * PermissionsAuditSeeder
 *
 * Master seeder that consolidates ALL permissions from ALL modules into one place.
 * Creates default roles and assigns appropriate permissions.
 *
 * Run with: php artisan db:seed --class=PermissionsAuditSeeder
 *
 * ROLES defined here:
 *   super-settings  — Full access to everything (bypassed by Gate::before in Auth module)
 *   settings        — Full access except critical system operations
 *   manager         — Operational management access
 *   administrative  — Administrative processing (returns, payments)
 *   customer        — End-user, own data only
 *
 * Module-specific roles (created by their own seeders):
 *   attention-settings, attention-manager, attention-agent, attention-user
 *   blog-admin, blog-editor, blog-author
 *   cms-settings, cms-editor, cms-author, cms-viewer
 *   helpdesk-agent
 */
class PermissionsAuditSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Creating all permissions...');
        $this->createPermissions();

        $this->command->info('Creating base roles...');
        $this->createBaseRoles();

        $this->command->info('Assigning permissions to roles...');
        $this->assignPermissionsToRoles();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Done. All permissions and roles are up to date.');
    }

    // -------------------------------------------------------------------------
    // PERMISSION DEFINITIONS
    // -------------------------------------------------------------------------

    private function createPermissions(): void
    {
        foreach ($this->allPermissions() as $name => $description) {
            Permission::findOrCreate($name, 'web');
        }

        $this->command->line('  Created/verified '.count($this->allPermissions()).' permissions.');
    }

    /**
     * @return array<string, string>
     */
    private function allPermissions(): array
    {
        return array_merge(
            $this->dashboardPermissions(),
            $this->userPermissions(),
            $this->rolePermissions(),
            $this->attentionPermissions(),
            $this->analyticsPermissions(),
            $this->backupPermissions(),
            $this->blogPermissions(),
            $this->cachePermissions(),
            $this->cookiePermissions(),
            $this->databasePermissions(),
            $this->formsPermissions(),
            $this->helpdeskPermissions(),
            $this->mailerPermissions(),
            $this->mailrelayPermissions(),
            $this->mediaPermissions(),
            $this->notificationPermissions(),
            $this->pagePermissions(),
            $this->reviewsPermissions(),
            $this->seoPermissions(),
            $this->systemPermissions(),
            $this->templatePermissions(),
            $this->storagePermissions(),
        );
    }

    /** @return array<string, string> */
    private function dashboardPermissions(): array
    {
        return [
            'dashboard.view' => 'Ver dashboard',
            'dashboard.statistics' => 'Ver estadísticas del dashboard',
            'dashboard.reports' => 'Generar reportes del dashboard',
        ];
    }

    /** @return array<string, string> */
    private function userPermissions(): array
    {
        return [
            // Used by UserPolicy (view-users / create-users / edit-users / delete-users naming)
            'view-users' => 'Ver usuarios',
            'create-users' => 'Crear usuarios',
            'edit-users' => 'Editar usuarios',
            'delete-users' => 'Eliminar usuarios',
            'export-users' => 'Exportar usuarios',
            // Canonical naming (used in CompleteRolesAndPermissionsSeeder)
            'users.view' => 'Ver usuarios',
            'users.create' => 'Crear usuarios',
            'users.update' => 'Actualizar usuarios',
            'users.delete' => 'Eliminar usuarios',
            'users.export' => 'Exportar usuarios',
            'users.roles.assign' => 'Asignar roles a usuarios',
            'users.permissions.assign' => 'Asignar permisos a usuarios',
        ];
    }

    /** @return array<string, string> */
    private function rolePermissions(): array
    {
        return [
            'roles.view' => 'Ver roles',
            'roles.create' => 'Crear roles',
            'roles.edit' => 'Editar roles',
            'roles.delete' => 'Eliminar roles',
            'roles.show.permissions' => 'Ver permisos de un rol',
            'roles.update.permissions' => 'Actualizar permisos de un rol',
            'roles.show.users' => 'Ver usuarios de un rol',
            'roles.assign.users' => 'Asignar usuarios a un rol',
            'permissions.view' => 'Ver permisos',
            'permissions.create' => 'Crear permisos',
            'permissions.edit' => 'Editar permisos',
            'permissions.delete' => 'Eliminar permisos',
        ];
    }

    /** @return array<string, string> */
    private function attentionPermissions(): array
    {
        return [
            'attention.view' => 'Ver PQRSF asignados o del departamento',
            'attention.view-all' => 'Ver todos los PQRSF sin restricciones',
            'attention.create' => 'Crear nuevos PQRSF',
            'attention.update' => 'Actualizar PQRSF',
            'attention.delete' => 'Eliminar PQRSF',
            'attention.manage' => 'Gestionar completamente PQRSF',
            'attention.assign' => 'Asignar PQRSF a usuarios o departamentos',
            'attention.change-status' => 'Cambiar estado de PQRSF',
            'attention.resolve' => 'Resolver PQRSF',
            'attention.close' => 'Cerrar PQRSF',
            'attention.send-email' => 'Enviar emails relacionados con PQRSF',
            'attention.manage-notes' => 'Gestionar notas de PQRSF',
            'attention.view-history' => 'Ver historial completo de PQRSF',
            'attention.manage-departments' => 'Gestionar departamentos de atención',
            'attention.manage-types' => 'Gestionar tipos de PQRSF',
            'attention.manage-settings' => 'Gestionar configuración del módulo Attention',
            'attention.view-reports' => 'Ver reportes y estadísticas de Attention',
        ];
    }

    /** @return array<string, string> */
    private function analyticsPermissions(): array
    {
        return [
            'analytics.view' => 'Ver dashboard de Analytics',
            'analytics.settings.view' => 'Ver configuración de Analytics',
            'analytics.settings.update' => 'Actualizar configuración de Analytics',
            'analytics.schedules.view' => 'Ver reportes programados de Analytics',
            'analytics.schedules.manage' => 'Gestionar reportes programados de Analytics',
        ];
    }

    /** @return array<string, string> */
    private function backupPermissions(): array
    {
        return [
            'Backup.backups.index' => 'Ver backups',
            'Backup.backups.download' => 'Descargar backups',
            'Backup.backups.delete' => 'Eliminar backups',
            'Backup.schedules.index' => 'Ver programaciones de backup',
            'Backup.schedules.create' => 'Crear programaciones de backup',
            'Backup.schedules.update' => 'Actualizar programaciones de backup',
            'Backup.schedules.delete' => 'Eliminar programaciones de backup',
            // Legacy aliases from CompleteRolesAndPermissionsSeeder
            'backups.langs' => 'Gestionar idiomas del manager',
            'backups.langs.create' => 'Crear idiomas del manager',
            'backups.langs.store' => 'Almacenar idiomas del manager',
            'backups.langs.update' => 'Actualizar idiomas del manager',
            'backups.langs.edit' => 'Editar idiomas del manager',
            'backups.langs.view' => 'Ver idiomas del manager',
            'backups.langs.destroy' => 'Eliminar idiomas del manager',
            'backups.langs.categories' => 'Gestionar categorías de idiomas del manager',
        ];
    }

    /** @return array<string, string> */
    private function blogPermissions(): array
    {
        return [
            'blog.post.view' => 'Ver publicaciones propias del blog',
            'blog.post.view-all' => 'Ver todas las publicaciones del blog',
            'blog.post.create' => 'Crear publicaciones en el blog',
            'blog.post.update' => 'Editar publicaciones del blog',
            'blog.post.delete' => 'Eliminar publicaciones del blog',
            'blog.post.publish' => 'Publicar y despublicar entradas del blog',
            'blog.category.view' => 'Ver categorías del blog',
            'blog.category.create' => 'Crear categorías del blog',
            'blog.category.update' => 'Editar categorías del blog',
            'blog.category.delete' => 'Eliminar categorías del blog',
            'blog.tag.view' => 'Ver etiquetas del blog',
            'blog.tag.create' => 'Crear etiquetas del blog',
            'blog.tag.update' => 'Editar etiquetas del blog',
            'blog.tag.delete' => 'Eliminar etiquetas del blog',
            'blog.comment.view' => 'Ver comentarios del blog',
            'blog.comment.moderate' => 'Moderar comentarios del blog',
            'blog.comment.delete' => 'Eliminar comentarios del blog',
            'blog.settings' => 'Gestionar configuración del módulo Blog',
        ];
    }

    /** @return array<string, string> */
    private function cachePermissions(): array
    {
        return [
            'Cache.index' => 'Acceder al módulo de caché',
            'Cache.settings.index' => 'Ver configuración de caché',
            'Cache.settings.update' => 'Actualizar configuración de caché',
        ];
    }

    /** @return array<string, string> */
    private function cookiePermissions(): array
    {
        return [
            'Cookie.settings.index' => 'Ver configuración de cookies',
            'Cookie.settings.update' => 'Actualizar configuración de cookies',
        ];
    }

    /** @return array<string, string> */
    private function databasePermissions(): array
    {
        return [
            'database.backups.view' => 'Ver configuración de base de datos',
            'database.backups.update' => 'Actualizar configuración de base de datos',
            'database.backups.test_connection' => 'Probar conexión a base de datos',
            'database.cleanup.view' => 'Ver herramienta de limpieza de base de datos',
            'database.cleanup.truncate' => 'Truncar tablas de base de datos',
            'database.cleanup.get_table_count' => 'Obtener conteo de registros de tablas',
        ];
    }

    /** @return array<string, string> */
    private function formsPermissions(): array
    {
        return [
            'Forms.forms.index' => 'Ver formularios',
            'Forms.forms.create' => 'Crear formularios',
            'Forms.forms.edit' => 'Editar formularios',
            'Forms.forms.delete' => 'Eliminar formularios',
            'Forms.submissions.index' => 'Ver respuestas de formularios',
            'Forms.submissions.export' => 'Exportar respuestas de formularios',
            'Forms.submissions.delete' => 'Eliminar respuestas de formularios',
            'Forms.categories.manage' => 'Gestionar categorías de formularios',
            'Forms.analytics.index' => 'Ver analíticas de formularios',
            'Forms.settings.manage' => 'Gestionar configuración de formularios',
        ];
    }

    /** @return array<string, string> */
    private function helpdeskPermissions(): array
    {
        return [
            // Legacy flat permissions (PermissionsSeeder)
            'manage_helpdesk_settings' => 'Gestionar configuración del Helpdesk',
            'access_helpdesk' => 'Acceder al Helpdesk',
            'create_tickets' => 'Crear tickets',
            'view_all_tickets' => 'Ver todos los tickets',
            'view_assigned_tickets' => 'Ver tickets asignados',
            'edit_tickets' => 'Editar tickets',
            'delete_tickets' => 'Eliminar tickets',
            'assign_tickets' => 'Asignar tickets',
            'close_tickets' => 'Cerrar tickets',
            'reopen_tickets' => 'Reabrir tickets',
            'merge_tickets' => 'Fusionar tickets',
            'view_internal_notes' => 'Ver notas internas',
            'create_internal_notes' => 'Crear notas internas',
            'manage_canned_replies' => 'Gestionar respuestas predefinidas',
            'view_reports' => 'Ver reportes',
            'export_reports' => 'Exportar reportes',
            // Granular namespaced permissions (TicketPermissionsSeeder)
            'manager.helpdesk.tickets.index' => 'Ver lista de tickets',
            'manager.helpdesk.tickets.show' => 'Ver detalles de ticket',
            'manager.helpdesk.tickets.show.all' => 'Ver todos los tickets',
            'manager.helpdesk.tickets.show.assigned' => 'Ver tickets asignados',
            'manager.helpdesk.tickets.show.group' => 'Ver tickets de mi grupo',
            'manager.helpdesk.tickets.create' => 'Crear tickets (namespaced)',
            'manager.helpdesk.tickets.store' => 'Guardar tickets',
            'manager.helpdesk.tickets.edit' => 'Editar tickets (namespaced)',
            'manager.helpdesk.tickets.update' => 'Actualizar tickets',
            'manager.helpdesk.tickets.update.all' => 'Actualizar todos los tickets',
            'manager.helpdesk.tickets.update.assigned' => 'Actualizar tickets asignados',
            'manager.helpdesk.tickets.update.group' => 'Actualizar tickets del grupo',
            'manager.helpdesk.tickets.delete' => 'Eliminar tickets (namespaced)',
            'manager.helpdesk.tickets.destroy' => 'Destruir tickets',
            'manager.helpdesk.tickets.restore' => 'Restaurar tickets eliminados',
            'manager.helpdesk.tickets.force-delete' => 'Eliminar tickets permanentemente',
            'manager.helpdesk.tickets.close' => 'Cerrar tickets',
            'manager.helpdesk.tickets.close.unresolved' => 'Cerrar tickets sin resolver',
            'manager.helpdesk.tickets.resolve' => 'Marcar tickets como resueltos',
            'manager.helpdesk.tickets.reopen' => 'Reabrir tickets cerrados',
            'manager.helpdesk.tickets.archive' => 'Archivar tickets',
            'manager.helpdesk.tickets.assign' => 'Asignar tickets',
            'manager.helpdesk.tickets.priority.change' => 'Cambiar prioridad de tickets',
            'manager.helpdesk.tickets.messages.store' => 'Enviar mensajes en tickets',
            'manager.helpdesk.tickets.messages.internal' => 'Enviar notas internas',
            'manager.helpdesk.tickets.attachments.upload' => 'Subir archivos adjuntos a tickets',
            'manager.helpdesk.backups.tickets.categories.index' => 'Ver categorías de tickets',
            'manager.helpdesk.backups.tickets.categories.show' => 'Ver detalles de categoría',
            'manager.helpdesk.backups.tickets.categories.create' => 'Crear categorías de tickets',
            'manager.helpdesk.backups.tickets.categories.store' => 'Guardar categorías de tickets',
            'manager.helpdesk.backups.tickets.categories.edit' => 'Editar categorías de tickets',
            'manager.helpdesk.backups.tickets.categories.update' => 'Actualizar categorías de tickets',
            'manager.helpdesk.backups.tickets.categories.delete' => 'Eliminar categorías de tickets',
            'manager.helpdesk.backups.tickets.categories.destroy' => 'Destruir categorías de tickets',
            'manager.helpdesk.backups.tickets.groups.index' => 'Ver grupos de tickets',
            'manager.helpdesk.backups.tickets.groups.show' => 'Ver detalles de grupo',
            'manager.helpdesk.backups.tickets.groups.create' => 'Crear grupos de tickets',
            'manager.helpdesk.backups.tickets.groups.store' => 'Guardar grupos de tickets',
            'manager.helpdesk.backups.tickets.groups.edit' => 'Editar grupos de tickets',
            'manager.helpdesk.backups.tickets.groups.update' => 'Actualizar grupos de tickets',
            'manager.helpdesk.backups.tickets.groups.delete' => 'Eliminar grupos de tickets',
            'manager.helpdesk.backups.tickets.groups.destroy' => 'Destruir grupos de tickets',
            'manager.helpdesk.backups.tickets.canned-replies.index' => 'Ver respuestas enlatadas',
            'manager.helpdesk.backups.tickets.canned-replies.show' => 'Ver detalles de respuesta enlatada',
            'manager.helpdesk.backups.tickets.canned-replies.create' => 'Crear respuestas enlatadas',
            'manager.helpdesk.backups.tickets.canned-replies.store' => 'Guardar respuestas enlatadas',
            'manager.helpdesk.backups.tickets.canned-replies.edit' => 'Editar respuestas enlatadas',
            'manager.helpdesk.backups.tickets.canned-replies.update' => 'Actualizar respuestas enlatadas',
            'manager.helpdesk.backups.tickets.canned-replies.delete' => 'Eliminar respuestas enlatadas',
            'manager.helpdesk.backups.tickets.canned-replies.destroy' => 'Destruir respuestas enlatadas',
            'manager.helpdesk.backups.tickets.sla-policies.index' => 'Ver políticas SLA',
            'manager.helpdesk.backups.tickets.sla-policies.show' => 'Ver detalles de política SLA',
            'manager.helpdesk.backups.tickets.sla-policies.create' => 'Crear políticas SLA',
            'manager.helpdesk.backups.tickets.sla-policies.store' => 'Guardar políticas SLA',
            'manager.helpdesk.backups.tickets.sla-policies.edit' => 'Editar políticas SLA',
            'manager.helpdesk.backups.tickets.sla-policies.update' => 'Actualizar políticas SLA',
            'manager.helpdesk.backups.tickets.sla-policies.delete' => 'Eliminar políticas SLA',
            'manager.helpdesk.backups.tickets.sla-policies.destroy' => 'Destruir políticas SLA',
            'manager.helpdesk.backups.tickets.statuses.index' => 'Ver estados de tickets',
            'manager.helpdesk.backups.tickets.statuses.show' => 'Ver detalles de estado',
            'manager.helpdesk.backups.tickets.statuses.create' => 'Crear estados de tickets',
            'manager.helpdesk.backups.tickets.statuses.store' => 'Guardar estados de tickets',
            'manager.helpdesk.backups.tickets.statuses.edit' => 'Editar estados de tickets',
            'manager.helpdesk.backups.tickets.statuses.update' => 'Actualizar estados de tickets',
            'manager.helpdesk.backups.tickets.statuses.delete' => 'Eliminar estados de tickets',
            'manager.helpdesk.backups.tickets.statuses.destroy' => 'Destruir estados de tickets',
            'manager.helpdesk.backups.tickets.views.index' => 'Ver vistas guardadas',
            'manager.helpdesk.backups.tickets.views.show' => 'Ver detalles de vista',
            'manager.helpdesk.backups.tickets.views.create' => 'Crear vistas de tickets',
            'manager.helpdesk.backups.tickets.views.store' => 'Guardar vistas de tickets',
            'manager.helpdesk.backups.tickets.views.edit' => 'Editar vistas de tickets',
            'manager.helpdesk.backups.tickets.views.update' => 'Actualizar vistas de tickets',
            'manager.helpdesk.backups.tickets.views.delete' => 'Eliminar vistas de tickets',
            'manager.helpdesk.backups.tickets.views.destroy' => 'Destruir vistas de tickets',
            'manager.helpdesk.tickets.reports' => 'Ver reportes de tickets',
            'manager.helpdesk.tickets.reports.sla' => 'Ver reportes de SLA',
            'manager.helpdesk.tickets.reports.performance' => 'Ver reportes de rendimiento',
            'manager.helpdesk.tickets.export' => 'Exportar tickets',
            'manager.helpdesk.tickets.bulk-actions' => 'Acciones masivas en tickets',
            'manager.helpdesk.tickets.merge' => 'Combinar tickets',
            'manager.helpdesk.tickets.watchers.manage' => 'Gestionar observadores de tickets',
        ];
    }

    /** @return array<string, string> */
    private function mailerPermissions(): array
    {
        return [
            'mailer.templates.view' => 'Ver plantillas de correo',
            'mailer.templates.create' => 'Crear plantillas de correo',
            'mailer.templates.update' => 'Actualizar plantillas de correo',
            'mailer.templates.delete' => 'Eliminar plantillas de correo',
            'mailer.templates.preview' => 'Previsualizar plantillas de correo',
            'mailer.templates.manage' => 'Gestionar plantillas de correo',
            'mailer.components.view' => 'Ver componentes de correo',
            'mailer.components.create' => 'Crear componentes de correo',
            'mailer.components.update' => 'Actualizar componentes de correo',
            'mailer.components.delete' => 'Eliminar componentes de correo',
            'mailer.components.preview' => 'Previsualizar componentes de correo',
            'mailer.components.manage' => 'Gestionar componentes de correo',
            'mailer.variables.view' => 'Ver variables de correo',
            'mailer.variables.create' => 'Crear variables de correo',
            'mailer.variables.update' => 'Actualizar variables de correo',
            'mailer.variables.delete' => 'Eliminar variables de correo',
            'mailer.variables.manage' => 'Gestionar variables de correo',
            'mailer.endpoints.view' => 'Ver endpoints de correo',
            'mailer.endpoints.create' => 'Crear endpoints de correo',
            'mailer.endpoints.update' => 'Actualizar endpoints de correo',
            'mailer.endpoints.delete' => 'Eliminar endpoints de correo',
            'mailer.endpoints.logs' => 'Ver logs de endpoints de correo',
            'mailer.endpoints.manage' => 'Gestionar endpoints de correo',
            'mailer.endpoints.regenerate-token' => 'Regenerar token de API de correo',
            'mailer.manage' => 'Gestionar módulo Mailer',
        ];
    }

    /** @return array<string, string> */
    private function mailrelayPermissions(): array
    {
        return [
            'mailrelay.access' => 'Acceder al módulo Mailrelay',
            'mailrelay.dashboard.view' => 'Ver dashboard de Mailrelay',
            'mailrelay.campaigns.view' => 'Ver campañas de Mailrelay',
            'mailrelay.campaigns.create' => 'Crear campañas de Mailrelay',
            'mailrelay.campaigns.update' => 'Actualizar campañas de Mailrelay',
            'mailrelay.campaigns.delete' => 'Eliminar campañas de Mailrelay',
            'mailrelay.campaigns.send' => 'Enviar campañas de Mailrelay',
            'mailrelay.campaigns.duplicate' => 'Duplicar campañas de Mailrelay',
            'mailrelay.campaigns.analytics' => 'Ver analíticas de campañas',
            'mailrelay.subscribers.view' => 'Ver suscriptores',
            'mailrelay.subscribers.create' => 'Crear suscriptores',
            'mailrelay.subscribers.update' => 'Actualizar suscriptores',
            'mailrelay.subscribers.delete' => 'Eliminar suscriptores',
            'mailrelay.subscribers.manage' => 'Gestionar suscriptores',
            'mailrelay.subscribers.import' => 'Importar suscriptores',
            'mailrelay.subscribers.export' => 'Exportar suscriptores',
            'mailrelay.imports.create' => 'Crear importaciones',
            'mailrelay.imports.view' => 'Ver importaciones',
            'mailrelay.imports.process' => 'Procesar importaciones',
            'mailrelay.imports.delete' => 'Eliminar importaciones',
            'mailrelay.lists.view' => 'Ver listas de Mailrelay',
            'mailrelay.lists.create' => 'Crear listas de Mailrelay',
            'mailrelay.lists.update' => 'Actualizar listas de Mailrelay',
            'mailrelay.lists.delete' => 'Eliminar listas de Mailrelay',
            'mailrelay.validation.test' => 'Probar validación de Mailrelay',
            'mailrelay.validation.validate' => 'Validar en Mailrelay',
            'mailrelay.settings.general' => 'Ver ajustes generales de Mailrelay',
            'mailrelay.settings.api' => 'Gestionar API de Mailrelay',
            'mailrelay.settings.templates' => 'Gestionar plantillas de Mailrelay',
            'mailrelay.settings.groups' => 'Gestionar grupos de Mailrelay',
            'mailrelay.settings.custom-fields' => 'Gestionar campos personalizados de Mailrelay',
            'mailrelay.settings.automations' => 'Gestionar automatizaciones de Mailrelay',
            'mailrelay.settings.webhooks' => 'Gestionar webhooks de Mailrelay',
            'mailrelay.settings.permissions' => 'Gestionar permisos de Mailrelay',
            'mailrelay.settings.manage' => 'Gestionar configuración de Mailrelay',
        ];
    }

    /** @return array<string, string> */
    private function mediaPermissions(): array
    {
        return [
            'media.view' => 'Ver archivos multimedia',
            'media.upload' => 'Subir archivos multimedia',
            'media.update' => 'Actualizar archivos multimedia',
            'media.delete' => 'Eliminar archivos multimedia',
            'media.folders.view' => 'Ver carpetas de media',
            'media.folders.create' => 'Crear carpetas de media',
            'media.folders.update' => 'Actualizar carpetas de media',
            'media.folders.delete' => 'Eliminar carpetas de media',
        ];
    }

    /** @return array<string, string> */
    private function notificationPermissions(): array
    {
        return [
            'notifications.view' => 'Ver notificaciones',
            'notifications.manage' => 'Gestionar notificaciones propias',
        ];
    }

    /** @return array<string, string> */
    private function pagePermissions(): array
    {
        return [
            'page.view' => 'Ver páginas',
            'page.view-all' => 'Ver todas las páginas',
            'page.create' => 'Crear páginas',
            'page.update' => 'Editar páginas',
            'page.delete' => 'Eliminar páginas',
            'page.publish' => 'Publicar páginas',
            'page.manage' => 'Gestionar páginas',
            'page.approve' => 'Aprobar o rechazar páginas en revisión',
            'page.manage-categories' => 'Gestionar categorías de páginas',
            'page.manage-webhooks' => 'Gestionar webhooks de páginas',
            'page.bulk-action' => 'Ejecutar acciones masivas en páginas',
            'page.import' => 'Importar páginas',
            'page.export' => 'Exportar páginas',
            'page.view-analytics' => 'Ver analíticas de páginas',
            'page.manage-performance' => 'Gestionar análisis de rendimiento PageSpeed',
            'menu.view' => 'Ver menús',
            'menu.create' => 'Crear menús',
            'menu.update' => 'Editar menús',
            'menu.delete' => 'Eliminar menús',
            'menu.manage' => 'Gestionar menús',
            'menu.manage-items' => 'Gestionar elementos de menú',
            'shortcode.use' => 'Usar shortcodes',
            'shortcode.manage' => 'Gestionar shortcodes',
            'shortcode.create-custom' => 'Crear shortcodes personalizados',
            'sitemap.view' => 'Ver sitemap',
            'sitemap.generate' => 'Generar sitemap',
            'sitemap.manage' => 'Gestionar sitemap',
        ];
    }

    /** @return array<string, string> */
    private function reviewsPermissions(): array
    {
        return [
            'reviews.connections.view' => 'Ver conexiones de Google Business Profile',
            'reviews.connections.create' => 'Conectar cuentas de Google Business Profile',
            'reviews.connections.delete' => 'Eliminar conexiones de Google Business Profile',
            'reviews.reviews.view' => 'Ver reseñas',
            'reviews.reviews.export' => 'Exportar reseñas',
            'reviews.moderate' => 'Moderar reseñas (mostrar/ocultar)',
            'reviews.moderate.featured' => 'Marcar reseñas como destacadas',
            'reviews.replies.create' => 'Crear respuestas a reseñas',
            'reviews.replies.approve' => 'Aprobar respuestas a reseñas',
            'reviews.replies.publish' => 'Publicar respuestas en Google',
            'reviews.replies.delete' => 'Eliminar respuestas a reseñas',
            'reviews.templates.view' => 'Ver plantillas de respuesta',
            'reviews.templates.create' => 'Crear plantillas de respuesta',
            'reviews.templates.update' => 'Actualizar plantillas de respuesta',
            'reviews.templates.delete' => 'Eliminar plantillas de respuesta',
            'reviews.settings.manage' => 'Gestionar configuración del módulo Reviews',
            // Missing permissions used by GeneratedReportPolicy
            'view_reports' => 'Ver reportes generados',
            'view_all_reports' => 'Ver todos los reportes generados',
            'create_reports' => 'Crear reportes generados',
            'delete_all_reports' => 'Eliminar todos los reportes generados',
        ];
    }

    /** @return array<string, string> */
    private function seoPermissions(): array
    {
        return [
            'Seo.metas.index' => 'Ver meta SEO',
            'Seo.metas.create' => 'Crear meta SEO',
            'Seo.metas.update' => 'Actualizar meta SEO',
            'Seo.metas.delete' => 'Eliminar meta SEO',
            'Seo.redirects.index' => 'Ver redirecciones SEO',
            'Seo.redirects.create' => 'Crear redirecciones SEO',
            'Seo.redirects.update' => 'Actualizar redirecciones SEO',
            'Seo.redirects.delete' => 'Eliminar redirecciones SEO',
            'Seo.robots.index' => 'Ver robots.txt',
            'Seo.robots.update' => 'Editar robots.txt',
            'Seo.static-urls.index' => 'Ver URLs estáticas del sitemap',
            'Seo.static-urls.create' => 'Crear URLs estáticas del sitemap',
            'Seo.static-urls.update' => 'Actualizar URLs estáticas del sitemap',
            'Seo.static-urls.delete' => 'Eliminar URLs estáticas del sitemap',
            'Seo.dashboard.view' => 'Ver dashboard SEO',
            'Seo.report.view' => 'Ver reporte SEO',
            'Seo.orphans.view' => 'Ver contenido sin SEO',
            'Seo.orphans.generate' => 'Generar listado de contenido sin SEO',
            'Seo.404-logs.view' => 'Ver errores 404',
            'Seo.404-logs.delete' => 'Eliminar errores 404',
            'Seo.templates.index' => 'Ver plantillas SEO',
            'Seo.templates.create' => 'Crear plantillas SEO',
            'Seo.templates.update' => 'Actualizar plantillas SEO',
            'Seo.templates.delete' => 'Eliminar plantillas SEO',
            'Seo.audit.history' => 'Ver historial de auditorías SEO',
            // Aliases used by CmsPermissionsSeeder (page module cross-reference)
            'seo.view' => 'Ver configuración SEO',
            'seo.manage' => 'Gestionar SEO',
            'seo.edit-meta' => 'Editar meta tags',
            'seo.manage-redirects' => 'Gestionar redirecciones SEO',
        ];
    }

    /** @return array<string, string> */
    private function systemPermissions(): array
    {
        return [
            'system.backups.manage' => 'Gestionar configuración del sistema',
            'system.maintenance' => 'Modo mantenimiento del sistema',
            'system.logs.view' => 'Ver logs del sistema',
            'system.api.manage' => 'Gestionar API tokens del sistema',
            'system.emails.manage' => 'Configurar emails del sistema',
            'system.hours.manage' => 'Configurar horarios del sistema',
        ];
    }

    /** @return array<string, string> */
    private function templatePermissions(): array
    {
        return [
            'template.view' => 'Ver plantillas del sistema',
            'template.create' => 'Crear plantillas del sistema',
            'template.update' => 'Actualizar plantillas del sistema',
            'template.delete' => 'Eliminar plantillas del sistema',
            'template.manage' => 'Gestionar plantillas del sistema',
        ];
    }

    /** @return array<string, string> */
    private function storagePermissions(): array
    {
        return [
            'storage.view' => 'Ver configuración de almacenamiento',
            'storage.create' => 'Crear discos de almacenamiento',
            'storage.update' => 'Actualizar discos de almacenamiento',
            'storage.delete' => 'Eliminar discos de almacenamiento',
        ];
    }

    // -------------------------------------------------------------------------
    // ROLE CREATION
    // -------------------------------------------------------------------------

    private function createBaseRoles(): void
    {
        $roles = [
            'super-settings' => 'Super administrador con acceso completo',
            'settings' => 'Administrador con acceso completo excepto operaciones críticas',
            'manager' => 'Gestor con acceso operacional general',
            'administrative' => 'Administrativo con acceso a procesos administrativos',
            'customer' => 'Usuario final con acceso a sus propios datos',
        ];

        foreach ($roles as $name => $description) {
            Role::findOrCreate($name, 'web');
        }

        $this->command->line('  Created/verified '.count($roles).' base roles.');
    }

    // -------------------------------------------------------------------------
    // PERMISSION ASSIGNMENTS
    // -------------------------------------------------------------------------

    private function assignPermissionsToRoles(): void
    {
        // super-settings is bypassed globally by Gate::before in AuthServiceProvider.
        // We still assign all permissions so hasPermissionTo() checks work directly.
        $superSettings = Role::findOrCreate('super-settings', 'web');
        $superSettings->syncPermissions(Permission::all());
        $this->command->line('  super-settings: all permissions assigned.');

        $this->assignSettingsRole();
        $this->assignManagerRole();
        $this->assignAdministrativeRole();
        $this->assignCustomerRole();
    }

    private function assignSettingsRole(): void
    {
        $role = Role::findOrCreate('settings', 'web');
        $role->givePermissionTo(
            Permission::all()->reject(fn ($p) => in_array($p->name, [
                'system.maintenance',
                'system.api.manage',
                'database.cleanup.truncate',
                'Backup.backups.delete',
                'Backup.schedules.delete',
            ]))
        );
        $this->command->line('  settings: permissions assigned (excluding critical system ops).');
    }

    private function assignManagerRole(): void
    {
        $role = Role::findOrCreate('manager', 'web');
        $role->givePermissionTo([
            // Dashboard
            'dashboard.view',
            'dashboard.statistics',
            'dashboard.reports',
            // Users
            'users.view',
            'view-users',
            // Roles (read-only)
            'roles.view',
            'roles.show.permissions',
            'roles.show.users',
            'permissions.view',
            // Attention
            'attention.view-all',
            'attention.create',
            'attention.update',
            'attention.manage',
            'attention.assign',
            'attention.change-status',
            'attention.resolve',
            'attention.close',
            'attention.send-email',
            'attention.manage-notes',
            'attention.view-history',
            'attention.view-reports',
            // Analytics
            'analytics.view',
            'analytics.settings.view',
            'analytics.schedules.view',
            // Blog (view/create/edit only)
            'blog.post.view',
            'blog.post.view-all',
            'blog.post.create',
            'blog.post.update',
            'blog.post.publish',
            'blog.category.view',
            'blog.category.create',
            'blog.category.update',
            'blog.tag.view',
            'blog.tag.create',
            'blog.tag.update',
            'blog.comment.view',
            'blog.comment.moderate',
            // Forms
            'Forms.forms.index',
            'Forms.forms.create',
            'Forms.forms.edit',
            'Forms.submissions.index',
            'Forms.submissions.export',
            'Forms.categories.manage',
            'Forms.analytics.index',
            // Helpdesk
            'access_helpdesk',
            'view_all_tickets',
            'view_reports',
            'export_reports',
            'manage_canned_replies',
            'close_tickets',
            'reopen_tickets',
            'merge_tickets',
            'assign_tickets',
            'edit_tickets',
            'manager.helpdesk.tickets.index',
            'manager.helpdesk.tickets.show',
            'manager.helpdesk.tickets.show.all',
            'manager.helpdesk.tickets.create',
            'manager.helpdesk.tickets.store',
            'manager.helpdesk.tickets.edit',
            'manager.helpdesk.tickets.update',
            'manager.helpdesk.tickets.close',
            'manager.helpdesk.tickets.resolve',
            'manager.helpdesk.tickets.reopen',
            'manager.helpdesk.tickets.assign',
            'manager.helpdesk.tickets.priority.change',
            'manager.helpdesk.tickets.messages.store',
            'manager.helpdesk.tickets.messages.internal',
            'manager.helpdesk.tickets.attachments.upload',
            'manager.helpdesk.tickets.reports',
            'manager.helpdesk.tickets.export',
            'manager.helpdesk.tickets.bulk-actions',
            'manager.helpdesk.tickets.merge',
            'manage_helpdesk_settings',
            // Mailer
            'mailer.templates.view',
            'mailer.templates.create',
            'mailer.templates.update',
            'mailer.templates.preview',
            'mailer.components.view',
            'mailer.components.create',
            'mailer.components.update',
            'mailer.components.preview',
            'mailer.variables.view',
            'mailer.variables.create',
            'mailer.variables.update',
            'mailer.endpoints.view',
            'mailer.endpoints.create',
            'mailer.endpoints.update',
            'mailer.endpoints.logs',
            // Mailrelay
            'mailrelay.access',
            'mailrelay.dashboard.view',
            'mailrelay.campaigns.view',
            'mailrelay.campaigns.create',
            'mailrelay.campaigns.update',
            'mailrelay.campaigns.send',
            'mailrelay.campaigns.duplicate',
            'mailrelay.campaigns.analytics',
            'mailrelay.subscribers.view',
            'mailrelay.subscribers.create',
            'mailrelay.subscribers.update',
            'mailrelay.subscribers.import',
            'mailrelay.subscribers.export',
            'mailrelay.imports.create',
            'mailrelay.imports.view',
            'mailrelay.imports.process',
            'mailrelay.lists.view',
            'mailrelay.lists.create',
            'mailrelay.lists.update',
            'mailrelay.validation.test',
            'mailrelay.validation.validate',
            'mailrelay.settings.templates',
            // Media
            'media.view',
            'media.upload',
            'media.update',
            'media.delete',
            'media.folders.view',
            'media.folders.create',
            'media.folders.update',
            // Page / CMS
            'page.view',
            'page.view-all',
            'page.create',
            'page.update',
            'page.publish',
            'page.bulk-action',
            'page.export',
            'page.view-analytics',
            'menu.view',
            'menu.update',
            'menu.manage-items',
            'shortcode.use',
            'sitemap.view',
            // Reviews
            'reviews.connections.view',
            'reviews.reviews.view',
            'reviews.reviews.export',
            'reviews.moderate',
            'reviews.moderate.featured',
            'reviews.replies.create',
            'reviews.templates.view',
            'reviews.templates.create',
            'reviews.templates.update',
            'view_reports',
            // SEO
            'seo.view',
            'seo.edit-meta',
            'Seo.dashboard.view',
            'Seo.metas.index',
            'Seo.metas.create',
            'Seo.metas.update',
            'Seo.redirects.index',
            'Seo.redirects.create',
            'Seo.redirects.update',
            'Seo.robots.index',
            'Seo.static-urls.index',
            'Seo.report.view',
            'Seo.orphans.view',
            'Seo.404-logs.view',
            'Seo.templates.index',
            'Seo.audit.history',
            // Database (view only)
            'database.backups.view',
            'database.cleanup.view',
            'database.cleanup.get_table_count',
            // Notifications
            'notifications.view',
            'notifications.manage',
            // Template
            'template.view',
        ]);
        $this->command->line('  manager: permissions assigned.');
    }

    private function assignAdministrativeRole(): void
    {
        $role = Role::findOrCreate('administrative', 'web');
        $role->givePermissionTo([
            'dashboard.view',
            // Notifications
            'notifications.view',
            'notifications.manage',
            // Mailer (view only)
            'mailer.templates.view',
            'mailer.templates.preview',
            'mailer.components.view',
            'mailer.components.preview',
            'mailer.variables.view',
            'mailer.endpoints.view',
            'mailer.endpoints.logs',
            // Mailrelay (view only)
            'mailrelay.access',
            'mailrelay.campaigns.view',
            'mailrelay.subscribers.view',
            'mailrelay.lists.view',
        ]);
        $this->command->line('  administrative: permissions assigned.');
    }

    private function assignCustomerRole(): void
    {
        $role = Role::findOrCreate('customer', 'web');
        $role->givePermissionTo([
            // Only own data
            'notifications.view',
            'notifications.manage',
            'media.view',
            'media.upload',
            'media.update',
        ]);
        $this->command->line('  customer: permissions assigned.');
    }
}

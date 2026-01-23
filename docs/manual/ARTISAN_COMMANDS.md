# Documentación de Comandos Artisan

> 📝 Generado automáticamente el 2025-12-15 03:26:00

> Este documento se actualiza automáticamente ejecutando: `php artisan docs:generate-commands`

## 📑 Tabla de Contenidos

- [Comandos Personalizados](#comandos-personalizados) (38)
- [Comandos de Laravel](#comandos-de-laravel) (0)
- [Guía de Uso](#guía-de-uso)

## Comandos Personalizados

Estos son los comandos específicos desarrollados para este proyecto.

### `app:create-sample-documents`

**Descripción:** Create sample documents from PrestaShop with test data and optionally send initial request emails

**Archivo:** `CreateSampleDocumentsFromPrestashop.php`

**Comando completo:** `php artisan app:create-sample-documents {--count=3 : Number of sample documents to create} {--send-emails : Send initial request emails}`

**Clase:** `App\Console\Commands\CreateSampleDocumentsFromPrestashop`

---

### `app:fix-media-permissions`

**Descripción:** Fix permissions for all media files to be accessible via web server

**Archivo:** `FixMediaPermissions.php`

**Comando completo:** `php artisan app:fix-media-permissions {--dry-run : Show what would be changed without making changes}`

**Clase:** `App\Console\Commands\FixMediaPermissions`

---

### `app:run-scheduled-backups`

**Descripción:** Run scheduled backups that are due

**Archivo:** `RunScheduledBackups.php`

**Comando completo:** `php artisan app:run-scheduled-backups`

**Clase:** `App\Console\Commands\RunScheduledBackups`

---

### `campaign:test`

**Descripción:** Command description

**Archivo:** `TestCampaign.php`

**Comando completo:** `php artisan campaign:test`

**Clase:** `App\Console\Commands\TestCampaign`

---

### `components:process
`

**Descripción:** Procesar componentes: stock, optimizaciones, reorden

**Archivo:** `ProcessComponents.php`

**Comando completo:** `php artisan components:process
                           {--check-stock : Verificar niveles de stock}
                           {--optimize : Optimizar asignaciones}
                           {--reorder : Generar órdenes de reposición}`

**Clase:** `App\Console\Commands\ProcessComponents`

---

### `docs:generate-commands`

**Descripción:** Generar y actualizar documentación de todos los comandos artisan disponibles

**Archivo:** `GenerateCommandsDocumentation.php`

**Comando completo:** `php artisan docs:generate-commands {--output=manual/ARTISAN_COMMANDS.md : Output file path}`

**Clase:** `App\Console\Commands\GenerateCommandsDocumentation`

---

### `documents:send-reminders`

**Descripción:** Detecta pedidos pagados en Prestashop y envía recordatorios de documentación pendientes.

**Archivo:** `SendDocumentUploadReminders.php`

**Comando completo:** `php artisan documents:send-reminders {--force : Reenviar aunque ya se haya enviado el recordatorio}`

**Clase:** `App\Console\Commands\SendDocumentUploadReminders`

---

### `documents:sync-fields`

**Descripción:** Valida y genera required_documents y uploaded_documents para todos los documentos

**Archivo:** `SyncDocumentFields.php`

**Comando completo:** `php artisan documents:sync-fields {--uid= : Sync specific document by UID} {--type= : Sync documents of specific type} {--force : Force resync even if fields already exist}`

**Clase:** `App\Console\Commands\SyncDocumentFields`

---

### `erp:check
`

**Descripción:** Verify connection to the ERP system

**Archivo:** `ErpCheckCommand.php`

**Comando completo:** `php artisan erp:check
                            {--update-status : Update the connection status in database}`

**Clase:** `App\Console\Commands\ErpCheckCommand`

---

### `geoip:check`

**Descripción:** Check the current GeoIp service

**Archivo:** `GeoIpCheck.php`

**Comando completo:** `php artisan geoip:check`

**Clase:** `App\Console\Commands\GeoIpCheck`

---

### `handler:run`

**Descripción:** Command description

**Archivo:** `RunHandler.php`

**Comando completo:** `php artisan handler:run`

**Clase:** `App\Console\Commands\RunHandler`

---

### `logs:cleanup`

**Descripción:** Clean up old logs from database and file system

**Archivo:** `CleanupOldLogs.php`

**Comando completo:** `php artisan logs:cleanup {--days=30 : Number of days to keep logs}`

**Clase:** `App\Console\Commands\CleanupOldLogs`

---

### `maintenance:configure`

**Descripción:** Configura las rutas de Composer y PHP para el mantenimiento del sistema

**Archivo:** `ConfigureMaintenanceTools.php`

**Comando completo:** `php artisan maintenance:configure {--composer= : Ruta al ejecutable de Composer} {--php= : Ruta al ejecutable de PHP}`

**Clase:** `App\Console\Commands\ConfigureMaintenanceTools`

---

### `migrate:ticket-categories-to-helpdesk`

**Descripción:** Migrate ticket categories from old system to new Helpdesk structure

**Archivo:** `MigrateTicketCategoriesToHelpdesk.php`

**Comando completo:** `php artisan migrate:ticket-categories-to-helpdesk`

**Clase:** `App\Console\Commands\MigrateTicketCategoriesToHelpdesk`

---

### `migrate:ticket-status-to-helpdesk`

**Descripción:** Migrate ticket status from old system to new Helpdesk structure

**Archivo:** `MigrateTicketStatusToHelpdesk.php`

**Comando completo:** `php artisan migrate:ticket-status-to-helpdesk`

**Clase:** `App\Console\Commands\MigrateTicketStatusToHelpdesk`

---

### `notifications:clean`

**Descripción:** Limpiar notificaciones antiguas de la base de datos

**Archivo:** `CleanOldNotifications.php`

**Comando completo:** `php artisan notifications:clean {--days=30 : Días de antigüedad para eliminar notificaciones}`

**Clase:** `App\Console\Commands\CleanOldNotifications`

---

### `permissions:assign
`

**Descripción:** Assign a permission to a user or role

**Archivo:** `AssignPermissionCommand.php`

**Comando completo:** `php artisan permissions:assign
                            {target_id : User ID or Role ID}
                            {permission : Permission name}
                            {--role : Assign to role instead of user}`

**Clase:** `App\Console\Commands\AssignPermissionCommand`

---

### `permissions:create`

**Descripción:** Create permissions based on synced routes and optionally assign to roles

**Archivo:** `CreatePermissionsCommand.php`

**Comando completo:** `php artisan permissions:create {--assign : Assign permissions to roles}`

**Clase:** `App\Console\Commands\CreatePermissionsCommand`

---

### `permissions:list`

**Descripción:** List all permissions and their role assignments

**Archivo:** `ListPermissionsCommand.php`

**Comando completo:** `php artisan permissions:list {--role= : Filter by specific role} {--user= : Filter permissions for specific user}`

**Clase:** `App\Console\Commands\ListPermissionsCommand`

---

### `returns:audit-rules`

**Descripción:** Auditar reglas de devolución para detectar conflictos y problemas

**Archivo:** `AuditReturnRules.php`

**Comando completo:** `php artisan returns:audit-rules {--fix : Reparar conflictos automáticamente}`

**Clase:** `App\Console\Commands\AuditReturnRules`

---

### `returns:cleanup-communications
`

**Descripción:** Limpiar comunicaciones antiguas de devoluciones

**Archivo:** `CleanupOldCommunications.php`

**Comando completo:** `php artisan returns:cleanup-communications
                            {--days=90 : Días de antigüedad para eliminar}
                            {--dry-run : Ejecutar sin eliminar registros}`

**Clase:** `App\Console\Commands\CleanupOldCommunications`

---

### `returns:send-reminders
`

**Descripción:** Enviar recordatorios automáticos para devoluciones pendientes

**Archivo:** `SendReturnReminders.php`

**Comando completo:** `php artisan returns:send-reminders
                            {--days=7 : Días de antigüedad para enviar recordatorio}
                            {--dry-run : Ejecutar sin enviar emails reales}
                            {--status=* : Estados específicos a procesar}`

**Clase:** `App\Console\Commands\SendReturnReminders`

---

### `returns:update-tracking`

**Descripción:** Update tracking statuses for all active pickup requests

**Archivo:** `UpdateTrackingStatuses.php`

**Comando completo:** `php artisan returns:update-tracking`

**Clase:** `App\Console\Commands\UpdateTrackingStatuses`

---

### `roles:assign`

**Descripción:** Assign or change a role for a user. Usage: roles:assign user@email.com rolename

**Archivo:** `AssignRoleCommand.php`

**Comando completo:** `php artisan roles:assign {email} {role}`

**Clase:** `App\Console\Commands\AssignRoleCommand`

---

### `roles:create`

**Descripción:** Create all application roles from roleMapping configuration

**Archivo:** `CreateRolesCommand.php`

**Comando completo:** `php artisan roles:create`

**Clase:** `App\Console\Commands\CreateRolesCommand`

---

### `roles:list`

**Descripción:** List all roles and optionally show users with their roles

**Archivo:** `ListRolesCommand.php`

**Comando completo:** `php artisan roles:list {--users : Show users with their roles}`

**Clase:** `App\Console\Commands\ListRolesCommand`

---

### `routes:clean-duplicates`

**Descripción:** Remove duplicate routes from the database (by name)

**Archivo:** `CleanDuplicateRoutesCommand.php`

**Comando completo:** `php artisan routes:clean-duplicates`

**Clase:** `App\Console\Commands\CleanDuplicateRoutesCommand`

---

### `routes:daemon
`

**Descripción:** Start/stop route watcher as a background daemon process

**Archivo:** `StartRouteWatcherDaemonCommand.php`

**Comando completo:** `php artisan routes:daemon
                            {--interval=5 : Check interval in seconds}
                            {--stop : Stop the daemon}
                            {--status : Show daemon status}`

**Clase:** `App\Console\Commands\StartRouteWatcherDaemonCommand`

---

### `routes:sync`

**Descripción:** Synchronize application routes with database, automatically detecting added and deleted routes

**Archivo:** `SyncRoutesCommand.php`

**Comando completo:** `php artisan routes:sync {--force : Force synchronization even if no changes detected}`

**Clase:** `App\Console\Commands\SyncRoutesCommand`

---

### `routes:watch
`

**Descripción:** Monitor route files for changes and automatically sync with database. Press Ctrl+C to stop.

**Archivo:** `WatchRoutesCommand.php`

**Comando completo:** `php artisan routes:watch
                            {--interval=5 : Check interval in seconds}
                            {--add= : Add additional file to watch}`

**Clase:** `App\Console\Commands\WatchRoutesCommand`

---

### `sender:verify`

**Descripción:** Verify Sender

**Archivo:** `VerifySender.php`

**Comando completo:** `php artisan sender:verify`

**Clase:** `App\Console\Commands\VerifySender`

---

### `supervisor:backup`

**Descripción:** Crear un backup manual de las configuraciones de Supervisor

**Archivo:** `SupervisorBackupCommand.php`

**Comando completo:** `php artisan supervisor:backup {name? : Nombre del backup} {--environment=dev : Ambiente (dev, prod, staging)} {--description= : Descripción opcional}`

**Clase:** `App\Console\Commands\SupervisorBackupCommand`

---

### `system:cleanup`

**Descripción:** System cleanup

**Archivo:** `SystemCleanup.php`

**Comando completo:** `php artisan system:cleanup`

**Clase:** `App\Console\Commands\SystemCleanup`

---

### `tickets:check-sla-breaches`

**Descripción:** Verificar incumplimientos de SLA en tickets abiertos

**Archivo:** `CheckSlaBreaches.php`

**Comando completo:** `php artisan tickets:check-sla-breaches`

**Clase:** `App\Console\Commands\CheckSlaBreaches`

---

### `tickets:sla-warnings`

**Descripción:** Enviar advertencias sobre SLAs próximos a vencer

**Archivo:** `SendSlaWarnings.php`

**Comando completo:** `php artisan tickets:sla-warnings {--threshold=80 : Porcentaje de umbral para advertencias}`

**Clase:** `App\Console\Commands\SendSlaWarnings`

---

### `translation:merge`

**Descripción:** Merge translation phrases from $new to $current (overwrite). The utility is helpful when we have a new translation file and want to apply it to a current file in the repos.
        IMPORTANT: do not merge any files under lang/en/ folder (which is considered the main language) or it may add redundant keys to the main file which will in turn propogate to the other files of other languages

**Archivo:** `MergeTranslationFiles.php`

**Comando completo:** `php artisan translation:merge {current} {update}`

**Clase:** `App\Console\Commands\MergeTranslationFiles`

---

### `translation:upgrade`

**Descripción:** Update translation files to make those up-to-date with the default EN language

**Archivo:** `UpgradeTranslation.php`

**Comando completo:** `php artisan translation:upgrade`

**Clase:** `App\Console\Commands\UpgradeTranslation`

---

### `warranties:process`

**Descripción:** Procesar garantías: notificaciones, estados, sincronización

**Archivo:** `ProcessWarranties.php`

**Comando completo:** `php artisan warranties:process {--sync-manufacturers : Sincronizar con fabricantes}`

**Clase:** `App\Console\Commands\ProcessWarranties`

---

## Comandos de Laravel

Estos son los comandos predeterminados de Laravel disponibles en este proyecto.

## Guía de Uso

### Regenerar Documentación

Para actualizar este documento después de crear o modificar comandos:

```bash
php artisan docs:generate-commands
```

### Convenciones de Naming

Al crear nuevos comandos, sigue estas convenciones:

- **Nombre de clase**: PascalCase, ej: `SyncRoutesCommand`
- **Signature**: snake-case con namespace, ej: `routes:sync`
- **Descripción**: Texto claro en español explicando qué hace

### Estructura de un Comando Personalizado

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MyCommand extends Command
{
    protected $signature = 'namespace:command-name';
    protected $description = 'Descripción clara del comando';

    public function handle(): int
    {
        // Lógica del comando aquí
        return Command::SUCCESS;
    }
}
```

### Mejores Prácticas

1. **Descripción clara**: Siempre incluye una descripción `protected $description`
2. **Namespace lógico**: Agrupa comandos relacionados bajo el mismo namespace
3. **Validación**: Valida los argumentos y opciones en el comando
4. **Feedback al usuario**: Usa `info()`, `warn()`, `error()` para comunicar progreso
5. **Return codes**: Retorna `SUCCESS` o `FAILURE` apropiadamente
6. **Documentación automática**: Actualiza la documentación después de cambios


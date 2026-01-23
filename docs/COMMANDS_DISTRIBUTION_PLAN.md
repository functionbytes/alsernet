# Plan de Distribución de Comandos de Consola

## Resumen
39 comandos de consola actualmente en `app/Console/Commands/` necesitan ser distribuidos a sus módulos correspondientes.

---

## Distribución por Módulo

### 1. **Módulo Returns** (5 comandos)
Ubicación destino: `Modules/Returns/app/Console/Commands/`

| Comando | Descripción | Prioridad |
|---------|-------------|-----------|
| `ProcessComponents.php` | Procesar componentes de retorno (stock, optimizaciones) | 🔴 Alto |
| `ProcessWarranties.php` | Procesar garantías (notificaciones, sincronización) | 🔴 Alto |
| `SendReturnReminders.php` | Enviar recordatorios automáticos de devoluciones | 🔴 Alto |
| `AuditReturnRules.php` | Auditar reglas de devolución | 🟡 Medio |
| `MigrateProductBlockades.php` | Migración histórica de bloqueos de producto | 🟢 Bajo |

**Dependencias**: `Modules\Returns\Services\*`

---

### 2. **Módulo Documents** (3 comandos)
Ubicación destino: `Modules/Documents/app/Console/Commands/`

| Comando | Descripción | Prioridad |
|---------|-------------|-----------|
| `MigrateTicketCategoryToHelpdesk.php` | Migración de categorías de tickets | 🟢 Bajo |
| `MigrateTicketStatusToHelpdesk.php` | Migración de estados de tickets | 🟢 Bajo |
| `CheckSlaBreaches.php` | Verificar incumplimientos de SLA en documentos | 🟡 Medio |

**Dependencias**: `Modules\Documents\Models\*`, `Modules\Documents\Services\*`

---

### 3. **Módulo Mail/Campaign** (4 comandos)
Ubicación destino: `Modules/Campaign/app/Console/Commands/`

| Comando | Descripción | Prioridad |
|---------|-------------|-----------|
| `TestCampaign.php` | Probar campañas de email | 🔴 Alto |
| `SendGroupNotification.php` | Enviar notificación a grupo | 🟡 Medio |
| `SendTestNotifications.php` | Enviar notificaciones de prueba | 🟡 Medio |
| `VerifySender.php` | Verificar configuración de remitente de email | 🟡 Medio |

**Dependencias**: `Modules\Mail\Services\*`, `Modules\Campaign\Services\*`

---

### 4. **Módulo Warehouse** (2 comandos)
Ubicación destino: `Modules/Warehouse/app/Console/Commands/`

| Comando | Descripción | Prioridad |
|---------|-------------|-----------|
| `UpdateTrackingStatuses.php` | Actualizar estados de seguimiento de envíos | 🟡 Medio |
| `MigrateProductBlockades.php` | Migración de bloqueos de inventario | 🟢 Bajo |

**Dependencias**: `Modules\Warehouse\Models\*`

---

### 5. **Módulo Helpdesk** (2 comandos)
Ubicación destino: `Modules/Helpdesk/app/Console/Commands/` (crear módulo si no existe)

| Comando | Descripción | Prioridad |
|---------|-------------|-----------|
| `SendSlaWarnings.php` | Enviar alertas de SLA | 🔴 Alto |
| `CleanupOldCommunications.php` | Limpiar comunicaciones antiguas | 🟡 Medio |

**Dependencias**: `Modules\Helpdesk\Models\*`

---

### 6. **Core / Admin** (23 comandos)
Ubicación: Permanecen en `app/Console/Commands/` (aplicación principal)

#### 6.1 Gestión de Roles y Permisos (4 comandos)
```
- AssignPermissionCommand.php
- AssignRoleCommand.php
- CreatePermissionsCommand.php
- CreateRolesCommand.php
- ListPermissionsCommand.php
- ListRolesCommand.php
```

#### 6.2 Mantenimiento y Limpieza (6 comandos)
```
- CleanupOldLogs.php           - Limpiar logs antiguos
- CleanOldNotifications.php    - Limpiar notificaciones antiguas
- SystemCleanup.php            - Limpieza general del sistema
- ConfigureMaintenanceTools.php - Configurar herramientas de mantenimiento
- FixMediaPermissions.php      - Reparar permisos de media
- MergeTranslationFiles.php    - Fusionar archivos de traducción
```

#### 6.3 Rutas y Sincronización (5 comandos)
```
- SyncRoutesCommand.php              - Sincronizar rutas definidas
- WatchRoutesCommand.php             - Vigilar cambios de rutas
- StartRouteWatcherDaemonCommand.php - Iniciar demonio vigilador de rutas
- CleanDuplicateRoutesCommand.php    - Limpiar rutas duplicadas
- SyncPrestaShopCategories.php       - Sincronizar con PrestaShop
```

#### 6.4 Utilidades y Herramientas (4 comandos)
```
- GenerateCommandsDocumentation.php  - Generar documentación de comandos
- GeoIpCheck.php                    - Verificar GeoIP
- ErpCheckCommand.php               - Verificar conexión ERP
- RunHandler.php                    - Ejecutador de handlers
```

#### 6.5 Backups (2 comandos)
```
- RunScheduledBackups.php       - Ejecutar backups programados
- SupervisorBackupCommand.php   - Backup de configuración Supervisor
```

#### 6.6 Migraciones y Upgrades (2 comandos)
```
- UpgradeTranslation.php        - Actualizar traducciones
```

---

## Análisis de Dependencias

### Comandos sin dependencias claras (pueden quedar en Core)
- `GenerateCommandsDocumentation.php`
- `GeoIpCheck.php`
- `ErpCheckCommand.php`
- `RunHandler.php`
- `CleanDuplicateRoutesCommand.php`
- `MergeTranslationFiles.php`
- `UpgradeTranslation.php`

### Comandos que necesitan refactorización de dependencias
- `SyncPrestaShopCategories.php` - Necesita servicio opcional de Prestashop
- `TestCampaign.php` - Necesita servicio de Campaign
- `SendGroupNotification.php` - Necesita servicio de Mail

---

## Estructura de Directorios a Crear

```
Modules/Returns/app/Console/
├── Commands/
│   ├── ProcessComponents.php
│   ├── ProcessWarranties.php
│   ├── SendReturnReminders.php
│   ├── AuditReturnRules.php
│   └── MigrateProductBlockades.php

Modules/Documents/app/Console/
├── Commands/
│   ├── MigrateTicketCategoryToHelpdesk.php
│   ├── MigrateTicketStatusToHelpdesk.php
│   └── CheckSlaBreaches.php

Modules/Campaign/app/Console/
├── Commands/
│   ├── TestCampaign.php
│   ├── SendGroupNotification.php
│   ├── SendTestNotifications.php
│   └── VerifySender.php

Modules/Warehouse/app/Console/
├── Commands/
│   └── UpdateTrackingStatuses.php

Modules/Helpdesk/app/Console/
├── Commands/
│   ├── SendSlaWarnings.php
│   └── CleanupOldCommunications.php

app/Console/Commands/
├── (23 comandos de aplicación principal)
```

---

## Pasos de Implementación

### Fase 1: Planificación ✅ (Esta fase)
- [x] Mapear todos los comandos
- [x] Identificar módulos destino
- [x] Crear plan de distribución

### Fase 2: Preparación
- [ ] Crear directorios `Console/Commands` en módulos
- [ ] Revisar cada comando para dependencias de módulos deshabilitados
- [ ] Identificar servicios que deben ser inyectados opcionalmente

### Fase 3: Movimiento
- [ ] Copiar comandos a módulos correspondientes
- [ ] Actualizar namespaces (`App\Console\Commands` → `Modules\{Nombre}\Console\Commands`)
- [ ] Actualizar imports de servicios
- [ ] Actualizar firmas de comando (namespace en signature)

### Fase 4: Actualización de Autoloader
- [ ] Agregar PSR-4 para `Console` en `composer.json` si es necesario
- [ ] Ejecutar `composer dump-autoload`

### Fase 5: Validación
- [ ] Verificar sintaxis PHP de todos los comandos
- [ ] Ejecutar `php artisan list` para verificar que se cargan
- [ ] Ejecutar cada comando para verificar funcionamiento

### Fase 6: Limpieza y Documentación
- [ ] Eliminar comandos de `app/Console/Commands/`
- [ ] Crear documentación de referencia
- [ ] Actualizar guías de módulos

---

## Comandos por Tamaño

### Pequeños (<30 líneas)
```
- AssignPermissionCommand.php
- AssignRoleCommand.php
- CreatePermissionsCommand.php
- CreateRolesCommand.php
- ListPermissionsCommand.php
- ListRolesCommand.php
- CleanOldNotifications.php
- GeoIpCheck.php
```

### Medianos (30-80 líneas)
```
Mayoría de los comandos
```

### Grandes (>80 líneas)
```
- ProcessComponents.php
- ProcessWarranties.php
- SystemCleanup.php
- SyncRoutesCommand.php
- WatchRoutesCommand.php
- StartRouteWatcherDaemonCommand.php
```

---

## Consideraciones Especiales

### 1. Comandos que dependen de módulos deshabilitados
- `SyncPrestaShopCategories.php` - Depende de Prestashop (deshabilitado)
  - **Solución**: Hacer opcional la inyección de servicio

### 2. Comandos de migración histórica
- `MigrateProductBlockades.php`
- `MigrateTicketCategoryToHelpdesk.php`
- `MigrateTicketStatusToHelpdesk.php`
- **Consideración**: Podrían eliminarse después de ejecutarse en producción

### 3. Comandos de Supervisor
- `SupervisorBackupCommand.php`
- **Consideración**: Podría requerirse acceso especial, posiblemente mantener en core

### 4. Comandos de enrutamiento
- `SyncRoutesCommand.php`
- `WatchRoutesCommand.php`
- `StartRouteWatcherDaemonCommand.php`
- `CleanDuplicateRoutesCommand.php`
- **Consideración**: Son de aplicación global, mejor mantener en core

---

## Resumen de Cambios

| Aspecto | Cantidad | Detalles |
|---------|----------|----------|
| Comandos a mover | 16 | 5 Returns, 3 Documents, 4 Campaign, 2 Warehouse, 2 Helpdesk |
| Comandos a mantener en Core | 23 | Admin, Mantenimiento, Rutas, Utilidades, Backups |
| Directorios a crear | 5 | Console/Commands en cada módulo |
| Actualizaciones de namespace | 16 | En comandos movidos |
| Archivos a eliminar | 16 | De `app/Console/Commands/` |

---

## Notas Finales

- Los comandos en Core pueden seguir siendo accesibles desde la raíz de la aplicación
- Las rutas de consola en `routes/console.php` no requieren cambios (Laravel auto-descubre)
- Si Laravel no auto-descubre comandos en módulos, se puede registrar en `routes/console.php`
- La documentación de cada módulo debe incluir la lista de comandos disponibles

---

**Estado**: Plan Completo ✅
**Próximo Paso**: Implementar Fase 2 - Preparación

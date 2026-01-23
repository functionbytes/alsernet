# Módulo Supplier - Documentación Técnica

## Descripción General

El módulo **Supplier** es un sistema completo de gestión de proveedores con sincronización bidireccional con ERP Oracle. Proporciona funcionalidades para:

- Gestión de proveedores y sus productos
- Sincronización automática de datos con Oracle ERP
- Manejo de precios y categorías
- Automatización de flujos de trabajo
- Generación de contenido con IA
- Auditoría de conflictos y fallos de sincronización

---

## Arquitectura del Módulo

### Estructura de Directorios

```
modules/Supplier/
├── app/
│   ├── Commands/           # Comandos Artisan
│   ├── Events/             # Eventos del sistema
│   ├── Http/
│   │   └── Controllers/    # Controladores web y API
│   ├── Jobs/               # Jobs de procesamiento asíncrono
│   ├── Listeners/          # Event listeners para sincronización
│   ├── Models/             # Modelos Eloquent (45 modelos)
│   ├── Observers/          # Observers para detectar cambios
│   ├── Providers/          # Service providers del módulo
│   └── Services/           # Servicios de negocio
├── database/
│   ├── migrations/         # Migraciones de base de datos
│   └── seeders/            # Seeders para datos iniciales
├── resources/
│   └── views/              # Vistas Blade
├── routes/
│   └── web.php             # Definición de rutas
└── config/
    └── supplier.php        # Configuración del módulo
```

---

## Componentes Principales

### 1. Modelos (45 en total)

**Modelos Core**:
- `Supplier` - Proveedor principal
- `SupplierProduct` - Productos del proveedor
- `SupplierProductPrice` - Precios históricos
- `SupplierErpProvider` - Proveedores en ERP
- `SupplierCategory` - Categorías de productos
- `SupplierSyncFailure` - Fallos de sincronización
- `SupplierSyncConflict` - Conflictos detectados

**Modelos de Automatización**:
- `SupplierAutomationChain` - Cadenas de automatización
- `SupplierAutomationExecution` - Ejecuciones de workflows
- `SupplierAutomationChainExecution` - Ejecución de cadenas

**Modelos de IA**:
- `SupplierAiContent` - Contenido generado con IA
- `SupplierPrompt` - Prompts para IA

**Modelos de Extracción**:
- `SupplierSource` - Fuentes de datos (API, FTP, etc.)
- `SupplierExtractionBatch` - Lotes de extracción
- `SupplierExtractionMapping` - Mapeos de campos

### 2. Servicios

#### ErpSyncService
Gestiona la sincronización bidireccional con Oracle ERP.

**Métodos principales**:
- `syncPriceToErp()` - Sincroniza precio local → ERP
- `syncProductToErp()` - Sincroniza producto local → ERP
- `syncProviderToErp()` - Sincroniza proveedor local → ERP
- `registerConflict()` - Registra conflictos para auditoría
- `handleSyncFailure()` - Maneja fallos de sincronización

**Características**:
- Estrategia "ERP always wins" para resolución de conflictos
- Prevención de loops infinitos con cache flags
- Dead letter queue para fallos
- Límite de reintentos configurable

#### AutomationOrchestrationService
Orquesta workflows de automatización.

**Funcionalidades**:
- Ejecución de cadenas de pasos
- Manejo de triggers (schedule, manual, event)
- Registro de logs y métricas
- Retry logic para pasos fallidos

#### ContentGenerationService
Genera contenido con IA para productos.

**Funcionalidades**:
- Generación de descripciones
- Optimización SEO
- Traducciones automáticas
- Procesamiento batch

#### ExtractionService
Extrae datos de fuentes externas.

**Fuentes soportadas**:
- REST APIs
- FTP/SFTP
- CSV/Excel files
- XML feeds
- Webhooks

### 3. Observers

Los observers detectan cambios en modelos y disparan eventos de sincronización:

- `SupplierProductPriceObserver` - Detecta cambios en precios
- `SupplierProductObserver` - Detecta cambios en productos
- `SupplierErpProviderObserver` - Detecta cambios en proveedores

**Eventos disparados**:
- `SupplierProductPriceChanged`
- `SupplierProductUpdated`
- `SupplierErpProviderUpdated`

### 4. Listeners

Los listeners encolan jobs para sincronización:

- `SyncPriceToErpListener` - Encola sincronización de precios
- `SyncProductToErpListener` - Encola sincronización de productos
- `SyncProviderToErpListener` - Encola sincronización de proveedores

**Cola de procesamiento**: `erp-sync` (alta prioridad)

### 5. Comandos Artisan

#### `supplier:cleanup-sync-cache`
Limpia flags de sincronización obsoletos.

```bash
# Ejecución manual
php artisan supplier:cleanup-sync-cache

# Con opciones
php artisan supplier:cleanup-sync-cache --dry-run --ttl=120
```

**Programación**: Se ejecuta cada hora vía scheduler.

**Opciones**:
- `--dry-run` - Simula sin eliminar
- `--ttl=60` - TTL máximo en minutos (default: 60)

---

## Sistema de Sincronización Bidireccional

### Flujo de Datos

```
┌─────────────────┐         ┌──────────────────┐
│  Supplier DB    │◄───────►│   Oracle ERP     │
│  (PostgreSQL)   │         │   (Production)   │
└────────┬────────┘         └────────▲─────────┘
         │                           │
         │                           │
    ┌────▼────────┐         ┌────────┴─────────┐
    │  Observer   │──event──►│   Listener      │
    │  Detecta    │         │   Encola Job     │
    │  Cambios    │         │   erp-sync       │
    └─────────────┘         └──────────────────┘
                                     │
                            ┌────────▼─────────┐
                            │  ErpSyncService  │
                            │  Sincroniza a    │
                            │  Oracle          │
                            └──────────────────┘
```

### Características Clave

1. **Estrategia "ERP Always Wins"**
   - En caso de conflicto, el ERP tiene la verdad absoluta
   - Los datos locales se sobrescriben con los del ERP
   - Se auditan todos los conflictos en `supplier_sync_conflicts`

2. **Prevención de Loops Infinitos**
   - Cache flags: `sync_in_progress_{type}_{id}`
   - TTL configurable (default: 5 minutos)
   - Cleanup automático cada hora

3. **Manejo de Fallos**
   - Dead letter queue: `supplier_sync_failures`
   - Límite de reintentos: 3 (configurable)
   - Retry exponencial backoff
   - Dashboard de monitoreo

4. **Auditoría Completa**
   - Todos los cambios se registran
   - Comparación local vs ERP
   - Campos modificados identificados
   - Timestamps de detección y resolución

---

## Base de Datos

### Tablas Principales

#### `supplier_products`
```sql
id, uid, supplier_id, erp_product_id, code, barcode, name,
description, category_id, price, stock, is_active,
last_synced_at, created_at, updated_at, deleted_at
```

**Índices de rendimiento**:
- `idx_products_erp_active` - Composite (erp_product_id, is_active)
- `idx_products_code` - Individual (code)
- `idx_products_barcode` - Individual (barcode)
- `idx_products_last_sync` - Individual (last_synced_at)

#### `supplier_product_prices`
```sql
id, uid, supplier_product_id, supplier_provider_product_id,
price, currency, effective_date, is_current, is_active,
last_synced_at, created_at, updated_at
```

**Índices de rendimiento**:
- `idx_prices_provider_current` - Composite (supplier_provider_product_id, is_current)
- `idx_prices_effective_date` - Individual (effective_date)
- `idx_prices_last_sync` - Individual (last_synced_at)

#### `supplier_sync_failures`
```sql
id, sync_type, supplier_id, erp_id, error_message,
error_details, retry_count, max_retries, last_retry_at,
resolved_at, created_at, updated_at
```

**Índices de rendimiento**:
- `idx_sync_failures_type_retry` - Composite (sync_type, retry_count)
- `idx_sync_failures_last_retry` - Individual (last_retry_at)

#### `supplier_sync_conflicts`
```sql
id, entity_type, entity_id, erp_id, resolution_strategy,
local_data, erp_data, resolved_data, changed_fields,
conflict_detected_at, resolved_at, resolved_by_user_id,
resolution_ip, resolution_notes, created_at, updated_at
```

### Migraciones Clave

1. **2026_01_16_102755_create_supplier_sync_conflicts_table.php**
   - Crea tabla de auditoría de conflictos
   - Incluye índices para consultas rápidas

2. **2026_01_16_104947_add_performance_indexes_to_supplier_tables.php**
   - Agrega índices de optimización a 7 tablas
   - Mejora rendimiento de consultas frecuentes
   - Usa verificación de existencia para idempotencia

---

## Rutas y Permisos

### Rutas Operacionales (`/suppliers`)

**Permisos requeridos**: `auth` middleware

```php
GET    /suppliers                    - Listado de proveedores
GET    /suppliers/data               - Datos AJAX
GET    /suppliers/show/{uid}         - Ver detalle
GET    /suppliers/edit/{uid}         - Editar
PUT    /suppliers/{uid}              - Actualizar
DELETE /suppliers/{uid}              - Eliminar
POST   /suppliers/{uid}/toggle       - Activar/Desactivar
POST   /suppliers/test-all           - Probar conexiones
```

### Rutas de Configuración (`/settings/suppliers`)

**Permisos requeridos**: `role:super-admin`

```php
# Gestión de proveedores
GET    /settings/suppliers/create    - Crear proveedor
POST   /settings/suppliers           - Guardar proveedor

# Prompts
GET    /settings/suppliers/prompts                  - Listado
POST   /settings/suppliers/prompts/{uid}/toggle     - Activar/Desactivar
POST   /settings/suppliers/prompts/{uid}/test       - Probar prompt

# Automatización
GET    /settings/suppliers/automation               - Dashboard
POST   /settings/suppliers/automation/workflows/{uid}/run  - Ejecutar workflow
POST   /settings/suppliers/automation/workflows/run-all    - Ejecutar todos

# Contenido generado
GET    /settings/suppliers/content                  - Listado
POST   /settings/suppliers/content/publish/{uid}    - Publicar contenido

# Fallos de sincronización
GET    /settings/suppliers/sync-failures            - Dashboard
POST   /settings/suppliers/sync-failures/{id}/retry - Reintentar
POST   /settings/suppliers/sync-failures/bulk-retry - Reintentar múltiples
DELETE /settings/suppliers/sync-failures/{id}       - Eliminar
```

---

## Configuración

### Archivo: `config/supplier.php`

```php
return [
    // Configuración de sincronización ERP
    'erp' => [
        'enabled' => env('SUPPLIER_ERP_SYNC_ENABLED', true),
        'host' => env('SUPPLIER_ERP_HOST', 'erp.example.com'),
        'port' => env('SUPPLIER_ERP_PORT', 1521),
        'database' => env('SUPPLIER_ERP_DATABASE', 'orcl'),
        'username' => env('SUPPLIER_ERP_USERNAME', ''),
        'password' => env('SUPPLIER_ERP_PASSWORD', ''),
        'timeout' => env('SUPPLIER_ERP_TIMEOUT', 30),
        'retry_attempts' => env('SUPPLIER_ERP_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('SUPPLIER_ERP_RETRY_DELAY', 60), // seconds
    ],

    // Configuración de sincronización bidireccional
    'sync' => [
        'strategy' => 'erp_wins', // Estrategia de resolución de conflictos
        'cache_ttl' => 300, // 5 minutos
        'cleanup_cron' => '0 * * * *', // Cada hora
        'queue' => 'erp-sync',
    ],

    // Configuración de automatización
    'automation' => [
        'enabled' => env('SUPPLIER_AUTOMATION_ENABLED', true),
        'max_executions_per_minute' => 10,
        'timeout' => 600, // 10 minutos
    ],

    // Configuración de IA
    'ai' => [
        'provider' => env('SUPPLIER_AI_PROVIDER', 'openai'),
        'model' => env('SUPPLIER_AI_MODEL', 'gpt-4'),
        'api_key' => env('SUPPLIER_AI_API_KEY', ''),
        'max_tokens' => 2000,
    ],
];
```

### Variables de Entorno

```env
# ERP Oracle Connection
SUPPLIER_ERP_SYNC_ENABLED=true
SUPPLIER_ERP_HOST=erp.alsernet.com
SUPPLIER_ERP_PORT=1521
SUPPLIER_ERP_DATABASE=orcl
SUPPLIER_ERP_USERNAME=supplier_sync
SUPPLIER_ERP_PASSWORD=secret
SUPPLIER_ERP_TIMEOUT=30
SUPPLIER_ERP_RETRY_ATTEMPTS=3
SUPPLIER_ERP_RETRY_DELAY=60

# AI Configuration
SUPPLIER_AUTOMATION_ENABLED=true
SUPPLIER_AI_PROVIDER=openai
SUPPLIER_AI_MODEL=gpt-4
SUPPLIER_AI_API_KEY=sk-...
```

---

## Testing

### Ejecución de Tests

```bash
# Tests específicos del módulo Supplier
php artisan test modules/Supplier/tests/

# Tests de sincronización ERP
php artisan test --filter=ErpSyncServiceTest

# Tests de observadores
php artisan test --filter=SupplierObserverTest
```

### Tests Críticos

1. **ErpSyncServiceTest** - Prueba sincronización bidireccional
2. **SupplierProductPriceObserverTest** - Prueba detección de cambios
3. **CleanupSyncCacheCommandTest** - Prueba limpieza de cache
4. **SupplierSyncFailuresControllerTest** - Prueba dashboard de fallos

---

## Monitoreo y Debugging

### Logs

**Ubicación**: `storage/logs/laravel.log`

**Contextos importantes**:
```php
Log::info('Sync cache cleanup completed', [
    'total_scanned' => 150,
    'total_cleaned' => 5,
    'max_ttl_minutes' => 60,
]);

Log::warning('ERP sync failed', [
    'sync_type' => 'price',
    'supplier_id' => 123,
    'error' => 'Connection timeout',
]);

Log::error('Conflict detected during sync', [
    'entity_type' => 'product',
    'entity_id' => 456,
    'local_value' => 100,
    'erp_value' => 105,
]);
```

### Métricas Clave

- **Fallos de sincronización**: `supplier_sync_failures.count()`
- **Conflictos no resueltos**: `supplier_sync_conflicts.unresolved.count()`
- **Tasa de éxito**: `(total_syncs - failures) / total_syncs * 100`
- **Tiempo promedio de sync**: `AVG(sync_duration)`

### Dashboard de Monitoreo

**URL**: `/settings/suppliers/sync-failures`

**Métricas mostradas**:
- Total de fallos
- Fallos reintentables
- Total de conflictos
- Conflictos no resueltos

---

## Deployment

### Checklist de Deployment

- [ ] Ejecutar migraciones: `php artisan migrate --force`
- [ ] Limpiar cache: `php artisan cache:clear`
- [ ] Compilar assets: `npm run build`
- [ ] Configurar variables de entorno ERP
- [ ] Configurar workers de cola `erp-sync`
- [ ] Configurar scheduler para cleanup comando
- [ ] Verificar permisos de escritura en `storage/logs`
- [ ] Probar conexión con ERP Oracle
- [ ] Verificar que los índices están creados

### Configuración de Workers

```bash
# Supervisor configuration for erp-sync queue
[program:supplier-erp-sync-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=erp-sync --tries=3 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/logs/erp-sync-worker.log
stopwaitsecs=3600
```

### Scheduler Configuration

```bash
# Crontab entry for Laravel scheduler
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

El scheduler ejecutará automáticamente:
- `supplier:cleanup-sync-cache` cada hora

---

## Enlaces a Documentación Adicional

- [Sistema de Sincronización ERP](./erp-sync.md) - Documentación detallada del sistema de sincronización
- [Dashboard de Fallos](./sync-failures-dashboard.md) - Guía del dashboard de monitoreo
- [Troubleshooting](./troubleshooting.md) - Solución de problemas comunes
- [Guía de Pruebas](../../guides/supplier-sync-failures-testing.md) - Testing manual

---

## Changelog

### v2.0.0 (2026-01-16)
- ✨ Refactorización de Entities → Models (45 modelos)
- ✨ Sistema de auditoría de conflictos
- ✨ Dashboard de fallos de sincronización
- ✨ Comando de limpieza de cache
- ✨ Índices de optimización de rendimiento
- 🐛 Corrección de loops infinitos en sincronización
- 📝 Documentación técnica completa

### v1.0.0 (2025-12-01)
- 🎉 Release inicial del módulo Supplier
- Sincronización bidireccional con ERP Oracle
- Gestión de proveedores y productos
- Sistema de automatización de workflows

---

**Última actualización**: 2026-01-16
**Mantenido por**: Equipo de Backend Alsernet
**Contacto**: backend@alsernet.com

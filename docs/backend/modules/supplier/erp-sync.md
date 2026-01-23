# Sistema de Sincronización Bidireccional ERP

## Descripción General

El sistema de sincronización bidireccional permite mantener sincronizados los datos del módulo Supplier con Oracle ERP. Implementa una estrategia **"ERP Always Wins"** donde el ERP es la fuente de verdad en caso de conflictos.

---

## Arquitectura del Sistema

### Diagrama de Flujo Completo

```
┌──────────────────────────────────────────────────────────────────────┐
│                    USUARIO MODIFICA DATO LOCAL                       │
│                  (Supplier Product Price, Product, Provider)         │
└────────────────────────────┬─────────────────────────────────────────┘
                             │
                 ┌───────────▼──────────────┐
                 │   MODEL OBSERVER         │
                 │  - ProductPriceObserver  │
                 │  - ProductObserver       │
                 │  - ErpProviderObserver   │
                 └───────────┬──────────────┘
                             │
                             │ Dispara Evento
                             │
                 ┌───────────▼──────────────┐
                 │   EVENT DISPATCHER       │
                 │  - PriceChanged          │
                 │  - ProductUpdated        │
                 │  - ProviderUpdated       │
                 └───────────┬──────────────┘
                             │
                             │ Escuchado por
                             │
                 ┌───────────▼──────────────┐
                 │   EVENT LISTENER         │
                 │  - SyncPriceToErp        │
                 │  - SyncProductToErp      │
                 │  - SyncProviderToErp     │
                 └───────────┬──────────────┘
                             │
                             │ Encola Job
                             │
                 ┌───────────▼──────────────┐
                 │   QUEUE: erp-sync        │
                 │   Priority: HIGH         │
                 └───────────┬──────────────┘
                             │
                             │ Worker procesa
                             │
                 ┌───────────▼──────────────────────────────────┐
                 │   ErpSyncService                             │
                 │                                               │
                 │  1. Verificar cache flag (prevent loop)      │
                 │  2. Set sync_in_progress_{type}_{id} = true  │
                 │  3. Leer datos locales                       │
                 │  4. Leer datos del ERP                       │
                 │  5. Comparar cambios                         │
                 │  6. ¿Conflicto detectado?                    │
                 └───────────┬──────────────────────────────────┘
                             │
                    ┌────────┴────────┐
                    │                 │
              SÍ ───┤                 ├─── NO
                    │                 │
        ┌───────────▼──────────────┐  │  ┌──────────────────────┐
        │  CONFLICTO DETECTADO     │  │  │  SINCRONIZACIÓN OK   │
        │                          │  │  │                      │
        │  1. Registrar conflicto  │  │  │  1. Actualizar ERP   │
        │     en audit table       │  │  │  2. Actualizar local │
        │  2. Aplicar estrategia   │  │  │  3. Marcar synced_at │
        │     "ERP Wins"           │  │  │  4. Liberar cache    │
        │  3. Sobrescribir local   │  │  └──────────────────────┘
        │  4. Liberar cache        │  │
        └──────────────────────────┘  │
                                      │
                         ┌────────────▼────────────┐
                         │   FALLÓ SINCRONIZACIÓN  │
                         │                         │
                         │  1. Registrar en        │
                         │     supplier_sync_      │
                         │     failures            │
                         │  2. Incrementar         │
                         │     retry_count         │
                         │  3. Liberar cache       │
                         │  4. ¿retry_count <      │
                         │     max_retries?        │
                         └─────────────┬───────────┘
                                       │
                              ┌────────┴────────┐
                              │                 │
                        SÍ ───┤                 ├─── NO
                              │                 │
                  ┌───────────▼──────────────┐  │  ┌──────────────────────┐
                  │  RE-ENCOLAR JOB          │  │  │  DEAD LETTER QUEUE   │
                  │  Con backoff exponencial │  │  │  Requiere intervención│
                  └──────────────────────────┘  │  │  manual desde         │
                                                │  │  dashboard            │
                                                │  └──────────────────────┘
                                                │
```

---

## Componentes del Sistema

### 1. Observers

Los observers detectan cambios en los modelos y disparan eventos.

#### SupplierProductPriceObserver

**Ubicación**: `modules/Supplier/app/Observers/SupplierProductPriceObserver.php`

**Métodos**:
```php
public function updated(SupplierProductPrice $price): void
{
    // Solo sincroniza si cambió el precio o estado
    if ($price->isDirty(['price', 'is_current', 'is_active'])) {
        event(new SupplierProductPriceChanged($price));
    }
}
```

**Eventos disparados**:
- `updated()` → `SupplierProductPriceChanged`
- `created()` → `SupplierProductPriceChanged`
- `deleted()` → `SupplierProductPriceChanged`

#### SupplierProductObserver

**Ubicación**: `modules/Supplier/app/Observers/SupplierProductObserver.php`

**Métodos**:
```php
public function updated(SupplierProduct $product): void
{
    if ($product->isDirty(['name', 'description', 'price', 'stock', 'is_active'])) {
        event(new SupplierProductUpdated($product));
    }
}
```

#### SupplierErpProviderObserver

**Ubicación**: `modules/Supplier/app/Observers/SupplierErpProviderObserver.php`

**Métodos**:
```php
public function updated(SupplierErpProvider $provider): void
{
    if ($provider->isDirty(['name', 'code', 'is_active'])) {
        event(new SupplierErpProviderUpdated($provider));
    }
}
```

### 2. Events

Los eventos encapsulan los datos del cambio.

**Ubicación**: `modules/Supplier/app/Events/`

**Eventos disponibles**:
- `SupplierProductPriceChanged` - Cambio en precio
- `SupplierProductUpdated` - Cambio en producto
- `SupplierErpProviderUpdated` - Cambio en proveedor

**Estructura común**:
```php
class SupplierProductPriceChanged
{
    public function __construct(
        public SupplierProductPrice $price
    ) {}
}
```

### 3. Listeners

Los listeners encolan jobs para procesamiento asíncrono.

**Ubicación**: `modules/Supplier/app/Listeners/`

**Listeners disponibles**:
- `SyncPriceToErpListener`
- `SyncProductToErpListener`
- `SyncProviderToErpListener`

**Estructura común**:
```php
class SyncPriceToErpListener implements ShouldQueue
{
    public $queue = 'erp-sync';
    public $delay = 5; // seconds

    public function __construct(
        protected ErpSyncService $erpSyncService
    ) {}

    public function handle(SupplierProductPriceChanged $event): void
    {
        $this->erpSyncService->syncPriceToErp($event->price);
    }
}
```

### 4. ErpSyncService

Servicio principal que ejecuta la sincronización.

**Ubicación**: `modules/Supplier/app/Services/ErpSyncService.php`

**Métodos públicos**:

#### syncPriceToErp()
```php
public function syncPriceToErp(SupplierProductPrice $price): void
{
    // 1. Verificar cache flag para prevenir loops
    if ($this->isSyncInProgress('price', $price->id)) {
        Log::warning('Sync already in progress', [
            'type' => 'price',
            'id' => $price->id,
        ]);
        return;
    }

    try {
        // 2. Marcar sync en progreso
        $this->markSyncInProgress('price', $price->id);

        // 3. Obtener datos del ERP
        $erpPrice = $this->getErpPrice($price->erp_price_id);

        // 4. Comparar datos
        $localData = $this->extractEntityData('price', $price);
        $erpData = $this->extractErpEntityData('price', $erpPrice);

        // 5. Detectar campos cambiados
        $changedFields = $this->detectChangedFields($localData, $erpData);

        // 6. ¿Hay conflicto?
        if (! empty($changedFields)) {
            // Registrar conflicto para auditoría
            $this->registerConflict('price', $price, $erpPrice, $changedFields);

            // Aplicar estrategia "ERP Wins"
            $price->update([
                'price' => $erpPrice->price,
                'currency' => $erpPrice->currency,
                'is_current' => $erpPrice->is_current,
                'last_synced_at' => now(),
            ]);

            Log::info('Conflict resolved (ERP wins)', [
                'type' => 'price',
                'id' => $price->id,
                'changed_fields' => $changedFields,
            ]);
        } else {
            // 7. Sin conflicto - actualizar ERP
            $this->updateErpPrice($price);

            // 8. Actualizar timestamp local
            $price->update(['last_synced_at' => now()]);

            Log::info('Price synced successfully', [
                'type' => 'price',
                'id' => $price->id,
            ]);
        }
    } catch (\Exception $e) {
        // 9. Manejar fallo
        $this->handleSyncFailure('price', $price->id, $e);
    } finally {
        // 10. Liberar cache flag
        $this->clearSyncInProgress('price', $price->id);
    }
}
```

**Métodos protegidos**:

```php
// Verificar si ya hay una sincronización en curso
protected function isSyncInProgress(string $type, int $id): bool
{
    return Cache::has("sync_in_progress_{$type}_{$id}");
}

// Marcar sincronización en progreso
protected function markSyncInProgress(string $type, int $id): void
{
    Cache::put("sync_in_progress_{$type}_{$id}", true, 300); // 5 min
}

// Liberar flag de sincronización
protected function clearSyncInProgress(string $type, int $id): void
{
    Cache::forget("sync_in_progress_{$type}_{$id}");
}

// Registrar conflicto para auditoría
protected function registerConflict(
    string $entityType,
    $localEntity,
    $erpEntity,
    array $changedFields
): void {
    SupplierSyncConflict::create([
        'entity_type' => $entityType,
        'entity_id' => $localEntity->id,
        'erp_id' => $this->getErpId($entityType, $erpEntity),
        'resolution_strategy' => SupplierSyncConflict::STRATEGY_ERP_WINS,
        'local_data' => $this->extractEntityData($entityType, $localEntity),
        'erp_data' => $this->extractErpEntityData($entityType, $erpEntity),
        'resolved_data' => $this->extractErpEntityData($entityType, $erpEntity),
        'changed_fields' => $changedFields,
        'conflict_detected_at' => now(),
        'resolved_at' => now(),
    ]);
}

// Manejar fallo de sincronización
protected function handleSyncFailure(
    string $type,
    int $id,
    \Exception $exception
): void {
    $failure = SupplierSyncFailure::firstOrCreate(
        [
            'sync_type' => $type,
            'supplier_id' => $id,
        ],
        [
            'erp_id' => null,
            'error_message' => $exception->getMessage(),
            'error_details' => json_encode([
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]),
            'retry_count' => 0,
            'max_retries' => 3,
        ]
    );

    $failure->increment('retry_count');
    $failure->update(['last_retry_at' => now()]);

    Log::error('ERP sync failed', [
        'type' => $type,
        'id' => $id,
        'retry_count' => $failure->retry_count,
        'error' => $exception->getMessage(),
    ]);
}
```

---

## Estrategia de Resolución de Conflictos

### "ERP Always Wins"

Cuando se detecta un conflicto (datos locales difieren de ERP), se sigue esta estrategia:

1. **Detección**: Comparar `local_data` vs `erp_data` campo por campo
2. **Registro**: Guardar conflicto en `supplier_sync_conflicts` para auditoría
3. **Resolución**: Sobrescribir datos locales con datos del ERP
4. **Auditoría**: Almacenar:
   - Datos locales antes de sobrescribir
   - Datos del ERP
   - Campos que cambiaron
   - Timestamp de detección y resolución

**Ejemplo de conflicto**:
```json
{
  "entity_type": "price",
  "entity_id": 123,
  "erp_id": 456,
  "resolution_strategy": "erp_wins",
  "local_data": {
    "price": 100.00,
    "currency": "USD",
    "is_current": true
  },
  "erp_data": {
    "price": 105.00,
    "currency": "USD",
    "is_current": true
  },
  "resolved_data": {
    "price": 105.00,
    "currency": "USD",
    "is_current": true
  },
  "changed_fields": ["price"],
  "conflict_detected_at": "2026-01-16 10:30:00",
  "resolved_at": "2026-01-16 10:30:01"
}
```

### Estrategias Alternativas (Futuras)

**Local Wins**: Datos locales tienen prioridad
```php
'resolution_strategy' => SupplierSyncConflict::STRATEGY_LOCAL_WINS
```

**Manual**: Requiere intervención humana
```php
'resolution_strategy' => SupplierSyncConflict::STRATEGY_MANUAL,
'resolved_at' => null // Pendiente de revisión
```

---

## Prevención de Loops Infinitos

### Problema

En un sistema bidireccional, hay riesgo de loops infinitos:

```
Local → ERP → dispara evento → Local → ERP → ...
```

### Solución: Cache Flags

**Mecanismo**:
1. Antes de sincronizar, verificar: `Cache::has("sync_in_progress_{type}_{id}")`
2. Si existe, abortar sincronización (ya hay una en curso)
3. Si no existe, establecer flag: `Cache::put("sync_in_progress_{type}_{id}", true, 300)`
4. Ejecutar sincronización
5. Limpiar flag: `Cache::forget("sync_in_progress_{type}_{id}")`

**TTL del flag**: 5 minutos (configurable)

**Cleanup automático**:
- Comando: `supplier:cleanup-sync-cache`
- Frecuencia: Cada hora (via scheduler)
- Elimina flags > 60 minutos (stale)

**Ventajas**:
- ✅ Previene loops infinitos
- ✅ No requiere cambios en base de datos
- ✅ Performance: Verificación en memoria (Redis)
- ✅ Auto-recovery: Flags expiran automáticamente

---

## Dead Letter Queue (DLQ)

### Concepto

Cuando una sincronización falla repetidamente (después de `max_retries`), el registro se mueve a la **Dead Letter Queue** (`supplier_sync_failures`).

### Proceso

1. **Primera falla**: `retry_count = 1`
2. **Re-intento con backoff exponencial**: Espera 60s, 120s, 240s...
3. **Alcanza max_retries (default: 3)**: Se marca como "dead"
4. **Intervención manual**: Admin debe revisar desde dashboard

### Motivos Comunes de Fallo

- **Timeout de conexión**: ERP no responde en tiempo
- **Credenciales inválidas**: Usuario/contraseña incorrectos
- **Datos inválidos**: Formato de datos no acepted por ERP
- **Constraint violations**: Violación de reglas de negocio en ERP
- **ERP fuera de línea**: Servidor ERP no disponible

### Retry desde Dashboard

**URL**: `/settings/suppliers/sync-failures`

**Acciones disponibles**:
- **Retry Individual**: Botón verde "Retry" → Reintenta un solo registro
- **Retry Bulk**: Checkbox multiple + "Retry Selected" → Reintenta múltiples
- **Delete**: Botón rojo "Delete" → Elimina de DLQ (no reintentará más)
- **View Details**: Ver error completo y stack trace

---

## Configuración

### Variables de Entorno

```env
# ERP Connection
SUPPLIER_ERP_SYNC_ENABLED=true
SUPPLIER_ERP_HOST=erp.alsernet.com
SUPPLIER_ERP_PORT=1521
SUPPLIER_ERP_DATABASE=orcl
SUPPLIER_ERP_USERNAME=supplier_sync
SUPPLIER_ERP_PASSWORD=secret_password
SUPPLIER_ERP_TIMEOUT=30

# Retry Configuration
SUPPLIER_ERP_RETRY_ATTEMPTS=3
SUPPLIER_ERP_RETRY_DELAY=60

# Cache TTL (seconds)
SUPPLIER_SYNC_CACHE_TTL=300
```

### Config File

**Ubicación**: `config/supplier.php`

```php
return [
    'erp' => [
        'enabled' => env('SUPPLIER_ERP_SYNC_ENABLED', true),
        'host' => env('SUPPLIER_ERP_HOST', ''),
        'port' => env('SUPPLIER_ERP_PORT', 1521),
        'database' => env('SUPPLIER_ERP_DATABASE', 'orcl'),
        'username' => env('SUPPLIER_ERP_USERNAME', ''),
        'password' => env('SUPPLIER_ERP_PASSWORD', ''),
        'timeout' => env('SUPPLIER_ERP_TIMEOUT', 30),
        'retry_attempts' => env('SUPPLIER_ERP_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('SUPPLIER_ERP_RETRY_DELAY', 60),
    ],

    'sync' => [
        'strategy' => 'erp_wins',
        'cache_ttl' => env('SUPPLIER_SYNC_CACHE_TTL', 300),
        'queue' => 'erp-sync',
        'cleanup_cron' => '0 * * * *', // Hourly
    ],
];
```

---

## Queue Workers

### Configuración de Supervisor

```ini
[program:supplier-erp-sync-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --queue=erp-sync --tries=3 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/erp-sync-worker.log
stopwaitsecs=3600
```

**Opciones importantes**:
- `--queue=erp-sync` - Procesa solo jobs de esta cola
- `--tries=3` - Máximo 3 intentos por job
- `--timeout=300` - Timeout de 5 minutos por job
- `numprocs=2` - 2 workers concurrentes

### Comandos de Gestión

```bash
# Iniciar workers
sudo supervisorctl start supplier-erp-sync-worker:*

# Detener workers
sudo supervisorctl stop supplier-erp-sync-worker:*

# Reiniciar workers
sudo supervisorctl restart supplier-erp-sync-worker:*

# Ver estado
sudo supervisorctl status supplier-erp-sync-worker:*

# Ver logs en tiempo real
tail -f storage/logs/erp-sync-worker.log
```

---

## Monitoreo y Logs

### Logs Relevantes

**Ubicación**: `storage/logs/laravel.log`

**Buscar por contexto**:
```bash
# Ver todos los logs de sincronización ERP
grep "ERP sync" storage/logs/laravel.log

# Ver solo fallos
grep "ERP sync failed" storage/logs/laravel.log

# Ver conflictos
grep "Conflict detected" storage/logs/laravel.log

# Ver sincronizaciones exitosas
grep "synced successfully" storage/logs/laravel.log
```

### Métricas a Monitorear

1. **Tasa de éxito de sincronización**:
   ```sql
   SELECT
       COUNT(*) as total_syncs,
       SUM(CASE WHEN resolved_at IS NOT NULL THEN 1 ELSE 0 END) as successful,
       (SUM(CASE WHEN resolved_at IS NOT NULL THEN 1 ELSE 0 END) / COUNT(*)) * 100 as success_rate
   FROM supplier_sync_conflicts
   WHERE created_at >= NOW() - INTERVAL 24 HOUR;
   ```

2. **Fallos pendientes de retry**:
   ```sql
   SELECT COUNT(*) as pending_retries
   FROM supplier_sync_failures
   WHERE retry_count < max_retries
     AND resolved_at IS NULL;
   ```

3. **Tiempo promedio de sincronización**:
   ```sql
   SELECT
       AVG(TIMESTAMPDIFF(SECOND, conflict_detected_at, resolved_at)) as avg_seconds
   FROM supplier_sync_conflicts
   WHERE resolved_at IS NOT NULL;
   ```

4. **Conflictos por tipo de entidad**:
   ```sql
   SELECT
       entity_type,
       COUNT(*) as count,
       COUNT(CASE WHEN resolved_at IS NULL THEN 1 END) as unresolved
   FROM supplier_sync_conflicts
   GROUP BY entity_type;
   ```

### Dashboard de Monitoreo

**URL**: `/settings/suppliers/sync-failures`

**Métricas mostradas**:
- Total Failures
- Retryable Failures (retry_count < max_retries)
- Total Conflicts
- Unresolved Conflicts

**Tablas**:
1. **Fallos de Sincronización**: Lista de fallos con botones de retry/delete
2. **Conflictos Detectados**: Lista de conflictos con detalles y estado

---

## Testing

### Unit Tests

```php
namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\Supplier\Services\ErpSyncService;
use Modules\Supplier\Models\SupplierProductPrice;

class ErpSyncServiceTest extends TestCase
{
    public function test_sync_price_to_erp_without_conflict()
    {
        $price = SupplierProductPrice::factory()->create([
            'price' => 100.00,
        ]);

        $service = app(ErpSyncService::class);
        $service->syncPriceToErp($price);

        $this->assertDatabaseHas('supplier_product_prices', [
            'id' => $price->id,
            'price' => 100.00,
        ]);

        $this->assertNotNull($price->fresh()->last_synced_at);
    }

    public function test_sync_price_to_erp_with_conflict_erp_wins()
    {
        // Simular conflicto: precio local = 100, precio ERP = 105

        $price = SupplierProductPrice::factory()->create([
            'price' => 100.00,
        ]);

        // Mock del ERP response
        // ...

        $service = app(ErpSyncService::class);
        $service->syncPriceToErp($price);

        // Verificar que el precio local se actualizó con el del ERP
        $this->assertDatabaseHas('supplier_product_prices', [
            'id' => $price->id,
            'price' => 105.00, // ERP wins
        ]);

        // Verificar que se registró el conflicto
        $this->assertDatabaseHas('supplier_sync_conflicts', [
            'entity_type' => 'price',
            'entity_id' => $price->id,
            'resolution_strategy' => 'erp_wins',
        ]);
    }

    public function test_prevents_infinite_loop_with_cache_flag()
    {
        $price = SupplierProductPrice::factory()->create();

        // Simular que ya hay una sincronización en progreso
        Cache::put("sync_in_progress_price_{$price->id}", true, 300);

        $service = app(ErpSyncService::class);
        $service->syncPriceToErp($price);

        // Verificar que no se actualizó last_synced_at
        $this->assertNull($price->fresh()->last_synced_at);
    }

    public function test_handles_sync_failure_and_registers_in_dlq()
    {
        $price = SupplierProductPrice::factory()->create();

        // Simular error de conexión con ERP
        // ...

        $service = app(ErpSyncService::class);

        try {
            $service->syncPriceToErp($price);
        } catch (\Exception $e) {
            // Expected
        }

        // Verificar que se registró el fallo
        $this->assertDatabaseHas('supplier_sync_failures', [
            'sync_type' => 'price',
            'supplier_id' => $price->id,
            'retry_count' => 1,
        ]);
    }
}
```

### Integration Tests

```php
namespace Tests\Integration;

use Tests\TestCase;
use Modules\Supplier\Models\SupplierProductPrice;
use Modules\Supplier\Events\SupplierProductPriceChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

class ErpSyncIntegrationTest extends TestCase
{
    public function test_observer_dispatches_event_on_price_change()
    {
        Event::fake([SupplierProductPriceChanged::class]);

        $price = SupplierProductPrice::factory()->create(['price' => 100]);

        // Actualizar precio
        $price->update(['price' => 110]);

        // Verificar que se disparó el evento
        Event::assertDispatched(SupplierProductPriceChanged::class, function ($event) use ($price) {
            return $event->price->id === $price->id;
        });
    }

    public function test_listener_enqueues_sync_job()
    {
        Queue::fake();

        $price = SupplierProductPrice::factory()->create(['price' => 100]);

        // Disparar evento
        event(new SupplierProductPriceChanged($price));

        // Verificar que se encoló el listener
        Queue::assertPushed(function ($job) use ($price) {
            return $job->displayName() === 'SyncPriceToErpListener'
                && $job->price->id === $price->id;
        });
    }

    public function test_full_sync_flow_end_to_end()
    {
        // 1. Crear precio
        $price = SupplierProductPrice::factory()->create(['price' => 100]);

        // 2. Actualizar precio (dispara observer)
        $price->update(['price' => 110]);

        // 3. Procesar queue
        $this->artisan('queue:work --once --queue=erp-sync');

        // 4. Verificar que se sincronizó
        $this->assertNotNull($price->fresh()->last_synced_at);
    }
}
```

---

## Troubleshooting

### Problema: Loops Infinitos

**Síntomas**:
- Cola `erp-sync` se llena indefinidamente
- Logs muestran múltiples sincronizaciones del mismo registro
- Alto consumo de CPU y memoria

**Diagnóstico**:
```bash
# Ver jobs en cola
php artisan queue:monitor erp-sync

# Ver logs de loops
grep "Sync already in progress" storage/logs/laravel.log
```

**Solución**:
1. Verificar que cache flags se están liberando correctamente
2. Ejecutar cleanup manual: `php artisan supplier:cleanup-sync-cache`
3. Revisar TTL de cache flags (debe ser > tiempo promedio de sync)

### Problema: Fallos Constantes de Conexión

**Síntomas**:
- Todos los syncs fallan con "Connection timeout"
- ERP no responde

**Diagnóstico**:
```bash
# Probar conexión con ERP
php artisan tinker
>>> $connection = oci_connect($username, $password, "$host:$port/$database");
>>> var_dump($connection);
```

**Solución**:
1. Verificar que ERP está online: `ping $ERP_HOST`
2. Verificar credenciales en `.env`
3. Verificar firewall no bloquea puerto 1521
4. Aumentar timeout en config: `SUPPLIER_ERP_TIMEOUT=60`

### Problema: Conflictos No Se Resuelven

**Síntomas**:
- Tabla `supplier_sync_conflicts` llena de registros con `resolved_at = NULL`
- Datos locales no coinciden con ERP

**Diagnóstico**:
```php
php artisan tinker
>>> use Modules\Supplier\Models\SupplierSyncConflict;
>>> SupplierSyncConflict::unresolved()->count();
>>> SupplierSyncConflict::unresolved()->first();
```

**Solución**:
1. Revisar estrategia de resolución: Debe ser `'erp_wins'`
2. Verificar que el método `registerConflict()` está siendo llamado
3. Revisar logs para ver si hay excepciones durante resolución

---

## Best Practices

1. **Monitorear DLQ Regularmente**
   - Revisar dashboard diariamente
   - Investigar fallos recurrentes
   - Limpiar registros resueltos

2. **Configurar Alertas**
   - Alerta si DLQ > 100 registros
   - Alerta si tasa de éxito < 95%
   - Alerta si conflictos no resueltos > 50

3. **Optimizar Workers**
   - 2-4 workers concurrentes para `erp-sync` queue
   - Timeout suficiente para operaciones lentas
   - Monitorear memoria y CPU de workers

4. **Backup Regular**
   - Backup diario de tablas:
     - `supplier_sync_failures`
     - `supplier_sync_conflicts`
   - Retención: 30 días

5. **Testing Pre-Deployment**
   - Ejecutar test suite completo: `php artisan test`
   - Probar conexión con ERP de staging
   - Verificar que queue workers están configurados

---

**Última actualización**: 2026-01-16
**Autor**: Equipo de Backend Alsernet

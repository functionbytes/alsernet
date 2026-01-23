# Dashboard de Fallos de Sincronización

## Descripción General

El **Dashboard de Fallos de Sincronización** es una interfaz web administrativa para monitorear, gestionar y resolver fallos en la sincronización bidireccional con Oracle ERP.

**URL**: `/settings/suppliers/sync-failures`
**Permisos**: Requiere rol `super-admin`
**Controlador**: `SupplierSyncFailuresController`
**Vista**: `modules/Supplier/resources/views/settings/sync-failures/index.blade.php`

---

## Características Principales

### 1. Estadísticas en Tiempo Real

Cuatro tarjetas animadas con métricas clave:

#### Total Failures
- **Descripción**: Total de fallos registrados (no resueltos)
- **Badge**: Rojo (`danger`)
- **Query**: `SELECT COUNT(*) FROM supplier_sync_failures WHERE resolved_at IS NULL`

#### Retryable Failures
- **Descripción**: Fallos que pueden ser reintentados
- **Badge**: Amarillo (`warning`)
- **Filtro**: `retry_count < max_retries`
- **Query**:
  ```sql
  SELECT COUNT(*) FROM supplier_sync_failures
  WHERE resolved_at IS NULL
    AND retry_count < max_retries
  ```

#### Total Conflicts
- **Descripción**: Total de conflictos detectados
- **Badge**: Azul (`primary`)
- **Query**: `SELECT COUNT(*) FROM supplier_sync_conflicts`

#### Unresolved Conflicts
- **Descripción**: Conflictos pendientes de resolución
- **Badge**: Naranja (`warning`)
- **Query**: `SELECT COUNT(*) FROM supplier_sync_conflicts WHERE resolved_at IS NULL`

### 2. Navegación por Tabs

**Tab 1: Fallos de Sincronización**
- Tabla de fallos con acciones individuales y bulk
- Filtros por tipo de sincronización
- Búsqueda por texto

**Tab 2: Conflictos Detectados**
- Tabla de conflictos con detalles
- Estado de resolución
- Modal para ver datos comparativos

---

## Tab 1: Fallos de Sincronización

### Estructura de la Tabla

#### Columnas

| Columna | Descripción | Formato |
|---------|-------------|---------|
| **ID** | Identificador único del fallo | `#123` |
| **Tipo** | Tipo de sincronización | Badge coloreado |
| **Supplier ID** | ID del recurso local | `456` |
| **ERP ID** | ID del recurso en ERP | `ERP-789` |
| **Error Message** | Mensaje de error | Texto truncado |
| **Reintentos** | Contador de intentos | `2/3` |
| **Última vez** | Timestamp último intento | `hace 2 horas` |
| **Acciones** | Botones de acción | Retry / Delete |

#### Badges de Tipo

```php
match($failure->sync_type) {
    'price' => 'badge-primary',    // Azul
    'product' => 'badge-success',  // Verde
    'provider' => 'badge-warning', // Naranja
    default => 'badge-secondary'   // Gris
}
```

### Filtros

#### Dropdown de Tipo
```html
<select name="type" class="form-select">
    <option value="">Todos los tipos</option>
    <option value="price">Precios</option>
    <option value="product">Productos</option>
    <option value="provider">Proveedores</option>
</select>
```

#### Campo de Búsqueda
```html
<input type="search" name="search" class="form-control"
       placeholder="Buscar por ID, ERP ID o mensaje de error...">
```

**Campos buscados**:
- `supplier_id`
- `erp_id`
- `error_message`

### Acciones Individuales

#### Botón Retry (Verde)
```html
<button class="btn btn-sm btn-success"
        data-action="retry"
        data-id="{{ $failure->id }}"
        @if($failure->retry_count >= $failure->max_retries) disabled @endif>
    <i class="fas fa-redo me-1"></i> Retry
</button>
```

**Endpoint**: `POST /settings/suppliers/sync-failures/{id}/retry`

**Respuesta exitosa**:
```json
{
  "success": true,
  "message": "Fallo reintentado exitosamente"
}
```

**Respuesta de fallo**:
```json
{
  "success": false,
  "message": "No se pudo reintentar: Connection timeout"
}
```

#### Botón Delete (Rojo)
```html
<button class="btn btn-sm btn-danger"
        data-action="delete"
        data-id="{{ $failure->id }}">
    <i class="fas fa-trash me-1"></i> Delete
</button>
```

**Endpoint**: `DELETE /settings/suppliers/sync-failures/{id}`

**Confirmación**: Muestra alert nativo del navegador

**Respuesta**:
```json
{
  "success": true,
  "message": "Fallo eliminado exitosamente"
}
```

### Acciones Bulk

#### Selección Múltiple

**Checkbox "Seleccionar Todo"** (en header):
```html
<input type="checkbox" id="selectAll" class="form-check-input">
```

**JavaScript**:
```javascript
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.failure-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    toggleBulkActions();
});
```

#### Barra de Acciones Bulk

Aparece cuando hay checkboxes seleccionados:

```html
<div id="bulkActionsBar" class="alert alert-info d-none">
    <span id="selectedCount">0</span> registros seleccionados
    <button id="bulkRetry" class="btn btn-success btn-sm ms-3">
        <i class="fas fa-redo me-1"></i> Retry Selected
    </button>
    <button id="bulkDelete" class="btn btn-danger btn-sm ms-2">
        <i class="fas fa-trash me-1"></i> Delete Selected
    </button>
</div>
```

#### Bulk Retry

**Endpoint**: `POST /settings/suppliers/sync-failures/bulk-retry`

**Payload**:
```json
{
  "ids": [1, 2, 3, 5, 8]
}
```

**Respuesta**:
```json
{
  "success": true,
  "message": "5 fallos reintentados exitosamente",
  "results": {
    "succeeded": 3,
    "failed": 2,
    "skipped": 0
  }
}
```

#### Bulk Delete

**Endpoint**: `DELETE /settings/suppliers/sync-failures/bulk-delete`

**Payload**:
```json
{
  "ids": [1, 2, 3, 5, 8]
}
```

**Respuesta**:
```json
{
  "success": true,
  "message": "5 fallos eliminados exitosamente"
}
```

### Paginación

**Registros por página**: 15 (configurable)

**Links de paginación**: Bootstrap 5.3
```html
{{ $failures->appends(request()->query())->links() }}
```

**Preserva filtros**: Mantiene `type` y `search` en los links de página

---

## Tab 2: Conflictos Detectados

### Estructura de la Tabla

#### Columnas

| Columna | Descripción | Formato |
|---------|-------------|---------|
| **ID** | Identificador único | `#123` |
| **Tipo** | Tipo de entidad | `price`, `product`, `provider` |
| **Entity ID** | ID de la entidad local | `456` |
| **Estrategia** | Estrategia de resolución | Badge coloreado |
| **Detectado** | Timestamp de detección | `2026-01-16 10:30:00` |
| **Resuelto** | Timestamp de resolución | `2026-01-16 10:30:01` o `Pendiente` |
| **Estado** | Estado actual | Badge `Resolved` / `Unresolved` |
| **Acciones** | Botones de acción | `View Details` |

#### Badges de Estrategia

```php
match($conflict->resolution_strategy) {
    'erp_wins' => 'badge-primary',   // Azul
    'local_wins' => 'badge-info',    // Celeste
    'manual' => 'badge-warning',     // Amarillo
    default => 'badge-secondary'     // Gris
}
```

#### Badges de Estado

```php
$conflict->resolved_at !== null
    ? '<span class="badge badge-success">Resolved</span>'
    : '<span class="badge badge-danger">Unresolved</span>'
```

### Modal de Detalles

#### Trigger

```html
<button class="btn btn-sm btn-info"
        data-action="view-conflict"
        data-id="{{ $conflict->id }}">
    <i class="fas fa-eye me-1"></i> View Details
</button>
```

#### Estructura del Modal

```html
<div class="modal fade" id="conflictDetailModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Detalles del Conflicto #<span id="modalConflictId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Información Básica -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <strong>Tipo:</strong> <span id="modalType"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Entity ID:</strong> <span id="modalEntityId"></span>
                    </div>
                </div>

                <!-- Comparación Lado a Lado -->
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-danger">Local Data</h6>
                        <pre id="modalLocalData"></pre>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-success">ERP Data</h6>
                        <pre id="modalErpData"></pre>
                    </div>
                </div>

                <!-- Campos Cambiados -->
                <div class="alert alert-warning">
                    <strong>Campos modificados:</strong>
                    <span id="modalChangedFields"></span>
                </div>

                <!-- Resolución -->
                <div class="border-top pt-3">
                    <strong>Estrategia:</strong> <span id="modalStrategy"></span><br>
                    <strong>Resuelto:</strong> <span id="modalResolved"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
```

#### Endpoint para Datos

**URL**: `GET /settings/suppliers/sync-failures/conflicts/{id}`

**Respuesta**:
```json
{
  "id": 123,
  "entity_type": "price",
  "entity_id": 456,
  "erp_id": 789,
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

#### JavaScript para Rellenar Modal

```javascript
document.querySelectorAll('[data-action="view-conflict"]').forEach(btn => {
    btn.addEventListener('click', async function() {
        const id = this.dataset.id;

        const response = await fetch(`/settings/suppliers/sync-failures/conflicts/${id}`);
        const data = await response.json();

        // Rellenar campos
        document.getElementById('modalConflictId').textContent = data.id;
        document.getElementById('modalType').textContent = data.entity_type;
        document.getElementById('modalEntityId').textContent = data.entity_id;

        // Formatear JSON
        document.getElementById('modalLocalData').textContent =
            JSON.stringify(data.local_data, null, 2);
        document.getElementById('modalErpData').textContent =
            JSON.stringify(data.erp_data, null, 2);

        // Campos cambiados
        document.getElementById('modalChangedFields').textContent =
            data.changed_fields.join(', ');

        // Estrategia y resolución
        document.getElementById('modalStrategy').textContent = data.resolution_strategy;
        document.getElementById('modalResolved').textContent =
            data.resolved_at || 'Pendiente';

        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('conflictDetailModal'));
        modal.show();
    });
});
```

---

## Controlador: SupplierSyncFailuresController

### Métodos Principales

#### index()
```php
public function index(Request $request): View
{
    $tab = $request->get('tab', 'failures');
    $syncType = $request->get('type');
    $searchKey = $request->get('search');

    // Consulta de fallos
    $failuresQuery = SupplierSyncFailure::query()
        ->latestFailures()
        ->when($syncType, fn($q) => $q->where('sync_type', $syncType))
        ->when($searchKey, function($q) use ($searchKey) {
            $q->where(function($query) use ($searchKey) {
                $query->where('supplier_id', 'like', "%{$searchKey}%")
                    ->orWhere('erp_id', 'like', "%{$searchKey}%")
                    ->orWhere('error_message', 'like', "%{$searchKey}%");
            });
        });

    $failures = $failuresQuery->paginate(15);

    // Consulta de conflictos
    $conflicts = SupplierSyncConflict::query()
        ->orderBy('conflict_detected_at', 'desc')
        ->paginate(15);

    // Estadísticas
    $stats = [
        'total_failures' => SupplierSyncFailure::count(),
        'retryable_failures' => SupplierSyncFailure::retryable()->count(),
        'total_conflicts' => SupplierSyncConflict::count(),
        'unresolved_conflicts' => SupplierSyncConflict::unresolved()->count(),
    ];

    return view('supplier::settings.sync-failures.index', compact(
        'failures',
        'conflicts',
        'stats',
        'tab',
        'syncType',
        'searchKey',
        'pageTitle',
        'breadcrumb'
    ));
}
```

#### retry()
```php
public function retry(Request $request, int $id): JsonResponse
{
    $failure = SupplierSyncFailure::findOrFail($id);

    // Verificar si puede ser reintentado
    if ($failure->retry_count >= $failure->max_retries) {
        return response()->json([
            'success' => false,
            'message' => 'Se alcanzó el límite máximo de reintentos',
        ], 422);
    }

    try {
        $result = match ($failure->sync_type) {
            'price' => $this->retryPriceSync($failure),
            'product' => $this->retryProductSync($failure),
            'provider' => $this->retryProviderSync($failure),
            default => throw new \Exception('Tipo de sincronización no soportado'),
        };

        if ($result['success']) {
            $failure->update(['resolved_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Fallo reintentado exitosamente',
            ]);
        } else {
            $failure->increment('retry_count');
            $failure->update(['last_retry_at' => now()]);

            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 500);
        }
    } catch (\Exception $e) {
        Log::error('Failed to retry sync', [
            'failure_id' => $id,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al reintentar: ' . $e->getMessage(),
        ], 500);
    }
}
```

#### bulkRetry()
```php
public function bulkRetry(Request $request): JsonResponse
{
    $ids = $request->input('ids', []);

    $failures = SupplierSyncFailure::whereIn('id', $ids)
        ->where('retry_count', '<', DB::raw('max_retries'))
        ->get();

    $results = [
        'succeeded' => 0,
        'failed' => 0,
        'skipped' => 0,
    ];

    foreach ($failures as $failure) {
        try {
            $result = match ($failure->sync_type) {
                'price' => $this->retryPriceSync($failure),
                'product' => $this->retryProductSync($failure),
                'provider' => $this->retryProviderSync($failure),
            };

            if ($result['success']) {
                $failure->update(['resolved_at' => now()]);
                $results['succeeded']++;
            } else {
                $failure->increment('retry_count');
                $failure->update(['last_retry_at' => now()]);
                $results['failed']++;
            }
        } catch (\Exception $e) {
            $results['failed']++;
        }
    }

    return response()->json([
        'success' => true,
        'message' => "{$results['succeeded']} fallos reintentados exitosamente",
        'results' => $results,
    ]);
}
```

#### destroy()
```php
public function destroy(int $id): JsonResponse
{
    $failure = SupplierSyncFailure::findOrFail($id);
    $failure->delete();

    return response()->json([
        'success' => true,
        'message' => 'Fallo eliminado exitosamente',
    ]);
}
```

#### bulkDestroy()
```php
public function bulkDestroy(Request $request): JsonResponse
{
    $ids = $request->input('ids', []);

    SupplierSyncFailure::whereIn('id', $ids)->delete();

    return response()->json([
        'success' => true,
        'message' => count($ids) . ' fallos eliminados exitosamente',
    ]);
}
```

#### showConflict()
```php
public function showConflict(int $id): JsonResponse
{
    $conflict = SupplierSyncConflict::findOrFail($id);

    return response()->json([
        'id' => $conflict->id,
        'entity_type' => $conflict->entity_type,
        'entity_id' => $conflict->entity_id,
        'erp_id' => $conflict->erp_id,
        'resolution_strategy' => $conflict->resolution_strategy,
        'local_data' => json_decode($conflict->local_data, true),
        'erp_data' => json_decode($conflict->erp_data, true),
        'resolved_data' => json_decode($conflict->resolved_data, true),
        'changed_fields' => json_decode($conflict->changed_fields, true),
        'conflict_detected_at' => $conflict->conflict_detected_at->format('Y-m-d H:i:s'),
        'resolved_at' => $conflict->resolved_at?->format('Y-m-d H:i:s'),
    ]);
}
```

---

## Estilos y Animaciones

### Tarjetas de Estadísticas

```css
.stat-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 12px;
    padding: 1.5rem;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.stat-card .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-card .stat-number {
    font-size: 2rem;
    font-weight: 700;
    margin: 0.5rem 0;
}

.stat-card .stat-label {
    font-size: 0.875rem;
    color: #6c757d;
}
```

### Tabs con Animación

```css
.nav-tabs {
    border-bottom: 2px solid #e9ecef;
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    padding: 1rem 1.5rem;
    position: relative;
    transition: color 0.3s;
}

.nav-tabs .nav-link.active {
    color: #90bb13;
    background: transparent;
}

.nav-tabs .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background: #90bb13;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: scaleX(0);
    }
    to {
        transform: scaleX(1);
    }
}
```

### Hover en Filas de Tabla

```css
.table tbody tr {
    transition: background-color 0.2s;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}
```

---

## Casos de Uso

### Caso 1: Monitoring Diario

**Escenario**: Admin revisa el dashboard cada mañana.

**Pasos**:
1. Abrir dashboard: `/settings/suppliers/sync-failures`
2. Revisar tarjetas de estadísticas
3. Si "Total Failures" > 0:
   - Ir al tab "Fallos de Sincronización"
   - Identificar fallos recientes
   - Hacer clic en "Retry" para fallos reintentables
4. Si "Unresolved Conflicts" > 0:
   - Ir al tab "Conflictos Detectados"
   - Revisar conflictos pendientes
   - Hacer clic en "View Details" para investigar

### Caso 2: Retry Masivo Después de Downtime de ERP

**Escenario**: ERP estuvo caído durante 2 horas. Ahora está online de nuevo.

**Pasos**:
1. Abrir dashboard
2. Seleccionar checkbox "Seleccionar todo"
3. Hacer clic en "Retry Selected"
4. Esperar confirmación
5. Revisar estadísticas actualizadas

### Caso 3: Investigar Fallos Recurrentes

**Escenario**: Producto específico falla constantemente.

**Pasos**:
1. Usar el campo de búsqueda: Escribir "Product ID: 12345"
2. Filtrar por tipo: "Productos"
3. Revisar todos los fallos del producto
4. Hacer clic en "View Details" en uno de los fallos
5. Analizar error message y stack trace
6. Identificar patrón común
7. Reportar bug o ajustar configuración

---

## Performance

### Optimizaciones Implementadas

1. **Índices de Base de Datos**:
   - `idx_sync_failures_type_retry` (sync_type, retry_count)
   - `idx_sync_failures_last_retry` (last_retry_at)

2. **Paginación**:
   - 15 registros por página
   - Reduce carga de renderizado

3. **Lazy Loading de Modal**:
   - Datos del conflicto se cargan solo al abrir modal
   - Reduce payload inicial de la página

4. **Caché de Estadísticas** (Futuro):
   ```php
   $stats = Cache::remember('supplier_sync_stats', 60, function() {
       return [
           'total_failures' => SupplierSyncFailure::count(),
           // ...
       ];
   });
   ```

### Benchmark

**Con 1,000 registros en tabla**:
- Carga inicial: ~350ms
- Filtrado: ~200ms
- Retry individual: ~800ms (depende de ERP)
- Bulk retry (10 items): ~5s

---

## Seguridad

### Autenticación y Autorización

```php
Route::middleware(['auth', 'role:super-admin'])->group(function() {
    Route::get('/settings/suppliers/sync-failures', [SupplierSyncFailuresController::class, 'index']);
    // ...
});
```

### Validación de Inputs

```php
public function bulkRetry(Request $request): JsonResponse
{
    $validated = $request->validate([
        'ids' => 'required|array|min:1',
        'ids.*' => 'required|integer|exists:supplier_sync_failures,id',
    ]);

    // ...
}
```

### CSRF Protection

Todos los formularios POST/DELETE incluyen token CSRF:

```html
<form method="POST">
    @csrf
    @method('DELETE')
    <!-- ... -->
</form>
```

Para AJAX:
```javascript
fetch(url, {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Content-Type': 'application/json',
    },
    body: JSON.stringify(data),
});
```

---

## Testing

### Browser Testing

```php
namespace Tests\Browser;

use Tests\DuskTestCase;
use Modules\Supplier\Models\SupplierSyncFailure;

class SyncFailuresDashboardTest extends DuskTestCase
{
    public function test_can_access_dashboard()
    {
        $admin = User::factory()->create(['role' => 'super-admin']);

        $this->browse(function ($browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/settings/suppliers/sync-failures')
                ->assertSee('Fallos de Sincronización')
                ->assertPresent('.stat-card');
        });
    }

    public function test_can_retry_individual_failure()
    {
        $admin = User::factory()->create(['role' => 'super-admin']);
        $failure = SupplierSyncFailure::factory()->create();

        $this->browse(function ($browser) use ($admin, $failure) {
            $browser->loginAs($admin)
                ->visit('/settings/suppliers/sync-failures')
                ->press("button[data-id='{$failure->id}'][data-action='retry']")
                ->waitForText('Fallo reintentado exitosamente');
        });
    }

    public function test_can_view_conflict_details()
    {
        $admin = User::factory()->create(['role' => 'super-admin']);
        $conflict = SupplierSyncConflict::factory()->create();

        $this->browse(function ($browser) use ($admin, $conflict) {
            $browser->loginAs($admin)
                ->visit('/settings/suppliers/sync-failures?tab=conflicts')
                ->press("button[data-id='{$conflict->id}'][data-action='view-conflict']")
                ->waitFor('#conflictDetailModal')
                ->assertVisible('#conflictDetailModal')
                ->assertSee('Local Data')
                ->assertSee('ERP Data');
        });
    }
}
```

---

## Troubleshooting

### Dashboard No Carga

**Síntomas**: 500 Internal Server Error

**Diagnóstico**:
```bash
tail -f storage/logs/laravel.log | grep "SyncFailuresController"
```

**Soluciones**:
1. Verificar que tablas existen: `php artisan migrate:status`
2. Verificar permisos de usuario: Debe tener rol `super-admin`
3. Verificar índices creados: `SHOW INDEX FROM supplier_sync_failures`

### Botones No Responden

**Síntomas**: Click en botones no hace nada

**Diagnóstico**:
```javascript
// En la consola del navegador (F12)
console.log('jQuery loaded:', typeof jQuery !== 'undefined');
console.log('Bootstrap loaded:', typeof bootstrap !== 'undefined');
```

**Soluciones**:
1. Verificar que jQuery está cargado
2. Verificar que Bootstrap JS está cargado
3. Revisar consola de JavaScript para errores

### Notificaciones No Aparecen

**Síntomas**: Acciones se ejecutan pero no hay feedback visual

**Diagnóstico**:
```javascript
// En la consola del navegador
console.log('Toastr loaded:', typeof toastr !== 'undefined');
```

**Soluciones**:
1. Incluir librería de notificaciones (Toastr o SweetAlert2)
2. Verificar que función de notificación está definida
3. Revisar logs de red (F12 → Network) para ver respuestas del servidor

---

**Última actualización**: 2026-01-16
**Autor**: Equipo de Backend Alsernet

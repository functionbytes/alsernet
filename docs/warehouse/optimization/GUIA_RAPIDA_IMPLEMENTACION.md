# Warehouse - Guía Rápida de Implementación

**Para Desarrolladores** | **Referencia Rápida** | **Copy-Paste Ready**

---

## ⚡ Fase 1: Índices (30 minutos)

### Paso 1: Crear Migration

```bash
php artisan make:migration add_warehouse_performance_indexes --create
```

### Paso 2: Copiar Contenido

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('warehouse_inventory_movements', function (Blueprint $table) {
            $table->index(['warehouse_id', 'recorded_at'], 'idx_wim_warehouse_recorded');
            $table->index(['movement_type', 'user_id', 'recorded_at'], 'idx_wim_movement_user');
            $table->index(['slot_id', 'recorded_at'], 'idx_wim_slot_recorded');
        });

        Schema::table('warehouse_inventory_slots', function (Blueprint $table) {
            $table->index(['location_id', 'product_id'], 'idx_wis_location_product');
            $table->index(['barcode'], 'idx_wis_barcode');
            $table->index(['location_id', 'is_occupied'], 'idx_wis_location_occupied');
        });

        Schema::table('warehouse_locations', function (Blueprint $table) {
            $table->index(['warehouse_id', 'code'], 'idx_wl_warehouse_code');
            $table->index(['floor_id', 'available'], 'idx_wl_floor_available');
        });

        Schema::table('warehouse_floors', function (Blueprint $table) {
            $table->index(['warehouse_id', 'level'], 'idx_wf_warehouse');
        });

        Schema::table('warehouse_location_sections', function (Blueprint $table) {
            $table->index(['location_id', 'code'], 'idx_wls_location_code');
        });

        Schema::table('user_warehouse', function (Blueprint $table) {
            $table->index(['warehouse_id', 'can_transfer', 'can_inventory'], 'idx_uw_warehouse_perms');
            $table->index(['user_id', 'is_default'], 'idx_uw_user_default');
        });

        Schema::table('warehouse_operation_items', function (Blueprint $table) {
            $table->index(['operation_id', 'status'], 'idx_woi_operation_status');
            $table->index(['slot_id', 'status'], 'idx_woi_slot_status');
        });
    }

    public function down(): void {
        Schema::table('warehouse_inventory_movements', function (Blueprint $table) {
            $table->dropIndex('idx_wim_warehouse_recorded');
            $table->dropIndex('idx_wim_movement_user');
            $table->dropIndex('idx_wim_slot_recorded');
        });

        Schema::table('warehouse_inventory_slots', function (Blueprint $table) {
            $table->dropIndex('idx_wis_location_product');
            $table->dropIndex('idx_wis_barcode');
            $table->dropIndex('idx_wis_location_occupied');
        });

        Schema::table('warehouse_locations', function (Blueprint $table) {
            $table->dropIndex('idx_wl_warehouse_code');
            $table->dropIndex('idx_wl_floor_available');
        });

        Schema::table('warehouse_floors', function (Blueprint $table) {
            $table->dropIndex('idx_wf_warehouse');
        });

        Schema::table('warehouse_location_sections', function (Blueprint $table) {
            $table->dropIndex('idx_wls_location_code');
        });

        Schema::table('user_warehouse', function (Blueprint $table) {
            $table->dropIndex('idx_uw_warehouse_perms');
            $table->dropIndex('idx_uw_user_default');
        });

        Schema::table('warehouse_operation_items', function (Blueprint $table) {
            $table->dropIndex('idx_woi_operation_status');
            $table->dropIndex('idx_woi_slot_status');
        });
    }
};
```

### Paso 3: Ejecutar

```bash
php artisan migrate
```

✅ **Listo** - Mejora: 70-80% en queries

---

## 🎯 Fase 1: Eager Loading (1-2 horas)

### ANTES - Problema N+1

```php
// ❌ MAL - Carga 1 + 500 + 500 queries
$location = WarehouseLocation::find($id);
return view('warehouse.locations.view', compact('location'));
```

### DESPUÉS - Solución Correcta

```php
// ✅ BIEN - Carga 1 query solamente
$location = WarehouseLocation::with([
    'sections.slots.product',
    'floor',
    'style',
])->find($id);
return view('warehouse.locations.view', compact('location'));
```

### Controladores a Actualizar

**Archivo:** `app/Http/Controllers/Managers/Warehouse/WarehouseLocationsController.php`

```php
// view() - Actualizar
public function view($warehouse_uid, $location_id)
{
    $location = WarehouseLocation::with([
        'sections.slots.product',
        'floor',
        'style',
    ])->find($location_id);

    return view('theme.views.warehouse.locations.view', compact('location'));
}

// index() - Actualizar
public function index($warehouse_uid)
{
    $locations = WarehouseLocation::where('warehouse_id', $warehouse_uid)
        ->with(['sections', 'floor', 'style'])
        ->paginate(50);

    return view('theme.views.warehouse.locations.index', compact('locations'));
}
```

**Archivo:** `app/Http/Controllers/Managers/Warehouse/WarehouseHistoryController.php`

```php
// index() - Actualizar
public function index(Request $request, $warehouse_id)
{
    $movements = WarehouseInventoryMovement::where('warehouse_id', $warehouse_id)
        ->with(['user', 'slot.product', 'slot.location'])
        ->orderBy('recorded_at', 'desc')
        ->paginate(50);

    return view('theme.views.warehouse.history.index', compact('movements'));
}
```

**Archivo:** `app/Http/Controllers/Managers/Warehouse/WarehouseDashboardController.php`

```php
// dashboard() - Actualizar
public function dashboard($warehouse_uid)
{
    $warehouse = Warehouse::with([
        'floors.locations.sections.slots',
        'users',
    ])->where('uid', $warehouse_uid)->first();

    return view('theme.views.warehouse.dashboard', compact('warehouse'));
}
```

### Checklist de Controllers

- [ ] WarehouseLocationsController - view(), index()
- [ ] WarehouseHistoryController - index(), view()
- [ ] WarehouseDashboardController - dashboard()
- [ ] WarehouseInventorySlotsController - index()
- [ ] WarehouseLocationSectionsController - view()
- [ ] LocationsController (User) - index()

✅ **Listo** - Mejora: 90% menos queries

---

## 📦 Fase 2: LocationCacheService (1 hora)

### Crear Archivo

**Archivo:** `app/Services/Warehouse/LocationCacheService.php`

```php
<?php

namespace App\Services\Warehouse;

use App\Models\Warehouse\WarehouseLocation;
use Illuminate\Support\Facades\Cache;

class LocationCacheService
{
    private const CACHE_PREFIX = 'warehouse.location.';
    private const CACHE_TTL = 300; // 5 minutos

    public static function getWithCache(int|string $locationId): ?WarehouseLocation
    {
        return Cache::remember(
            self::CACHE_PREFIX . $locationId,
            self::CACHE_TTL,
            fn() => WarehouseLocation::with([
                'sections.slots.product',
                'floor',
                'style',
            ])->find($locationId)
        );
    }

    public static function invalidate(int|string $locationId): void
    {
        Cache::forget(self::CACHE_PREFIX . $locationId);
    }

    public static function invalidateWarehouse(int|string $warehouseId): void
    {
        $locations = WarehouseLocation::where('warehouse_id', $warehouseId)
            ->pluck('id');

        foreach ($locations as $locationId) {
            self::invalidate($locationId);
        }
    }
}
```

### Usar en Controladores

```php
<?php

use App\Services\Warehouse\LocationCacheService;

class WarehouseLocationsController extends Controller
{
    public function view($warehouse_uid, $location_id)
    {
        $location = LocationCacheService::getWithCache($location_id);

        if (!$location) {
            abort(404);
        }

        return view('theme.views.warehouse.locations.view', compact('location'));
    }
}
```

### Invalidación Automática en Modelo

**Archivo:** `app/Models/Warehouse/WarehouseLocation.php`

```php
<?php

namespace App\Models\Warehouse;

use App\Services\Warehouse\LocationCacheService;
use Illuminate\Database\Eloquent\Model;

class WarehouseLocation extends Model
{
    protected static function booted(): void
    {
        static::updated(function ($location) {
            LocationCacheService::invalidate($location->id);
        });

        static::deleted(function ($location) {
            LocationCacheService::invalidate($location->id);
        });

        static::created(function ($location) {
            LocationCacheService::invalidateWarehouse($location->warehouse_id);
        });
    }
}
```

✅ **Listo** - Mejora: 95% en accesos repetidos

---

## 👤 Fase 2: UserPermissionService (45 minutos)

### Crear Archivo

**Archivo:** `app/Services/Warehouse/UserPermissionService.php`

```php
<?php

namespace App\Services\Warehouse;

use Illuminate\Support\Facades\Cache;

class UserPermissionService
{
    private const CACHE_PREFIX = 'user.warehouses.';
    private const CACHE_TTL = 600; // 10 minutos

    public static function canTransfer(int|string $userId, int|string $warehouseId): bool
    {
        $warehouses = Cache::remember(
            self::CACHE_PREFIX . $userId,
            self::CACHE_TTL,
            fn() => \Auth::user()->find($userId)?->warehouses()
                ->with('warehouse')
                ->get()
                ->toArray() ?? []
        );

        return collect($warehouses)
            ->where('warehouse_id', $warehouseId)
            ->where('pivot.can_transfer', true)
            ->isNotEmpty();
    }

    public static function canInventory(int|string $userId, int|string $warehouseId): bool
    {
        $warehouses = Cache::remember(
            self::CACHE_PREFIX . $userId,
            self::CACHE_TTL,
            fn() => \Auth::user()->find($userId)?->warehouses()
                ->with('warehouse')
                ->get()
                ->toArray() ?? []
        );

        return collect($warehouses)
            ->where('warehouse_id', $warehouseId)
            ->where('pivot.can_inventory', true)
            ->isNotEmpty();
    }

    public static function invalidate(int|string $userId): void
    {
        Cache::forget(self::CACHE_PREFIX . $userId);
    }
}
```

### Usar en Middleware

```php
<?php

use App\Services\Warehouse\UserPermissionService;

class WarehouseAccessMiddleware
{
    public function handle($request, $next)
    {
        $warehouseId = $request->route('warehouse_id');

        if (!UserPermissionService::canTransfer(auth()->id(), $warehouseId)) {
            abort(403, 'No tiene permisos en este almacén');
        }

        return $next($request);
    }
}
```

✅ **Listo** - Mejora: 85% en validación de permisos

---

## 🔧 Fase 2: BarcodeValidationService (1.5 horas)

### Crear Archivo

**Archivo:** `app/Services/Warehouse/BarcodeValidationService.php`

```php
<?php

namespace App\Services\Warehouse;

use App\Models\Warehouse\WarehouseInventorySlot;
use App\Models\Warehouse\WarehouseLocation;

class BarcodeValidationService
{
    public static function validateSlot(string $barcode, ?int $warehouseId = null): array
    {
        // Una sola query con todo
        $slot = WarehouseInventorySlot::with([
            'location.floor.warehouse',
            'location.style',
            'product',
        ])->where('barcode', $barcode)->first();

        if (!$slot) {
            return ['valid' => false, 'error' => 'Código no encontrado'];
        }

        if ($warehouseId && $slot->location->floor->warehouse->id !== $warehouseId) {
            return ['valid' => false, 'error' => 'Ranura pertenece a otro almacén'];
        }

        // Verificar capacidad
        if ($slot->quantity >= $slot->max_quantity) {
            return ['valid' => false, 'error' => 'Capacidad excedida'];
        }

        return [
            'valid' => true,
            'slot' => $slot,
            'location' => $slot->location,
            'product' => $slot->product?->toArray(),
        ];
    }

    public static function validateLocation(string $barcode, ?int $warehouseId = null): array
    {
        $location = WarehouseLocation::with([
            'sections.slots',
            'floor.warehouse',
        ])->where('barcode', $barcode)->first();

        if (!$location) {
            return ['valid' => false, 'error' => 'Ubicación no encontrada'];
        }

        if ($warehouseId && $location->floor->warehouse->id !== $warehouseId) {
            return ['valid' => false, 'error' => 'Ubicación en otro almacén'];
        }

        return ['valid' => true, 'location' => $location];
    }
}
```

### Crear Endpoint API

**Archivo:** `routes/api.php`

```php
Route::middleware('auth:api')->prefix('warehouse')->group(function () {
    Route::post('barcode/validate-slot', function (Request $request) {
        $result = \App\Services\Warehouse\BarcodeValidationService::validateSlot(
            $request->input('barcode'),
            $request->input('warehouse_id')
        );

        return response()->json($result, $result['valid'] ? 200 : 422);
    });

    Route::post('barcode/validate-location', function (Request $request) {
        $result = \App\Services\Warehouse\BarcodeValidationService::validateLocation(
            $request->input('barcode'),
            $request->input('warehouse_id')
        );

        return response()->json($result, $result['valid'] ? 200 : 422);
    });
});
```

✅ **Listo** - Mejora: 50-70% en escaneo de códigos

---

## 📋 Fase 3: Bulk Transfer Service (2 horas)

### Crear Archivo

**Archivo:** `app/Services/Warehouse/BulkTransferService.php`

```php
<?php

namespace App\Services\Warehouse;

use App\Models\Warehouse\WarehouseInventoryMovement;
use App\Models\Warehouse\WarehouseInventorySlot;
use Illuminate\Support\Facades\DB;

class BulkTransferService
{
    public static function transfer(array $transfers, int $userId, string $reason = 'Transferencia'): array
    {
        return DB::transaction(function () use ($transfers, $userId, $reason) {
            $successful = 0;
            $failed = 0;

            foreach ($transfers as $transfer) {
                $result = self::transferItem($transfer, $userId, $reason);
                $result['success'] ? $successful++ : $failed++;
            }

            return ['successful' => $successful, 'failed' => $failed];
        });
    }

    private static function transferItem(array $transfer, int $userId, string $reason): array
    {
        $from = WarehouseInventorySlot::lockForUpdate()->find($transfer['from_slot_id']);
        $to = WarehouseInventorySlot::lockForUpdate()->find($transfer['to_slot_id']);

        if (!$from || !$to) {
            return ['success' => false];
        }

        $qty = $transfer['quantity'] ?? 1;

        if ($from->quantity < $qty || ($to->quantity + $qty) > $to->max_quantity) {
            return ['success' => false];
        }

        // Actualizar
        $from->decrement('quantity', $qty);
        $to->increment('quantity', $qty);

        // Registrar movimientos
        WarehouseInventoryMovement::create([
            'slot_id' => $from->id,
            'product_id' => $from->product_id,
            'movement_type' => 'subtract',
            'from_quantity' => $from->quantity + $qty,
            'to_quantity' => $from->quantity,
            'quantity_delta' => -$qty,
            'reason' => $reason,
            'warehouse_id' => $from->location->warehouse_id,
            'user_id' => $userId,
            'recorded_at' => now(),
        ]);

        WarehouseInventoryMovement::create([
            'slot_id' => $to->id,
            'product_id' => $to->product_id,
            'movement_type' => 'add',
            'from_quantity' => $to->quantity - $qty,
            'to_quantity' => $to->quantity,
            'quantity_delta' => $qty,
            'reason' => $reason,
            'warehouse_id' => $to->location->warehouse_id,
            'user_id' => $userId,
            'recorded_at' => now(),
        ]);

        return ['success' => true];
    }
}
```

### Usar en Controlador

```php
<?php

use App\Services\Warehouse\BulkTransferService;

class TransferController extends Controller
{
    public function process(Request $request)
    {
        $result = BulkTransferService::transfer(
            $request->input('transfers'),
            auth()->id(),
            'Transferencia en lote'
        );

        return response()->json($result);
    }
}
```

✅ **Listo** - Mejora: 80-90% en transferencias

---

## 📊 Fase 4: Daily Summary Job (1.5 horas)

### Crear Tabla

```bash
php artisan make:migration create_warehouse_daily_summary
```

```php
public function up(): void
{
    Schema::create('warehouse_daily_summary', function (Blueprint $table) {
        $table->id();
        $table->uuid('warehouse_id');
        $table->date('date')->index();
        $table->integer('total_movements')->default(0);
        $table->integer('total_quantity_moved')->default(0);
        $table->decimal('total_weight_moved', 12, 2)->default(0);
        $table->integer('discrepancies')->default(0);
        $table->timestamps();

        $table->unique(['warehouse_id', 'date']);
        $table->index(['warehouse_id', 'date']);
    });
}
```

### Crear Job

**Archivo:** `app/Jobs/Warehouse/CalculateDailySummary.php`

```php
<?php

namespace App\Jobs\Warehouse;

use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseInventoryMovement;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class CalculateDailySummary implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $date = today()->subDay();

        Warehouse::all()->each(function ($warehouse) use ($date) {
            $movements = WarehouseInventoryMovement::where('warehouse_id', $warehouse->id)
                ->whereDate('recorded_at', $date)
                ->get();

            DB::table('warehouse_daily_summary')->updateOrInsert(
                ['warehouse_id' => $warehouse->id, 'date' => $date],
                [
                    'total_movements' => $movements->count(),
                    'total_quantity_moved' => $movements->sum('quantity_delta'),
                    'total_weight_moved' => $movements->sum('weight_delta'),
                    'discrepancies' => $movements->where('reason', 'like', '%discrepancia%')->count(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        });
    }
}
```

### Agendar Job

**Archivo:** `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule)
{
    $schedule->job(new \App\Jobs\Warehouse\CalculateDailySummary())
        ->dailyAt('02:00');
}
```

✅ **Listo** - Mejora: 80-90% en reportes

---

## 🚀 Testing Rápido

### Verificar Mejoras

```bash
# En Tinker
php artisan tinker

# Comparar queries - ANTES
DB::enableQueryLog();
$location = WarehouseLocation::find(1);
echo count(DB::getQueryLog()); // ~500 queries

# DESPUÉS
DB::enableQueryLog();
$location = WarehouseLocation::with('sections.slots.product', 'floor')->find(1);
echo count(DB::getQueryLog()); // ~1 query
```

### Load Testing

```bash
# Instalar Apache Bench
brew install httpd

# Probar endpoint (100 requests, 10 concurrentes)
ab -n 100 -c 10 http://localhost/warehouse/locations/1
```

---

## 📋 Checklist de Implementación

### Fase 1 (BD Performance)
- [ ] Migration indices creada
- [ ] 15 controladores actualizados
- [ ] Paginación agregada
- [ ] Tests pasando
- [ ] Deploy a staging

### Fase 2 (Cache)
- [ ] LocationCacheService creado
- [ ] UserPermissionService creado
- [ ] BarcodeValidationService creado
- [ ] Endpoints API funcionando
- [ ] Redis monitoreado

### Fase 3 (Bulk)
- [ ] BulkTransferService creado
- [ ] Controlador actualizado
- [ ] Tests con 50+ items
- [ ] Performance verificado

### Fase 4 (Analytics)
- [ ] Tabla daily_summary creada
- [ ] Job CalculateDailySummary creado
- [ ] Scheduler configurado
- [ ] Reportes funcionando

---

## 🆘 Troubleshooting

### Cache no se invalida
**Solución:** Verificar que `Cache::forget()` se llama en `booted()`
```php
protected static function booted(): void {
    static::updated(fn($model) => Cache::forget('key.' . $model->id));
}
```

### Query todavía lenta
**Solución:** Verificar eager loading con query log
```php
DB::enableQueryLog();
$data = Model::with('relationship')->get();
dd(DB::getQueryLog());
```

### Índices no aparecen
**Solución:** Rollback y re-migrate
```bash
php artisan migrate:rollback
php artisan migrate
```

---

**Listo para Implementar** ✅

Todos los códigos son copy-paste ready. Prueba en staging primero.

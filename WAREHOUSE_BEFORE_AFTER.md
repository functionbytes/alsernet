# Comparativa: Antes vs Después de la Unificación

## 📌 Ejemplo 1: Listar Almacenes

### ❌ ANTES (InventariesController - Con Bugs)
```php
// Ruta: /manager/inventaries
// Controlador: app/Http/Controllers/Managers/Inventaries/InventariesController.php

public function index(Request $request){
    $searchKey = null ?? $request->search;  // Bug: Siempre es null
    $available = null ?? $request->available;  // Bug: Siempre es null

    $inventaries = Warehouse::latest();

    if ($searchKey != null) {  // Nunca se ejecuta por bug anterior
        $inventaries = $inventaries->where('title', 'like', '%' . $searchKey . '%');
    }

    if ($available != null) {  // Nunca se ejecuta por bug anterior
        $inventaries = $inventaries->where('available', $available);
    }

    $inventaries = $inventaries->paginate(paginationNumber());

    return view('managers.views.warehouses.warehouses.index')->with([
        'inventaries' => $inventaries,
        'available' => $available,
        'searchKey' => $searchKey,
    ]);
}
```

### ✅ DESPUÉS (WarehouseController - Corregido)
```php
// Ruta: /manager/warehouse/ o /manager/warehouse/warehouses
// Controlador: app/Http/Controllers/Managers/Warehouse/WarehouseController.php

public function index(Request $request)
{
    $searchKey = $request->search ?? null;  // ✅ Sintaxis correcta
    $available = $request->available ?? null;  // ✅ Sintaxis correcta

    $warehouses = Warehouse::latest();

    if ($searchKey != null) {  // ✅ Ahora funciona correctamente
        $warehouses = $warehouses->where('title', 'like', '%' . $searchKey . '%');
    }

    if ($available != null) {  // ✅ Ahora funciona correctamente
        $warehouses = $warehouses->where('available', $available);
    }

    $warehouses = $warehouses->paginate(paginationNumber());

    return view('managers.views.warehouse.index')->with([
        'warehouses' => $warehouses,
        'available' => $available,
        'searchKey' => $searchKey,
    ]);
}
```

---

## 📌 Ejemplo 2: Crear Nuevo Almacén

### ❌ ANTES (InventariesController - Intenta usar clase inexistente)
```php
public function store(Request $request){
    $inventarie = new Plan;  // ❌ ERROR: Plan no existe en el contexto
    $inventarie->uid = $this->generate_uid('plans');  // ❌ ERROR
    $inventarie->title = Str::upper($request->title);
    $inventarie->slug  = Str::slug($request->title, '-');
    $inventarie->price = $request->price;  // ❌ Warehouse no tiene estos campos
    $inventarie->discount = $request->discount;  // ❌ Warehouse no tiene estos campos
    $inventarie->description = $request->description;
    $inventarie->specific = $request->specific;
    $inventarie->available = $request->available;
    $inventarie->save();

    return response()->json([
        'status' => true,
        'uid' => $inventarie->uid,
        'message' => 'Se creo el curso correctamente',  // ❌ Mensaje incorrecto
    ]);
}
```

### ✅ DESPUÉS (WarehouseController - Implementación correcta)
```php
public function store(Request $request)
{
    $validated = $request->validate([  // ✅ Validación explícita
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'available' => 'required|boolean',
    ]);

    $warehouse = new Warehouse();  // ✅ Modelo correcto
    $warehouse->uid = $this->generateUid('warehouses');  // ✅ Prefijo correcto
    $warehouse->title = Str::upper($validated['title']);
    $warehouse->slug = Str::slug($validated['title'], '-');
    $warehouse->description = $validated['description'] ?? '';
    $warehouse->available = $validated['available'];
    $warehouse->save();

    // ✅ Auditoría de cambios
    activity()
        ->causedBy(auth()->user())
        ->performedOn($warehouse)
        ->event('created')
        ->log('Almacén creado: ' . $warehouse->title);

    return response()->json([
        'status' => true,
        'uid' => $warehouse->uid,
        'message' => 'Almacén creado correctamente',  // ✅ Mensaje correcto
    ]);
}
```

---

## 📌 Ejemplo 3: Listar Ubicaciones de un Almacén

### ❌ ANTES (LocationsController - Usa modelos que no existen)
```php
// Ruta: /manager/inventaries/historys/locations/{uid}
// Controlador: app/Http/Controllers/Managers/Inventaries/LocationsController.php
// Nota: Importa modelos que fueron eliminados del proyecto

use App\Models\Warehouse\InventarieLocation;  // ❌ Este archivo fue eliminado
use App\Models\Warehouse\InventarieLocationItem;  // ❌ Este archivo fue eliminado

public function index(Request $request, $uid)
{
    $inventarie = Warehouse::uid($uid)->firstOrFail();
    $searchKey = $request->search ?? null;

    $locations = $inventarie->locations();

    if ($searchKey) {
        // ❌ Join con tabla 'locations' (confuso, mezcla de conceptos)
        $locations = $locations->join('locations', 'locations.id', '=', 'inventarie_locations.location_id')
           ->where(function ($query) use ($searchKey) {
                $query->where('locations.title', 'like', '%' . $searchKey . '%')
                    ->orWhere('locations.barcode', 'like', '%' . $searchKey . '%');
            })
           ->select('inventarie_locations.*');
    }

    $locations = $locations->paginate(paginationNumber());

    return view('managers.views.warehouses.locations.index', [
        'inventarie' => $inventarie,
        'locations' => $locations,
        'searchKey' => $searchKey,
    ]);
}
```

### ✅ DESPUÉS (WarehouseLocationsController - Modelos y relaciones claras)
```php
// Ruta: /manager/warehouse/locations/{warehouse_uid}
// Controlador: app/Http/Controllers/Managers/Warehouse/WarehouseLocationsController.php
// Nota: Usa modelos que existen y relaciones claras

use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseLocation;  // ✅ Modelo correcto
use App\Models\Warehouse\WarehouseInventorySlot;  // ✅ Modelo correcto

public function index(Request $request, $uid)
{
    $warehouse = Warehouse::uid($uid)->firstOrFail();
    $searchKey = $request->search ?? null;

    $locations = $warehouse->locations();

    if ($searchKey) {
        // ✅ Búsqueda directa en el modelo correcto
        $locations = $locations->where(function ($query) use ($searchKey) {
            $query->where('code', 'like', '%' . $searchKey . '%')
                ->orWhere('barcode', 'like', '%' . $searchKey . '%')
                ->orWhere('name', 'like', '%' . $searchKey . '%');
        });
    }

    // ✅ Eager load de relaciones importantes
    $locations = $locations->with(['floor', 'style', 'slots'])->paginate(paginationNumber());

    return view('managers.views.warehouse.locations.index')->with([
        'warehouse' => $warehouse,  // ✅ Nombre consistente
        'locations' => $locations,
        'searchKey' => $searchKey,
    ]);
}
```

---

## 📌 Ejemplo 4: Histórico de Movimientos

### ❌ ANTES (HistoryController - Referencias a modelos eliminados)
```php
// Ruta: /manager/inventaries/historys
// Controlador: app/Http/Controllers/Managers/Inventaries/HistoryController.php

use App\Models\Warehouse\InventarieLocationItem;  // ❌ ELIMINADO DEL PROYECTO

public function index(Request $request){
    $searchKey = null ?? $request->search;  // ❌ Bug de sintaxis
    $items = InventarieLocationItem::latest();  // ❌ Modelo inexistente

    if ($searchKey) {
        $items->when(!strpos($searchKey, '-'), function ($query) use ($searchKey) {
            // Búsqueda complicada y confusa
            $query->where('products.reference', 'like', '%' . $searchKey . '%')
                ->orWhere('products.barcode', 'like', '%' . $searchKey . '%')
                ->orWhere('products.title', 'like', '%' . $searchKey . '%')
                ->orWhereHas('location', function ($q) use ($searchKey) {
                    $q->where('locations.title', 'like', '%' . $searchKey . '%');
                });
        });
    }

    $items = $items->paginate(paginationNumber());

    return view('managers.views.warehouses.historys.index')->with([
        'items' => $items,
        'searchKey' => $searchKey,
    ]);
}
```

### ✅ DESPUÉS (WarehouseHistoryController - Modelo moderno con auditoría)
```php
// Ruta: /manager/warehouse/history
// Controlador: app/Http/Controllers/Managers/Warehouse/WarehouseHistoryController.php

use App\Models\Warehouse\WarehouseInventoryMovement;  // ✅ Modelo de auditoría

public function index(Request $request)
{
    $searchKey = $request->search ?? null;  // ✅ Sintaxis correcta
    $movements = WarehouseInventoryMovement::with(['slot', 'product', 'warehouse', 'user'])->latest();

    if ($searchKey) {
        // ✅ Búsqueda clara y eficiente
        $movements = $movements->where(function ($query) use ($searchKey) {
            // Búsqueda en productos
            $query->whereHas('product', function ($q) use ($searchKey) {
                $q->where('reference', 'like', '%' . $searchKey . '%')
                    ->orWhere('barcode', 'like', '%' . $searchKey . '%')
                    ->orWhere('title', 'like', '%' . $searchKey . '%');
            })
            // Búsqueda en ubicaciones/slots
            ->orWhereHas('slot', function ($q) use ($searchKey) {
                $q->where('barcode', 'like', '%' . $searchKey . '%')
                    ->orWhere('uid', 'like', '%' . $searchKey . '%');
            })
            // Búsqueda en razón del movimiento
            ->orWhere('reason', 'like', '%' . $searchKey . '%');
        });
    }

    $movements = $movements->paginate(paginationNumber());

    return view('managers.views.warehouse.history.index')->with([
        'movements' => $movements,
        'searchKey' => $searchKey,
    ]);
}
```

---

## 📌 Ejemplo 5: Estructura de Rutas

### ❌ ANTES
```
/manager/inventaries/
├── / (listar)
├── /create
├── /edit/{uid}
├── /view/{uid}
├── /destroy/{uid}
├── /report/{uid}
├── /historys/{uid}
├── /history/edit/{uid}
├── /history/destroy/{uid}
├── /history/update
├── /historys/locations/{uid}
├── /history/locations/details/{uid}
├── /history/locations/edit/{uid}
├── /history/locations/destroy/{uid}
├── /history/locations/update
├── /history/locations/destroy/items/{uid}
└── /historys/locationss/{uid}
```
❌ Estructura confusa con paths inconsistentes

### ✅ DESPUÉS
```
/manager/warehouse/
├── / (dashboard)
├── /api/statistics
├── /api/warehouses
├── /api/floors/{warehouse_id}
├── /warehouses/ (CRUD)
│   ├── /
│   ├── /create
│   ├── /store
│   ├── /edit/{uid}
│   ├── /update
│   ├── /view/{uid}
│   ├── /destroy/{uid}
│   ├── /{uid}/thumbnails
│   ├── /{uid}/summary
├── /locations/ (CRUD)
│   ├── /{warehouse_uid}
│   ├── /create/{warehouse_uid}
│   ├── /store
│   ├── /view/{uid}
│   ├── /edit/{uid}
│   ├── /update
│   ├── /destroy/{uid}
│   ├── /slot/{uid} (delete)
│   ├── /api/warehouse/{warehouse_id}
│   └── /api/barcode/{barcode}
├── /history/ (Movimientos)
│   ├── /
│   ├── /view/{uid}
│   ├── /edit/{uid}
│   ├── /update
│   ├── /api/slot/{slot_uid}
│   ├── /api/warehouse/{warehouse_uid}
│   ├── /api/filter (POST)
│   └── /api/statistics
├── /reports/ (Reportes)
│   ├── /
│   ├── /inventory (POST)
│   ├── /movements (POST)
│   ├── /occupancy (POST)
│   └── /capacity (POST)
└── /map (Visualización)
```
✅ Estructura clara, RESTful, bien organizada

---

## 🎯 Resumen de Mejoras

| Aspecto | Antes | Después |
|--------|-------|---------|
| **Bugs Sintácticos** | ❌ Múltiples | ✅ Ninguno |
| **Modelos Correctos** | ❌ Referencias a modelos eliminados | ✅ Todos los modelos existen |
| **Validación** | ❌ Mínima | ✅ Validación explícita |
| **Auditoría** | ❌ No existe | ✅ Activity Log integrado |
| **API REST** | ❌ Limitada | ✅ Endpoints completos |
| **Estructura de Rutas** | ❌ Inconsistente | ✅ RESTful y consistente |
| **Documentación Código** | ❌ Poca | ✅ Bien documentado |
| **Mantenibilidad** | ❌ Difícil | ✅ Fácil |
| **Escalabilidad** | ❌ Limitada | ✅ Completa |


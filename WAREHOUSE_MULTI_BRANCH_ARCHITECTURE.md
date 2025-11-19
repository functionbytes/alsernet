# 🏢 Arquitectura Multi-Sucursal para Warehouse + Inventory

## 📋 ANÁLISIS ACTUAL DE LA ESTRUCTURA

### **Modelos Existentes**

#### 1. **Warehouse Module** (app/Models/Warehouse/)
```
Floor (Pisos del almacén)
├── Stand (Estanterías en un piso)
│   ├── StandStyle (Tipo de estantería: ROW, COLUMNS)
│   └── InventorySlot (Posiciones dentro de estantería)
│       └── Product (Productos almacenados)
```

**Características:**
- ✅ Estructura completa de almacén físico
- ✅ Distribución en grilla (position_x, position_y)
- ✅ Sistema de escalado dinámico
- ✅ Vectores SVG mejorados
- ❌ **PROBLEMA**: No está vinculado a Location/Inventarie

#### 2. **Inventory Module** (app/Models/Inventarie/)
```
Inventarie (Evento/Inventario general)
├── Shop (Tienda/Empresa)
└── InventarieLocation
    ├── Location (Sucursal)
    │   └── Product (Productos en sucursal)
    └── InventarieLocationItem (Items específicos)
```

**Características:**
- ✅ Sistema de sucursales (Location)
- ✅ Relación con Shop
- ✅ Kardex y tracking
- ❌ **PROBLEMA**: No usa la estructura de Warehouse

---

## 🔴 PROBLEMAS IDENTIFICADOS

### **1. Desconexión entre Warehouse y Inventory**

**Escenario actual:**
```
App A (Warehouse Module)
├── Floor 1 → Stand 1 → InventorySlot 1
├── Floor 2 → Stand 2 → InventorySlot 2
└── No referencia a Location/Shop

App B (Inventory Module)
├── Inventarie (sin Warehouse)
├── Location 1 (sin Warehouse)
└── Product (sin InventorySlot)
```

**Impacto:**
- No sabes DÓNDE están los productos en el almacén físico
- No puedes filtrar stands por sucursal
- No hay trazabilidad desde producto → slot → sucursal
- Los datos de inventory no se usan en el warehouse map

### **2. Estructura de Datos Duplicada**

| Necesidad | Warehouse | Inventory | ¿Dónde van? |
|-----------|-----------|-----------|----------|
| Ubicación física | ✅ Stand + Slot | ❌ | Warehouse |
| Cantidad | ❌ | ✅ InventarieLocationItem | Inventory |
| Sucursal | ❌ | ✅ Location | Inventory |
| Producto | ❌ (product_id) | ✅ | Ambos |

### **3. Controladores Desconectados**

```
WarehouseMapController
├── getLayoutSpec() → Solo Warehouse
├── No filtra por Location/Sucursal
└── No usa datos de Inventarie

InventorySlotsController
├── Maneja InventorySlot
├── No tiene relación con Inventarie
└── No sincroniza con Inventory quantities

InventariesController
├── Maneja Inventarie (eventos)
├── No usa Warehouse
└── Información dispersa
```

---

## ✅ SOLUCIÓN PROPUESTA

### **Nivel 1: Crear la Cadena de Relaciones**

#### **Opción A: Vinculación Directa (Recomendada)**

```
Shop
├── Inventarie (evento de inventory)
│   ├── InventarieLocation (sucursal en el evento)
│   │   ├── Location (información de sucursal)
│   │   │   └── **Warehouse** (NEW) ← Cada sucursal tiene su propio warehouse
│   │   │       ├── Floor
│   │   │       ├── Stand
│   │   │       └── InventorySlot
│   │   └── InventarieLocationItem
│   │       └── InventorySlot (referencia a posición)
│   └── KardexEntry (movimientos)
└── Product
    └── ProductLocation (stock global por sucursal)
```

**Implementación:**
1. Crear tabla `location_warehouses` (pivot entre Location y Warehouse)
2. Agregar relación Location → Warehouse
3. Vincular InventarieLocationItem con InventorySlot

#### **BD Schema**

```sql
-- Nueva tabla
CREATE TABLE location_warehouses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    location_id INT FOREIGN KEY(locations.id),
    warehouse_id INT FOREIGN KEY(warehouses.id), -- Nueva tabla principal
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Nueva tabla principal
CREATE TABLE warehouses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uid VARCHAR(36) UNIQUE,
    code VARCHAR(50) UNIQUE,
    name VARCHAR(255),
    width_m DECIMAL(8,2) DEFAULT 42.23,
    height_m DECIMAL(8,2) DEFAULT 30.26,
    available BOOLEAN DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Modificar floors
ALTER TABLE warehouse_floors ADD warehouse_id INT FOREIGN KEY(warehouses.id);

-- Modificar inventory_slots para vincular con Inventarie
ALTER TABLE warehouse_inventory_slots ADD inventarie_location_item_id INT;
ALTER TABLE warehouse_inventory_slots ADD FOREIGN KEY(inventarie_location_item_id)
    REFERENCES inventarie_location_items(id);
```

---

### **Nivel 2: Crear Modelos Larvel**

#### **A. Modelo Warehouse (Nueva)**

```php
// app/Models/Warehouse/Warehouse.php

class Warehouse extends Model
{
    protected $fillable = [
        'uid', 'code', 'name',
        'width_m', 'height_m',
        'available'
    ];

    // Una sucursal puede tener múltiples almacenes
    public function locations()
    {
        return $this->belongsToMany(Location::class, 'location_warehouses');
    }

    // Un almacén tiene múltiples pisos
    public function floors()
    {
        return $this->hasMany(Floor::class);
    }

    // Métodos helper
    public function getTotalCapacity()
    {
        return $this->floors()
            ->with('stands')
            ->get()
            ->sum(fn($floor) => $floor->stands->count());
    }
}
```

#### **B. Modificar Floor Model**

```php
// app/Models/Warehouse/Floor.php

class Floor extends Model
{
    protected $fillable = [
        'uid', 'warehouse_id', // NEW
        'code', 'name', 'description',
        'available', 'order'
    ];

    // Relación con warehouse (NEW)
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // Relación con stands (EXISTENTE)
    public function stands()
    {
        return $this->hasMany(Stand::class);
    }

    // Scope para filtrar por warehouse
    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }
}
```

#### **C. Modificar Location Model**

```php
// app/Models/Location.php

class Location extends Model
{
    // Relación con warehouse (NEW)
    public function warehouses()
    {
        return $this->belongsToMany(
            Warehouse::class,
            'location_warehouses'
        );
    }

    // Relación existente
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    // Scope para obtener warehouse principal
    public function scopePrimaryWarehouse($query)
    {
        return $query->with('warehouses')
            ->first() ?->warehouses()->first();
    }
}
```

#### **D. Modificar InventorySlot Model**

```php
// app/Models/Warehouse/InventorySlot.php

class InventorySlot extends Model
{
    protected $fillable = [
        'uid', 'stand_id', 'product_id',
        'inventarie_location_item_id', // NEW
        'face', 'level', 'section',
        'barcode', 'quantity', 'max_quantity',
        'weight_current', 'weight_max',
        'is_occupied', 'last_movement'
    ];

    // Relación con inventarie location item (NEW)
    public function inventarieItem()
    {
        return $this->belongsTo(
            InventarieLocationItem::class,
            'inventarie_location_item_id'
        );
    }

    // Relaciones existentes
    public function stand()
    {
        return $this->belongsTo(Stand::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // NEW: Obtener la sucursal/location de este slot
    public function getLocation()
    {
        return $this->inventarieItem?->inventarieLocation?->location;
    }

    // NEW: Obtener el warehouse de este slot
    public function getWarehouse()
    {
        return $this->stand?->floor?->warehouse;
    }
}
```

---

### **Nivel 3: Crear Controllers Integrados**

#### **A. WarehouseMultiBranchController (Nuevo)**

```php
// app/Http/Controllers/Managers/Warehouse/WarehouseMultiBranchController.php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Models\Warehouse\Warehouse;
use App\Models\Location;
use App\Models\Inventarie\Inventarie;

class WarehouseMultiBranchController extends Controller
{
    /**
     * Get warehouse map filtered by location/branch
     */
    public function getMapByBranch(Request $request)
    {
        $locationId = $request->query('location_id');

        // Obtener warehouse principal de la sucursal
        $location = Location::find($locationId);
        $warehouse = $location->warehouses()->first();

        if (!$warehouse) {
            return response()->json(['error' => 'No warehouse for this location'], 404);
        }

        // Cargar pisos del warehouse específico
        $floors = $warehouse->floors()->with('stands.slots')->get();

        return response()->json([
            'success' => true,
            'warehouse' => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'location' => $location->title ?? $location->name,
                'width_m' => $warehouse->width_m,
                'height_m' => $warehouse->height_m,
            ],
            'floors' => $floors,
            'layout' => $this->buildLayoutSpec($floors),
        ]);
    }

    /**
     * Get inventory for specific branch
     */
    public function getBranchInventory(Request $request)
    {
        $locationId = $request->query('location_id');

        // Obtener inventario de la sucursal
        $location = Location::find($locationId);

        $inventory = InventarieLocationItem::with([
            'inventarieLocation.inventarie',
            'inventorySlot.stand.floor'
        ])
        ->whereHas('inventarieLocation', function ($query) use ($locationId) {
            $query->where('location_id', $locationId);
        })
        ->get();

        return response()->json([
            'success' => true,
            'location' => $location->title,
            'inventory' => $inventory->map(fn($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'slot' => [
                    'id' => $item->inventorySlot?->id,
                    'address' => $item->inventorySlot?->getAddress(),
                ],
            ]),
        ]);
    }

    /**
     * Assign product to warehouse slot
     */
    public function assignToSlot(Request $request)
    {
        $slotId = $request->input('slot_id');
        $itemId = $request->input('inventarie_item_id');
        $quantity = $request->input('quantity');

        $slot = InventorySlot::findOrFail($slotId);
        $item = InventarieLocationItem::findOrFail($itemId);

        // Validar que la ubicación coincida
        $location = $item->inventarieLocation->location;
        $slotLocation = $slot->getLocation();

        if ($slotLocation?->id !== $location->id) {
            return response()->json([
                'error' => 'Slot and item must be in same location'
            ], 422);
        }

        // Actualizar slot
        $slot->update([
            'inventarie_location_item_id' => $itemId,
            'quantity' => $quantity,
            'is_occupied' => true,
            'product_id' => $item->product_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product assigned to slot',
            'slot' => $slot->getFullInfo(),
        ]);
    }

    private function buildLayoutSpec($floors): array
    {
        // Similar a WarehouseMapController::transformStandsToLayoutSpec
        // pero filtrando por warehouse/location
    }
}
```

#### **B. Modificar WarehouseMapController (Existente)**

```php
// app/Http/Controllers/Managers/Warehouse/WarehouseMapController.php

class WarehouseMapController extends Controller
{
    /**
     * Display warehouse map (pueden ser múltiples warehouses)
     */
    public function map(Request $request)
    {
        // Si viene location_id, usar ese warehouse
        // Si no, mostrar warehouse default

        $warehouseId = $request->query('warehouse_id');
        $locationId = $request->query('location_id');

        $query = Warehouse::with('floors.stands');

        if ($warehouseId) {
            $query->where('id', $warehouseId);
        } elseif ($locationId) {
            $warehouse = Location::find($locationId)
                ->warehouses()
                ->first();
            return redirect()->route('warehouse.map',
                ['warehouse_id' => $warehouse->id]
            );
        }

        $warehouse = $query->firstOrFail();
        $floors = $warehouse->floors()->ordered()->get();

        return view('managers.views.warehouse.map.index', [
            'warehouse' => $warehouse,
            'floors' => $floors,
            'locations' => $warehouse->locations, // Para selector
        ]);
    }

    /**
     * API: Get layout for specific warehouse
     */
    public function getLayoutSpec(Request $request): JsonResponse
    {
        $floorId = $request->query('floor_id');
        $warehouseId = $request->query('warehouse_id');

        $query = Stand::with(['floor', 'style', 'slots.product']);

        if ($warehouseId) {
            $query->whereHas('floor', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });
        }

        if ($floorId) {
            $query->where('floor_id', $floorId);
        }

        $stands = $query->ordered()->get();
        $layoutSpec = $this->transformStandsToLayoutSpec($stands);

        return response()->json([
            'success' => true,
            'layoutSpec' => $layoutSpec,
            'metadata' => [
                'totalStands' => count($stands),
                'warehouseId' => $warehouseId,
            ],
        ]);
    }
}
```

---

### **Nivel 4: Actualizar Vistas/Frontend**

#### **A. Selector de Sucursal en Warehouse Map**

```html
<!-- En resources/views/managers/views/warehouse/map/index.blade.php -->

<div class="branch-selector">
    <label>Sucursal:</label>
    <select id="branch-select" @change="onBranchChange($event)">
        <option value="">-- Seleccionar Sucursal --</option>
        @foreach($locations as $location)
            <option value="{{ $location->id }}">{{ $location->title }}</option>
        @endforeach
    </select>
</div>

<script>
function onBranchChange(event) {
    const locationId = event.target.value;
    if (locationId) {
        // Recargar mapa con warehouse de esa sucursal
        fetch(`/api/warehouse/layout?location_id=${locationId}`)
            .then(r => r.json())
            .then(data => {
                SCALE_SYSTEM.setupResponsiveScaling();
                drawFloorGroup(data.warehouse);
            });
    }
}
</script>
```

#### **B. Agregar Información de Sucursal en Modal de Slot**

```html
<!-- En modal de inventory slot -->
<div class="slot-details">
    <h4 id="slotAddress"></h4>
    <div class="details-grid">
        <div>
            <label>Sucursal:</label>
            <span id="slotLocation"></span>
        </div>
        <div>
            <label>Almacén:</label>
            <span id="slotWarehouse"></span>
        </div>
        <div>
            <label>Piso:</label>
            <span id="slotFloor"></span>
        </div>
        <!-- ... otros datos ... -->
    </div>
</div>
```

---

## 📊 FLUJO DE DATOS PROPUESTO

### **Inserción de Inventario Multi-Sucursal**

```
1. Usuario crea Inventarie (evento de inventory)
   ↓
2. Define InventarieLocation (para sucursal X)
   ↓
3. Agrega InventarieLocationItem (producto Y, cantidad Z)
   ↓
4. Sistema propone InventorySlot disponible en warehouse de sucursal X
   ↓
5. Usuario asigna slot → InventarieLocationItem vinculado a InventorySlot
   ↓
6. InventorySlot actualizado con:
   - inventarie_location_item_id (vínculo)
   - quantity (cantidad)
   - product_id (producto)
   - location (sucursal X)
   ↓
7. KardexEntry registra movimiento
   ↓
8. Warehouse Map muestra distribución en tiempo real
```

### **Consulta de Inventario por Sucursal**

```
GET /api/warehouse/branch-inventory?location_id=5
    ↓
SELECT * FROM inventorie_location_items
WHERE inventarie_location_id IN (
    SELECT id FROM inventarie_locations
    WHERE location_id = 5
)
    ↓
JOIN inventory_slots ON inventorie_location_items.id = inventory_slots.inventarie_location_item_id
    ↓
JOIN stands ON inventory_slots.stand_id = stands.id
    ↓
JOIN floors ON stands.floor_id = floors.id
    ↓
JOIN warehouses ON floors.warehouse_id = warehouses.id
    ↓
Retorna: [
    {
        product: "Laptop",
        quantity: 50,
        location: "Sucursal Centro",
        warehouse: "WH-001",
        slot: "P1-PASILLO13A-1 / Derecha / Nivel 1 / Sección 1",
    }
]
```

---

## 🔄 DECISIONES CLAVE

### **Decisión 1: ¿Un warehouse por sucursal o varios?**

**Opción A: Un warehouse por sucursal** (Recomendada)
```
Location 1 (Sucursal Centro)
├── Warehouse 1 (42.23m × 30.26m)
└── Floors, Stands, Slots

Location 2 (Sucursal Oriente)
├── Warehouse 2 (40m × 25m) ← Diferente tamaño
└── Floors, Stands, Slots
```

**Ventajas:**
- ✅ Cada sucursal con su distribución propia
- ✅ Flexibilidad en tamaños/layouts
- ✅ Escalabilidad sin límites

---

### **Decisión 2: ¿Cómo vincular InventarieLocationItem con InventorySlot?**

**Opción A: Foreign Key** (Recomendada)
```php
InventarieLocationItem.inventory_slot_id → InventorySlot.id
```

**Ventajas:**
- ✅ Integridad relacional
- ✅ Fácil de consultar
- ✅ Transacciones ACID

**Riesgo:**
- ⚠️ Una posición no puede tener múltiples items (uno-a-uno)
- **Solución**: Permitir solo si quantity es la suma total

---

### **Decisión 3: ¿Sincronizar datos entre módulos?**

**Opción A: Listeners de Eloquent** (Recomendada)
```php
// Cuando se crea InventarieLocationItem
class InventarieLocationItem extends Model
{
    protected static function booted()
    {
        static::created(function ($model) {
            // Buscar slot disponible
            $slot = InventorySlot::available()
                ->whereHas('stand.floor.warehouse.locations',
                    fn($q) => $q->where('location_id',
                        $model->inventarieLocation->location_id)
                )
                ->first();

            if ($slot) {
                $slot->update([
                    'inventarie_location_item_id' => $model->id,
                    'product_id' => $model->product_id,
                    'quantity' => $model->quantity,
                ]);
            }
        });
    }
}
```

---

## 📋 CHECKLIST DE IMPLEMENTACIÓN

### **Fase 1: Base de Datos** (1-2 días)
- [ ] Crear tabla `warehouses`
- [ ] Crear tabla `location_warehouses`
- [ ] Migrar datos de floors a warehouse específico
- [ ] Agregar columna `warehouse_id` a floors
- [ ] Agregar columna `inventarie_location_item_id` a inventory_slots

### **Fase 2: Modelos** (1-2 días)
- [ ] Crear Warehouse model
- [ ] Modificar Floor model (agregar warehouse_id)
- [ ] Modificar Location model (relación con warehouse)
- [ ] Modificar InventorySlot (relación con inventarie_item)
- [ ] Agregar scopes y métodos

### **Fase 3: Controladores** (2-3 días)
- [ ] Crear WarehouseMultiBranchController
- [ ] Modificar WarehouseMapController (filtrar por warehouse)
- [ ] Crear API endpoints para branch-specific queries
- [ ] Agregar validaciones de integridad

### **Fase 4: Frontend** (2-3 días)
- [ ] Agregar selector de sucursal
- [ ] Actualizar warehouse map para filtrar
- [ ] Mostrar información de sucursal en modals
- [ ] Agregar filtros en inventory listings

### **Fase 5: Testing** (2-3 días)
- [ ] Tests unitarios de modelos
- [ ] Tests de API endpoints
- [ ] Tests de integridad de datos
- [ ] Tests de sincronización

### **Fase 6: Documentación** (1 día)
- [ ] Documentar endpoints
- [ ] Guía de uso para múltiples sucursales
- [ ] Troubleshooting

---

## 🚀 PRÓXIMOS PASOS

1. **Confirmar decisiones** sobre estructura
2. **Crear las migraciones** necesarias
3. **Implementar modelos** y relaciones
4. **Crear controllers** con endpoints
5. **Actualizar frontend** con selector de sucursal
6. **Hacer testing** completo
7. **Ejecutar seeder** con datos multi-sucursal

**¿Quieres que comencemos con alguna fase específica?** 🎯

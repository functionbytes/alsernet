# 🏢 ARQUITECTURA INTEGRADA: WAREHOUSE + INVENTARIE

**Fecha:** 17 de Noviembre de 2025
**Versión:** 2.0 - Arquitectura Unificada
**Estado:** Diseño de Reestructuración

---

## 📋 ÍNDICE

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Jerarquía Correcta](#jerarquía-correcta)
3. [Comparación Detallada de Modelos](#comparación-detallada-de-modelos)
4. [Nueva Arquitectura de Base de Datos](#nueva-arquitectura-de-base-de-datos)
5. [Relaciones y Constraints](#relaciones-y-constraints)
6. [Modificaciones a Modelos](#modificaciones-a-modelos)
7. [Plan de Migración](#plan-de-migración)
8. [Ejemplos de Uso](#ejemplos-de-uso)
9. [Cambios en Controladores](#cambios-en-controladores)
10. [Rutas Integradas](#rutas-integradas)

---

## 1. RESUMEN EJECUTIVO

### Problema Identificado

La estructura actual tenía dos sistemas desconectados:
- **Warehouse:** Un sistema de almacenamiento permanente (Floor → Stand → InventorySlot)
- **Inventarie:** Un sistema de auditoría temporal (Inventarie → Location → InventarieLocationItem)

**Solución:** Unificar ambos sistemas con una jerarquía clara donde **Inventarie es la Sede/Sucursal** que contiene toda la estructura de Warehouse.

### Nueva Jerarquía (CORRECTA)

```
INVENTARIE (Sede/Sucursal)
  │
  ├── Información: código, nombre, dirección, datos de contacto
  ├── Estado: activa/inactiva
  │
  ├── FLOOR (Piso/Planta)
  │   ├── Información: número, nombre, descripción
  │   │
  │   ├── STAND (Estantería)
  │   │   ├── Información: código, tipo/estilo, capacidad
  │   │   │
  │   │   └── INVENTORY_SLOT (Posición)
  │   │       ├── Ubicación: cara, nivel, sección
  │   │       ├── Producto almacenado
  │   │       ├── Cantidad actual / Máxima
  │   │       ├── Peso actual / Máximo
  │   │       └── Historial de movimientos
  │   │
  │   └── INVENTARIE_OPERATION (Operación de Inventario)
  │       └── INVENTARIE_LOCATION_ITEM (Producto contado)
  │           ├── Cantidad contada
  │           ├── Condición del producto
  │           └── Usuario que contó
  │
  └── INVENTARIE_CONDITION (Catálogo de condiciones)
```

---

## 2. JERARQUÍA CORRECTA

### 2.1 Estructura Multinivel

```
INVENTARIE (Sede de Negocio)
    ↓
FLOOR (Piso de la Sede)
    ↓
STAND (Estantería del Piso)
    ↓
INVENTORY_SLOT (Posición de la Estantería)
    ↓
PRODUCT (Producto Almacenado)
```

### 2.2 Relaciones Maestras-Detalles

```
1 Inventarie → N Floors           (1 sede puede tener múltiples pisos)
1 Floor → N Stands                (1 piso puede tener múltiples estanterías)
1 Stand → N InventorySlots        (1 estantería tiene múltiples posiciones)
1 InventorySlot → 1 Product       (1 posición almacena 1 tipo de producto)
```

### 2.3 Operaciones de Inventario dentro de Inventarie

```
1 Inventarie → N InventarieOperations    (Múltiples conteos en una sede)
  1 InventarieOperation → N InventarieLocations
    1 InventarieLocation → N InventarieLocationItems
```

---

## 3. COMPARACIÓN DETALLADA DE MODELOS

### 3.1 InventarieLocationItem vs InventorySlot

#### Similaridades

| Aspecto | InventarieLocationItem | InventorySlot |
|---------|----------------------|---------------|
| **Propósito** | Registrar producto contado | Almacenar producto permanente |
| **product_id** | ✅ Tiene | ✅ Tiene |
| **quantity** | ✅ count | ✅ quantity |
| **condition** | ✅ condition_id | ❌ No (se asume perfecto) |
| **user tracking** | ✅ user_id | ❌ No (solo last_movement) |
| **location tracking** | ✅ location_id | ✅ stand_id + face + level + section |

#### Diferencias Clave

**InventarieLocationItem:**
- Es **temporal** (durante un inventario)
- Registra **quién contó** y **en qué condición**
- Tiene dos ubicaciones: `original_id` y `validate_id` (lugar contado vs validado)
- Es un **evento** que ocurrió en un momento

**InventorySlot:**
- Es **permanente** (el almacén actual)
- Registra **límites de capacidad** (cantidad y peso)
- Tiene ubicación precisa (cara, nivel, sección)
- Es un **estado** que representa la realidad actual

### 3.2 Relación Propuesta

```
InventarieLocationItem (del conteo)
    ↓ (se sincroniza con)
InventorySlot (almacén permanente)

Cuando se "cierra" un InventarieLocationItem:
1. Se busca el InventorySlot correspondiente
2. Se actualiza la cantidad en InventorySlot
3. Se crea registro en InventoryMovement (auditoría)
4. Se establece link: InventorySlot.last_inventarie_id = inventarie_id
```

---

## 4. NUEVA ARQUITECTURA DE BASE DE DATOS

### 4.1 Tablas Modificadas

#### `inventaries` (Ya existe, se modifica)

```sql
CREATE TABLE inventaries (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uid UUID UNIQUE NOT NULL,

    -- Información de la Sede
    code VARCHAR(50) UNIQUE NOT NULL,           -- P1, P2, SEDE_NORTE
    name VARCHAR(255) NOT NULL,                  -- "Planta 1", "Almacén Central"
    slug VARCHAR(255) UNIQUE,                    -- Para URLs
    description TEXT,

    -- Ubicación física
    address VARCHAR(255),
    city VARCHAR(100),
    country VARCHAR(100),

    -- Estado operativo
    available BOOLEAN DEFAULT TRUE,

    -- Contacto
    phone VARCHAR(20),
    email VARCHAR(100),
    manager_id BIGINT,                           -- Gerente de la sede

    -- Timestamps
    closed_at TIMESTAMP NULL,                    -- Cuando se cerró (si aplica)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE KEY `uq_inventarie_code` (code),
    INDEX `idx_available` (available),
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
);
```

#### `warehouse_floors` (Vinculado a Inventarie)

```sql
CREATE TABLE warehouse_floors (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uid UUID UNIQUE NOT NULL,

    inventarie_id BIGINT NOT NULL,     -- ⭐ NUEVA: Relación con sede
    code VARCHAR(50),                   -- P1, P2, S0
    name VARCHAR(255),                  -- "Planta 1", "Sótano"
    description TEXT,

    available BOOLEAN DEFAULT TRUE,
    order INT DEFAULT 0,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE KEY `uq_floor_inventarie_code` (inventarie_id, code),
    FOREIGN KEY (inventarie_id) REFERENCES inventaries(id)
        ON DELETE CASCADE,
    INDEX `idx_inventarie_id` (inventarie_id),
    INDEX `idx_available` (available)
);
```

#### `warehouse_stands` (Vinculado a Floor)

```sql
CREATE TABLE warehouse_stands (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uid UUID UNIQUE NOT NULL,

    floor_id BIGINT NOT NULL,              -- Relación con piso
    stand_style_id BIGINT NOT NULL,        -- Tipo de estantería

    code VARCHAR(50),                      -- PASILLO1A, ISLA02
    barcode VARCHAR(100) UNIQUE,

    position_x DECIMAL(10, 2),
    position_y DECIMAL(10, 2),
    position_z DECIMAL(10, 2),

    total_levels INT DEFAULT 3,
    total_sections INT DEFAULT 5,
    capacity DECIMAL(10, 2),

    available BOOLEAN DEFAULT TRUE,
    notes TEXT,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE KEY `uq_stand_floor_code` (floor_id, code),
    FOREIGN KEY (floor_id) REFERENCES warehouse_floors(id)
        ON DELETE CASCADE,
    FOREIGN KEY (stand_style_id) REFERENCES warehouse_stand_styles(id)
        ON DELETE RESTRICT,
    INDEX `idx_floor_id` (floor_id),
    INDEX `idx_available` (available)
);
```

#### `warehouse_inventory_slots` (Vinculado a Stand)

```sql
CREATE TABLE warehouse_inventory_slots (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uid UUID UNIQUE NOT NULL,

    stand_id BIGINT NOT NULL,
    product_id BIGINT,                     -- Producto almacenado

    face ENUM('left', 'right', 'front', 'back'),
    level INT,
    section INT,

    barcode VARCHAR(100),
    quantity INT DEFAULT 0,
    max_quantity INT,

    weight_current DECIMAL(10, 2) DEFAULT 0,
    weight_max DECIMAL(10, 2),

    is_occupied BOOLEAN DEFAULT FALSE,
    last_movement TIMESTAMP,
    last_inventarie_id BIGINT,             -- ⭐ NUEVA: Link a último inventario

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE KEY `uq_slot_position` (stand_id, face, level, section),
    FOREIGN KEY (stand_id) REFERENCES warehouse_stands(id)
        ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE SET NULL,
    FOREIGN KEY (last_inventarie_id) REFERENCES inventaries(id)
        ON DELETE SET NULL,

    INDEX `idx_stand_id` (stand_id),
    INDEX `idx_product_id` (product_id),
    INDEX `idx_is_occupied` (is_occupied),
    INDEX `idx_last_inventarie` (last_inventarie_id),
    INDEX `idx_stand_occupied` (stand_id, is_occupied)
);
```

### 4.2 Nueva Tabla: Auditoría de Movimientos

```sql
CREATE TABLE warehouse_inventory_movements (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uid UUID UNIQUE NOT NULL,

    slot_id BIGINT NOT NULL,
    product_id BIGINT,

    movement_type ENUM('add', 'subtract', 'clear', 'move', 'count') DEFAULT 'add',

    from_quantity INT DEFAULT 0,
    to_quantity INT DEFAULT 0,
    quantity_delta INT,

    from_weight DECIMAL(10, 2) DEFAULT 0,
    to_weight DECIMAL(10, 2) DEFAULT 0,
    weight_delta DECIMAL(10, 2),

    reason VARCHAR(255),
    inventarie_id BIGINT,                  -- Operación que causó el cambio
    inventarie_location_item_id BIGINT,    -- ⭐ NUEVA: Link a producto contado
    user_id BIGINT,

    notes TEXT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (slot_id) REFERENCES warehouse_inventory_slots(id)
        ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE SET NULL,
    FOREIGN KEY (inventarie_id) REFERENCES inventaries(id)
        ON DELETE SET NULL,
    FOREIGN KEY (inventarie_location_item_id)
        REFERENCES inventarie_locations_items(id)
        ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL,

    INDEX `idx_slot_id` (slot_id),
    INDEX `idx_inventarie_id` (inventarie_id),
    INDEX `idx_movement_type` (movement_type),
    INDEX `idx_recorded_at` (recorded_at)
);
```

### 4.3 Estructura Completa en Diagrama

```
INVENTARIES
├─ id (PK)
├─ uid (UUID)
├─ code, name, address
├─ available
└─ created_at, updated_at
       ↓ (1:N)
WAREHOUSE_FLOORS
├─ id (PK)
├─ uid (UUID)
├─ inventarie_id (FK) ⭐ NUEVA
├─ code, name, order
├─ available
└─ created_at, updated_at
       ↓ (1:N)
WAREHOUSE_STANDS
├─ id (PK)
├─ uid (UUID)
├─ floor_id (FK)
├─ stand_style_id (FK)
├─ code, barcode, position_x/y/z
├─ total_levels, total_sections, capacity
├─ available
└─ created_at, updated_at
       ↓ (1:N)
WAREHOUSE_INVENTORY_SLOTS
├─ id (PK)
├─ uid (UUID)
├─ stand_id (FK)
├─ product_id (FK)
├─ face, level, section
├─ barcode
├─ quantity, max_quantity
├─ weight_current, weight_max
├─ is_occupied
├─ last_movement
├─ last_inventarie_id (FK) ⭐ NUEVA
└─ created_at, updated_at
       ↓ (1:N)
WAREHOUSE_INVENTORY_MOVEMENTS
├─ id (PK)
├─ uid (UUID)
├─ slot_id (FK)
├─ product_id (FK)
├─ movement_type
├─ from/to quantity/weight
├─ reason
├─ inventarie_id (FK)
├─ inventarie_location_item_id (FK) ⭐ NUEVA
├─ user_id (FK)
├─ recorded_at
└─ created_at, updated_at
```

---

## 5. RELACIONES Y CONSTRAINTS

### 5.1 Árbol de Relaciones

```
Inventarie (1)
  ├── (1:N) Floors
  │    └── (1:N) Stands
  │         └── (1:N) InventorySlots (cada slot apunta a Inventarie vía last_inventarie_id)
  │              └── (1:1) Products
  │
  ├── (1:N) InventarieOperations (operaciones de conteo)
  │    └── (1:N) InventarieLocations
  │         └── (1:N) InventarieLocationItems (productos contados)
  │              └── (1:1) InventoryMovements (cuando se sincronizan)
  │
  └── (1:N) InventarieConditions (catálogo de condiciones)
```

### 5.2 Constraints de Integridad

| Relación | ON DELETE | ON UPDATE | Razón |
|----------|-----------|-----------|-------|
| Floor → Inventarie | CASCADE | CASCADE | Si se elimina sede, se eliminan pisos |
| Stand → Floor | CASCADE | CASCADE | Si se elimina piso, se eliminan estanterías |
| InventorySlot → Stand | CASCADE | CASCADE | Si se elimina estantería, se eliminan posiciones |
| InventorySlot → Product | SET NULL | CASCADE | Si se elimina producto, se vacía posición |
| InventorySlot → Inventarie (last_inventarie_id) | SET NULL | CASCADE | Solo referencia histórica |
| InventoryMovement → InventorySlot | CASCADE | CASCADE | Si se elimina slot, se elimina su historial |

### 5.3 Restricciones de Unicidad

```sql
-- Cada piso tiene código único dentro de su sede
UNIQUE (inventarie_id, floor_code)

-- Cada estantería tiene código único dentro de su piso
UNIQUE (floor_id, stand_code)

-- Cada posición es única dentro de su estantería
UNIQUE (stand_id, face, level, section)
```

---

## 6. MODIFICACIONES A MODELOS

### 6.1 Modificar: `Inventarie.php`

```php
namespace App\Models\Inventarie;

use App\Models\Warehouse\WarehouseFloor;use App\Models\Warehouse\WarehouseInventoryOperation;use App\Models\Warehouse\WarehouseInventoryMovement;

class Inventarie extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'uid', 'code', 'name', 'slug', 'description',
        'address', 'city', 'country',
        'available', 'phone', 'email', 'manager_id',
        'closed_at'
    ];

    // ⭐ NUEVA RELACIÓN: Una sede tiene múltiples pisos
    public function floors()
    {
        return $this->hasMany(WarehouseFloor::class, 'inventarie_id');
    }

    // ⭐ NUEVA: Todas las operaciones de inventario en esta sede
    public function inventarieOperations()
    {
        return $this->hasMany(WarehouseInventoryOperation::class);
    }

    // ⭐ NUEVA: Todos los movimientos registrados en esta sede
    public function inventoryMovements()
    {
        return $this->hasMany(WarehouseInventoryMovement::class);
    }

    // ⭐ NUEVA: Buscar por código
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    // ⭐ NUEVA: Obtener información jerárquica completa
    public function getHierarchy()
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'name' => $this->name,
            'code' => $this->code,
            'total_floors' => $this->floors()->count(),
            'total_stands' => $this->floors()->sum(
                \DB::raw('(SELECT COUNT(*) FROM warehouse_stands WHERE warehouse_stands.floor_id = warehouse_floors.id)')
            ),
            'floors' => $this->floors()->with(['stands.style'])->get(),
        ];
    }
}
```

### 6.2 Modificar: `Floor.php`

```php
namespace App\Models\Warehouse;

class Floor extends Model
{
    use HasFactory, HasUid;

    protected $table = 'warehouse_floors';

    protected $fillable = [
        'uid', 'inventarie_id',  // ⭐ NUEVA
        'code', 'name', 'description',
        'available', 'order'
    ];

    // ⭐ NUEVA RELACIÓN: Un piso pertenece a una sede
    public function inventarie()
    {
        return $this->belongsTo(Warehouse::class, 'inventarie_id');
    }

    // Relación existente
    public function stands()
    {
        return $this->hasMany(WarehouseLocation::class, 'floor_id');
    }

    // ⭐ NUEVA: Scope por sede
    public function scopeByInventarie($query, $inventarieId)
    {
        return $query->where('inventarie_id', $inventarieId);
    }

    // ⭐ NUEVA: Obtener información con jerarquía completa
    public function getCompleteHierarchy()
    {
        return [
            'floor' => [
                'id' => $this->id,
                'uid' => $this->uid,
                'name' => $this->name,
                'code' => $this->code,
                'inventarie' => $this->inventarie->only(['id', 'name', 'code']),
            ],
            'stands' => $this->stands()->with('slots.product')->get(),
            'stats' => [
                'total_stands' => $this->stands()->count(),
                'total_slots' => \DB::table('warehouse_inventory_slots')
                    ->whereIn('stand_id', $this->stands()->pluck('id'))
                    ->count(),
                'occupied_slots' => \DB::table('warehouse_inventory_slots')
                    ->whereIn('stand_id', $this->stands()->pluck('id'))
                    ->where('is_occupied', true)
                    ->count(),
            ]
        ];
    }
}
```

### 6.3 Modificar: `InventorySlot.php`

```php
namespace App\Models\Warehouse;

class InventorySlot extends Model
{
    use HasFactory, HasUid;

    protected $fillable = [
        'uid', 'stand_id', 'product_id',
        'face', 'level', 'section', 'barcode',
        'quantity', 'max_quantity',
        'weight_current', 'weight_max',
        'is_occupied', 'last_movement',
        'last_inventarie_id'  // ⭐ NUEVA
    ];

    // ⭐ NUEVA RELACIÓN: Último inventario que afectó este slot
    public function lastInventarie()
    {
        return $this->belongsTo(Warehouse::class, 'last_inventarie_id');
    }

    // Relaciones existentes
    public function stand()
    {
        return $this->belongsTo(WarehouseLocation::class, 'stand_id');
    }

    public function product()
    {
        return $this->belongsTo('App\Models\Product\Product', 'product_id');
    }

    // ⭐ NUEVA: Obtener movimientos de este slot
    public function movements()
    {
        return $this->hasMany(WarehouseInventoryMovement::class, 'slot_id');
    }

    // ⭐ MODIFICADO: addQuantity con auditoría
    public function addQuantity(
        int $amount,
        ?string $reason = null,
        ?int $userId = null,
        ?int $inventarieId = null
    ): bool
    {
        if (!$this->canAddQuantity($amount)) {
            return false;
        }

        $fromQty = $this->quantity;
        $toQty = $this->quantity + $amount;

        // Actualizar slot
        $this->update([
            'quantity' => $toQty,
            'is_occupied' => true,
            'last_movement' => now(),
            'last_inventarie_id' => $inventarieId,
        ]);

        // ⭐ NUEVA: Registrar movimiento
        WarehouseInventoryMovement::create([
            'slot_id' => $this->id,
            'product_id' => $this->product_id,
            'movement_type' => 'add',
            'from_quantity' => $fromQty,
            'to_quantity' => $toQty,
            'quantity_delta' => $amount,
            'from_weight' => $this->weight_current,
            'to_weight' => $this->weight_current,
            'weight_delta' => 0,
            'reason' => $reason ?? 'Manual',
            'inventarie_id' => $inventarieId,
            'user_id' => $userId,
        ]);

        return true;
    }

    // Métodos similares para subtractQuantity, addWeight, etc.
    // ... (siguiendo el mismo patrón)
}
```

### 6.4 Crear: `InventoryMovement.php`

```php
namespace App\Models\Warehouse;

use App\Library\Traits\HasUid;

class InventoryMovement extends Model
{
    use HasFactory, HasUid;

    protected $table = 'warehouse_inventory_movements';

    protected $fillable = [
        'uid',
        'slot_id',
        'product_id',
        'movement_type',
        'from_quantity',
        'to_quantity',
        'quantity_delta',
        'from_weight',
        'to_weight',
        'weight_delta',
        'reason',
        'inventarie_id',
        'inventarie_location_item_id',  // ⭐ NUEVA
        'user_id',
        'notes',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function slot()
    {
        return $this->belongsTo(WarehouseInventorySlot::class, 'slot_id');
    }

    public function product()
    {
        return $this->belongsTo('App\Models\Product\Product', 'product_id');
    }

    public function inventarie()
    {
        return $this->belongsTo(Warehouse::class, 'inventarie_id');
    }

    // ⭐ NUEVA: Link a producto contado durante inventario
    public function inventarieLocationItem()
    {
        return $this->belongsTo(
            InventarieLocationItem::class,
            'inventarie_location_item_id'
        );
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    // Scopes útiles
    public function scopeBySlot($query, $slotId)
    {
        return $query->where('slot_id', $slotId);
    }

    public function scopeByInventarie($query, $inventarieId)
    {
        return $query->where('inventarie_id', $inventarieId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('recorded_at', '>=', now()->subDays($days));
    }
}
```

### 6.5 Modificar: `InventarieLocationItem.php`

```php
namespace App\Models\Inventarie;

use App\Models\Warehouse\WarehouseInventoryMovement;

class InventarieLocationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid', 'count', 'product_id', 'location_id',
        'original_id', 'validate_id', 'condition_id', 'user_id',
        'synced_to_warehouse',  // ⭐ NUEVA
        'inventory_movement_id' // ⭐ NUEVA
    ];

    // Relaciones existentes
    public function product()
    {
        return $this->belongsTo('App\Models\Product\Product', 'product_id');
    }

    // ... otras relaciones ...

    // ⭐ NUEVA: Link a movimiento de warehouse
    public function inventoryMovement()
    {
        return $this->belongsTo(WarehouseInventoryMovement::class, 'inventory_movement_id');
    }

    // ⭐ NUEVA: Método para sincronizar con warehouse
    public function syncToWarehouse($userId = null, $inventarieId = null)
    {
        // Buscar InventorySlot correspondiente en Warehouse
        $slot = \DB::table('warehouse_inventory_slots')
            ->where('product_id', $this->product_id)
            ->where('stand_id', function ($query) {
                $query->select('id')
                    ->from('warehouse_stands')
                    ->where('floor_id', function ($q) {
                        // Buscar stand que corresponda a la ubicación
                        $q->select('id')
                            ->from('warehouse_floors')
                            // Lógica de mapeo entre Location y Stand
                    });
            })
            ->first();

        if ($slot && $slot->quantity != $this->count) {
            $movement = WarehouseInventoryMovement::create([
                'slot_id' => $slot->id,
                'product_id' => $this->product_id,
                'movement_type' => 'count',
                'from_quantity' => $slot->quantity,
                'to_quantity' => $this->count,
                'quantity_delta' => $this->count - $slot->quantity,
                'reason' => 'Sincronización de inventario',
                'inventarie_id' => $inventarieId,
                'inventarie_location_item_id' => $this->id,
                'user_id' => $userId,
            ]);

            // Actualizar slot
            $slot->quantity = $this->count;
            $slot->is_occupied = $this->count > 0;
            $slot->last_movement = now();
            $slot->last_inventarie_id = $inventarieId;
            $slot->save();

            // Marcar como sincronizado
            $this->update([
                'synced_to_warehouse' => true,
                'inventory_movement_id' => $movement->id,
            ]);

            return true;
        }

        return false;
    }
}
```

---

## 7. PLAN DE MIGRACIÓN

### 7.1 Fase 1: Preparación (1-2 días)

**Paso 1: Crear migraciones nuevas**
- `ModifyWarehouseFloorsAddInventarieId` - Agregar FK a Inventarie
- `ModifyWarehouseInventorySlotsAddInventarieTracking` - Agregar campos de auditoría
- `CreateWarehouseInventoryMovements` - Nueva tabla de movimientos
- `ModifyInventarieLocationsItemsAddWarehouseSync` - Agregar campos de sincronización

**Paso 2: Crear modelos nuevos**
- `InventoryMovement.php` - Modelo de auditoría

**Paso 3: Actualizar modelos existentes**
- Modificar `Floor.php` - Agregar relación con Inventarie
- Modificar `InventorySlot.php` - Agregar auditoría
- Modificar `Inventarie.php` - Agregar relaciones con Warehouse
- Modificar `InventarieLocationItem.php` - Agregar sincronización

### 7.2 Fase 2: Migración de Datos (1-2 días)

**Paso 1: Migración de estructura**

```sql
-- Assumir que existe al menos un Inventarie principal
SET @DEFAULT_INVENTARIE_ID = 1;

-- Asignar todos los Floors a la Inventarie por defecto
UPDATE warehouse_floors
SET inventarie_id = @DEFAULT_INVENTARIE_ID
WHERE inventarie_id IS NULL;

-- Si hay múltiples Inventaries, hacer mapeo más complejo
-- (Basado en Shop o Location existentes)
```

**Paso 2: Verificación de integridad**

```sql
-- Verificar que no hay Floors huérfanos
SELECT COUNT(*) FROM warehouse_floors WHERE inventarie_id IS NULL;

-- Verificar que todos los Stands tienen Floor válido
SELECT COUNT(*) FROM warehouse_stands
WHERE floor_id NOT IN (SELECT id FROM warehouse_floors);

-- Verificar que todos los Slots tienen Stand válido
SELECT COUNT(*) FROM warehouse_inventory_slots
WHERE stand_id NOT IN (SELECT id FROM warehouse_stands);
```

### 7.3 Fase 3: Actualización de Código (2-3 días)

**Controladores a actualizar:**
- `InventorySlotsController` - Agregar auditoría
- `InventariesLocationsController` - Agregar sincronización
- Crear `WarehouseIntegrationController` - Nuevas operaciones

**Vistas a actualizar:**
- Mostrar jerarquía completa (Inventarie → Floor → Stand)
- Agregar historial de movimientos

### 7.4 Fase 4: Testing (1-2 días)

- Pruebas unitarias de modelos
- Pruebas de integración Inventarie ↔ Warehouse
- Pruebas de sincronización
- Pruebas de rendimiento

---

## 8. EJEMPLOS DE USO

### 8.1 Crear Estructura Completa de Inventarie

```php
// 1. Crear Inventarie (Sede)
$inventarie = Inventarie::create([
    'code' => 'SEDE_NORTE',
    'name' => 'Almacén Central - Sede Norte',
    'address' => 'Calle Principal 123',
    'city' => 'Madrid',
    'available' => true,
]);

// 2. Crear Floor dentro de Inventarie
$floor = Floor::create([
    'inventarie_id' => $inventarie->id,  // ⭐ NUEVA
    'code' => 'P1',
    'name' => 'Planta 1',
    'available' => true,
]);

// 3. Crear Stand dentro de Floor
$stand = Stand::create([
    'floor_id' => $floor->id,
    'stand_style_id' => 1,
    'code' => 'PASILLO1A',
    'total_levels' => 3,
    'total_sections' => 5,
    'capacity' => 500.00,
]);

// 4. Crear InventorySlots automáticamente
$stand->createSlots();

// 5. Obtener jerarquía completa
$hierarchy = $inventarie->getHierarchy();
// Resultado: Sede → Pisos → Estanterías → Posiciones
```

### 8.2 Operación de Inventario con Sincronización

```php
// 1. Crear operación de inventario (conteo)
$operation = InventarieOperation::create([
    'inventarie_id' => $inventarie->id,
    // ... otros campos
]);

// 2. Registrar producto contado
$item = InventarieLocationItem::create([
    'inventarie_operation_id' => $operation->id,
    'product_id' => 5,
    'count' => 10,      // Se contaron 10 unidades
    'condition_id' => 1, // Estado: Nuevo
    'user_id' => auth()->id(),
]);

// 3. Sincronizar a Warehouse
$synced = $item->syncToWarehouse(
    userId: auth()->id(),
    inventarieId: $inventarie->id
);

if ($synced) {
    // Se creó InventoryMovement automáticamente
    // Se actualizó InventorySlot.quantity
    // Se registró auditoria completa
}
```

### 8.3 Agregar Cantidad a Posición con Auditoría

```php
$slot = InventorySlot::find(1);

// Agregar 5 unidades
$slot->addQuantity(
    amount: 5,
    reason: 'Reposición manual',
    userId: auth()->id(),
    inventarieId: $inventarie->id
);

// Se crea automáticamente:
// 1. InventoryMovement con detalles del cambio
// 2. Se actualiza last_movement timestamp
// 3. Se registra last_inventarie_id
```

### 8.4 Ver Historial Completo de Movimientos

```php
// Movimientos de un slot específico
$movements = $slot->movements()
    ->orderByDesc('recorded_at')
    ->with(['user', 'inventarie', 'inventarieLocationItem'])
    ->get();

foreach ($movements as $move) {
    echo sprintf(
        "[%s] %s: %d → %d unidades (por %s)\n",
        $move->recorded_at,
        $move->movement_type,
        $move->from_quantity,
        $move->to_quantity,
        $move->user->name
    );
}

// Movimientos de una operación de inventario
$moves = InventoryMovement::byInventarie($inventarie->id)
    ->recent(30)
    ->get();
```

### 8.5 Consultas Complejas Jerárquicas

```php
// Todos los slots ocupados en una sede
$occupiedSlots = InventorySlot::whereHas('stand.floor', function ($q) {
    $q->where('inventarie_id', $inventarie->id);
})
->occupied()
->with(['product', 'stand.floor'])
->get();

// Estadísticas de una sede
$stats = [
    'total_floors' => $inventarie->floors()->count(),
    'total_stands' => $inventarie->floors()
        ->sum(\DB::raw('(SELECT COUNT(*) FROM warehouse_stands WHERE warehouse_stands.floor_id = warehouse_floors.id)')),
    'total_slots' => $inventarie->floors()
        ->sum(\DB::raw('(SELECT COUNT(*) FROM warehouse_inventory_slots WHERE warehouse_inventory_slots.stand_id IN (SELECT id FROM warehouse_stands WHERE warehouse_stands.floor_id = warehouse_floors.id))')),
    'occupied_slots' => InventorySlot::whereHas('stand.floor', function ($q) {
        $q->where('inventarie_id', $inventarie->id);
    })
    ->occupied()
    ->count(),
];
```

---

## 9. CAMBIOS EN CONTROLADORES

### 9.1 Nuevo Controlador: `WarehouseIntegrationController.php`

```php
namespace App\Http\Controllers\Managers\Warehouse;

use App\Models\Warehouse\Warehouse;use App\Models\Warehouse\WarehouseInventoryOperation;use App\Models\Warehouse\WarehouseInventoryMovement;

class WarehouseIntegrationController extends Controller
{
    // Sincronizar operación de inventario completa
    public function syncInventarieOperation(
        Warehouse $inventarie,
        WarehouseInventoryOperation $operation
    ) {
        $synced = 0;
        $errors = [];

        foreach ($operation->locations as $location) {
            foreach ($location->items as $item) {
                if ($item->syncToWarehouse(auth()->id(), $inventarie->id)) {
                    $synced++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'synced' => $synced,
            'errors' => $errors,
            'message' => "Se sincronizaron {$synced} productos"
        ]);
    }

    // Ver historial de movimientos de un slot
    public function slotHistory(InventorySlot $slot)
    {
        return response()->json([
            'slot' => [
                'id' => $slot->id,
                'address' => $slot->getAddress(),
                'current_quantity' => $slot->quantity,
            ],
            'movements' => $slot->movements()
                ->orderByDesc('recorded_at')
                ->with(['user', 'inventarie'])
                ->get()
                ->map(fn($m) => [
                    'type' => $m->movement_type,
                    'from_qty' => $m->from_quantity,
                    'to_qty' => $m->to_quantity,
                    'reason' => $m->reason,
                    'user' => $m->user?->name,
                    'inventarie' => $m->inventarie?->name,
                    'date' => $m->recorded_at,
                ])
        ]);
    }

    // Movimientos por sede
    public function inventarieMovements(Warehouse $inventarie)
    {
        return response()->json([
            'inventarie' => [
                'id' => $inventarie->id,
                'name' => $inventarie->name,
            ],
            'movements' => WarehouseInventoryMovement::byInventarie($inventarie->id)
                ->recent(30)
                ->with(['slot.stand.floor', 'user'])
                ->get()
        ]);
    }
}
```

### 9.2 Modificar: `InventorySlotsController.php`

En método `addQuantity()`:

```php
public function addQuantity(Request $request, $uid)
{
    $slot = InventorySlot::where('uid', $uid)->firstOrFail();
    $amount = $request->integer('quantity');

    // Ahora usa el método del modelo con auditoría
    $success = $slot->addQuantity(
        amount: $amount,
        reason: $request->input('reason', 'Manual'),
        userId: auth()->id(),
        inventarieId: $request->input('inventarie_id')  // Opcional
    );

    if (!$success) {
        return response()->json([
            'success' => false,
            'message' => 'No hay suficiente espacio'
        ], 422);
    }

    return response()->json([
        'success' => true,
        'message' => 'Cantidad agregada exitosamente',
        'data' => $slot->getSummary()
    ]);
}
```

---

## 10. RUTAS INTEGRADAS

### 10.1 Rutas de Inventarie (Sede)

```php
Route::group(['prefix' => 'managers/inventaries'], function () {
    // CRUD de Sedes
    Route::get('/', 'InventariesController@index')->name('manager.inventaries');
    Route::post('store', 'InventariesController@store')->name('manager.warehouses.store');
    Route::get('{uid}/edit', 'InventariesController@edit')->name('manager.warehouses.edit');
    Route::post('{uid}/update', 'InventariesController@update')->name('manager.warehouses.update');

    // Estructura de Warehouse dentro de Inventarie
    Route::group(['prefix' => '{inventarie}/warehouse'], function () {
        // Pisos de la sede
        Route::get('floors', 'WarehouseFloorsController@index')->name('manager.inventarie.floors');

        // Estanterías del piso
        Route::get('floors/{floor}/stands', 'WarehouseStandsController@index')
            ->name('manager.inventarie.stands');

        // Posiciones de la estantería
        Route::get('stands/{stand}/slots', 'InventorySlotsController@index')
            ->name('manager.inventarie.slots');
    });

    // Integración con operaciones de inventario
    Route::post('{inventarie}/sync-operation',
        'WarehouseIntegrationController@syncInventarieOperation')
        ->name('manager.inventarie.sync-operation');

    Route::get('{inventarie}/movements',
        'WarehouseIntegrationController@inventarieMovements')
        ->name('manager.inventarie.movements');
});
```

### 10.2 Rutas de Warehouse (Estructura dentro de Inventarie)

```php
Route::group(['prefix' => 'managers/warehouse'], function () {
    // Mapa visual (considerando Inventarie)
    Route::get('map/{inventarie?}', 'WarehouseMapController@map')
        ->name('manager.warehouse.map');

    // Estructura jerárquica
    Route::get('{inventarie}/structure', 'WarehouseStructureController@index')
        ->name('manager.warehouse.structure');

    // Operaciones en slots
    Route::post('slots/{uid}/add-quantity', 'InventorySlotsController@addQuantity')
        ->name('manager.warehouse.slots.add-quantity');

    // Historial
    Route::get('slots/{uid}/movements', 'WarehouseIntegrationController@slotHistory')
        ->name('manager.warehouse.slots.movements');
});
```

---

## 11. RESUMEN DE CAMBIOS

### Archivos a Crear
- ✅ `InventoryMovement.php` (Modelo)
- ✅ `WarehouseIntegrationController.php` (Controlador)
- ✅ 4 Migraciones nuevas

### Archivos a Modificar
- ✅ `Inventarie.php`
- ✅ `Floor.php`
- ✅ `Stand.php`
- ✅ `InventorySlot.php`
- ✅ `InventarieLocationItem.php`
- ✅ `InventorySlotsController.php`
- ✅ `routes/managers.php`

### Cambios Clave
| Cambio | Antes | Después | Beneficio |
|--------|-------|---------|-----------|
| Jerarquía | Floor aislado | Floor → Inventarie | Soporta múltiples sedes |
| Auditoría | Ninguna | InventoryMovement completa | Trazabilidad total |
| Sincronización | Manual | Automática con `syncToWarehouse()` | Menos errores |
| Relación | Ninguna | InventarieLocationItem ↔ InventorySlot | Integración total |

---

**Estado:** Diseño completo - Listo para implementación
**Próximo paso:** Ejecutar migraciones e implementar modelos

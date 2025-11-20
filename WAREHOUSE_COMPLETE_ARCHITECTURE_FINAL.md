# 🏢 ARQUITECTURA COMPLETA: WAREHOUSE + INVENTARIE + LOCATION

**Fecha:** 17 de Noviembre de 2025
**Versión:** 3.0 - Arquitectura Final Integrada
**Estado:** Diseño Final - Listo para Implementación

---

## 📋 ÍNDICE

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Entidades y Sus Roles](#entidades-y-sus-roles)
3. [Jerarquía Completa Corregida](#jerarquía-completa-corregida)
4. [Relaciones Maestro-Detalle](#relaciones-maestro-detalle)
5. [Modelos y Estructura](#modelos-y-estructura)
6. [Base de Datos - Tablas](#base-de-datos---tablas)
7. [Flujos de Creación](#flujos-de-creación)
8. [Flujos de Operación](#flujos-de-operación)
9. [Cambios en Modelos](#cambios-en-modelos)
10. [Plan de Implementación](#plan-de-implementación)

---

## 1. RESUMEN EJECUTIVO

El sistema integra tres capas:

### Capa 1: Maestro (Permanente)
- **Inventarie** = Sede/Sucursal (empresa física)
- **Location** = Ubicación/Sección (área de almacenamiento) = **Stand del modelo Warehouse**
- **InventorySlot** = Posición específica (cara, nivel, sección dentro de Location)
- **Product** = Producto almacenado

### Capa 2: Operación de Inventario (Temporal)
- **InventarieOperation** = Evento de conteo (inventario puntual)
- **InventarieLocation** = Distribución de secciones durante el conteo
- **InventarieLocationItem** = Producto contado en sección

### Capa 3: Estructura Warehouse (Permanente)
- **Floor** = Piso de la sede
- **Stand** = Estantería (equivalente a Location)
- **StandStyle** = Tipo de estantería
- **InventorySlot** = Posición (cara, nivel, sección)

---

## 2. ENTIDADES Y SUS ROLES

### Location.php

**¿Qué es?** Una ubicación/sección física dentro del almacén
**Es lo mismo que?** **Stand** en el modelo Warehouse
**Función:** Representa una zona de almacenamiento que puede contener múltiples productos

**Estructura actual:**
```php
id, uid, product_id, location_id (self-referencing), shop_id, count
```

**Problema:** Tiene `product_id` individual pero debería ser padre de múltiples posiciones
**Solución:** Convertir en contenedor de InventorySlots

---

### InventarieLocation.php

**¿Qué es?** Distribución de una Location para un inventario específico
**Función:** Vincula una Location a una operación de inventario
**Relación:** 1 Location → N InventarieLocations (una Location en múltiples inventarios)

**Estructura actual:**
```php
uid, available, location_id, inventarie_id
└─ hasMany: InventarieLocationItems (productos contados)
```

**Rol en integración:** Gateway entre Location maestro y los productos contados

---

### InventorySlot.php

**¿Qué es?** Posición física exacta dentro de una Location/Stand
**Función:** Representa un contenedor específico (cara, nivel, sección)
**Relaciones:**
- Pertenece a Location (Stand)
- Contiene un Product (opcional)
- Tiene cantidad y peso

**Estructura:**
```php
stand_id, face, level, section, barcode
quantity, max_quantity, weight_current, weight_max
is_occupied, last_movement, last_inventarie_id
```

---

### InventarieLocationItem.php

**¿Qué es?** Registro de un producto contado durante un inventario
**Función:** Captura lo que se contó, dónde, cuándo y quién lo hizo
**Relaciones:**
- Pertenece a InventarieLocation (distribución del conteo)
- Referencia a Product
- Registra usuario que contó
- Registra condición del producto

**Estructura:**
```php
product_id, count, condition_id, user_id
location_id (original donde se encontró)
validate_id (donde fue validado)
```

---

## 3. JERARQUÍA COMPLETA CORREGIDA

### Estructura Permanente (Maestro)

```
INVENTARIE (Sede/Sucursal)
│
├── FLOOR (Piso/Planta)
│   │
│   └── STAND / LOCATION (Ubicación/Sección)
│       │   (Se generan automáticamente)
│       │
│       └── INVENTORY_SLOT (Posición Específica)
│           ├── face (left, right, front, back)
│           ├── level (1, 2, 3, ...)
│           ├── section (1, 2, 3, ...)
│           │
│           └── PRODUCT (Producto Almacenado)
│               ├── quantity (actual)
│               ├── max_quantity (límite)
│               ├── weight_current (kg)
│               └── weight_max (kg)
│
└── INVENTARIE_CONDITION (Catálogo de condiciones)
```

### Estructura de Operación (Temporal)

```
INVENTARIE_OPERATION (Evento de Conteo)
│
└── INVENTARIE_LOCATION (Distribución de ubicaciones)
    │   (1 Location → 1 InventarieLocation por operación)
    │
    └── INVENTARIE_LOCATION_ITEM (Producto Contado)
        ├── product_id
        ├── count (cantidad contada)
        ├── condition_id (estado: nuevo, dañado, etc)
        └── user_id (quién contó)
```

### Sincronización

```
InventarieLocationItem (Conteo)
        ↓ syncToInventorySlot()
    INVENTORY_SLOT (Actualización permanente)
        ↓
    INVENTORY_MOVEMENT (Auditoría)
```

---

## 4. RELACIONES MAESTRO-DETALLE

### Relación 1: Inventarie → Location

```
1 Inventarie
  └─ N Locations (ubicaciones/secciones dentro de la sede)

Ejemplo:
- Inventarie: "Almacén Central"
  ├─ Location: "Pasillo 1"
  ├─ Location: "Pasillo 2"
  ├─ Location: "Isla Central"
  └─ Location: "Pared Norte"
```

### Relación 2: Location → InventorySlot

```
1 Location
  └─ N InventorySlots (posiciones dentro de la ubicación)

Ejemplo:
- Location: "Pasillo 1"
  ├─ InventorySlot: Cara Izquierda - Nivel 1 - Sección 1
  ├─ InventorySlot: Cara Izquierda - Nivel 1 - Sección 2
  ├─ InventorySlot: Cara Izquierda - Nivel 2 - Sección 1
  └─ ... (caras × niveles × secciones)
```

### Relación 3: Inventarie → InventarieLocation

```
1 Inventarie
  └─ N InventarieLocations (mismas ubicaciones en diferentes operaciones)

Ejemplo:
- Inventarie: "Almacén Central"
  ├─ InventarieLocation: Operación Inventario 2025-11-01
  │   └─ Pasillo 1
  ├─ InventarieLocation: Operación Inventario 2025-11-15
  │   └─ Pasillo 1
  └─ InventarieLocation: Operación Inventario 2025-12-01
      └─ Pasillo 1
```

### Relación 4: InventarieLocation → InventarieLocationItem

```
1 InventarieLocation
  └─ N InventarieLocationItems (productos contados en esa ubicación)

Ejemplo:
- InventarieLocation: Pasillo 1 (operación 2025-11-01)
  ├─ InventarieLocationItem: Producto A - 10 unidades
  ├─ InventarieLocationItem: Producto B - 5 unidades
  └─ InventarieLocationItem: Producto C - 0 unidades
```

---

## 5. MODELOS Y ESTRUCTURA

### 5.1 Equivalencias

| Concepto | Location | Warehouse | Función |
|----------|----------|-----------|---------|
| **Ubicación Física** | Location | Stand | Sección del almacén |
| **Posición Dentro** | (no existe) | InventorySlot | Posición exacta (cara, nivel, sección) |
| **Distribución Temporal** | InventarieLocation | (no existe) | Mapeo durante inventario |
| **Producto Contado** | InventarieLocationItem | (no existe) | Registro de lo contado |

### 5.2 Nueva Tabla: `locations` (Modificada)

```sql
CREATE TABLE locations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uid UUID UNIQUE NOT NULL,

    -- Ubicación padre (jerárquica)
    location_id BIGINT NULL,                    -- Self-reference (zona padre)
    floor_id BIGINT NULL,                       -- ⭐ NUEVA: Piso dentro de Inventarie
    inventarie_id BIGINT NULL,                  -- ⭐ NUEVA: Sede asociada

    -- Información
    code VARCHAR(50),                           -- P1A, ISLA1, etc
    title VARCHAR(255),                         -- Nombre legible
    description TEXT,

    -- Configuración física
    style_id BIGINT,                            -- ⭐ NUEVA: Tipo de ubicación
    total_faces INT DEFAULT 1,                  -- Caras (1 = pared, 2 = pasillo, 4 = isla)
    total_levels INT DEFAULT 3,                 -- Niveles de profundidad
    total_sections INT DEFAULT 5,               -- Secciones horizontales

    -- Capacidad
    capacity DECIMAL(10, 2),                    -- Peso máximo
    available BOOLEAN DEFAULT TRUE,

    -- Meta
    shop_id BIGINT,                             -- Tienda asociada
    count INT DEFAULT 0,                        -- ⭐ DEPRECADO (usar InventorySlot.quantity)

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (location_id) REFERENCES locations(id)
        ON DELETE SET NULL,
    FOREIGN KEY (floor_id) REFERENCES warehouse_floors(id)
        ON DELETE SET NULL,
    FOREIGN KEY (inventarie_id) REFERENCES inventaries(id)
        ON DELETE CASCADE,
    FOREIGN KEY (style_id) REFERENCES warehouse_stand_styles(id)
        ON DELETE SET NULL,
    FOREIGN KEY (shop_id) REFERENCES shops(id)
        ON DELETE SET NULL,

    UNIQUE KEY `uq_location_code` (inventarie_id, code),
    INDEX `idx_inventarie_id` (inventarie_id),
    INDEX `idx_floor_id` (floor_id),
    INDEX `idx_available` (available)
);
```

### 5.3 Tabla: `warehouse_inventory_slots` (Vinculada a Location)

```sql
CREATE TABLE warehouse_inventory_slots (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uid UUID UNIQUE NOT NULL,

    -- Relación a Location (no a Stand)
    location_id BIGINT NOT NULL,                -- ⭐ CAMBIADO: de stand_id a location_id
    product_id BIGINT,

    -- Posición dentro de Location
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
    last_inventarie_id BIGINT,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    -- Unicidad: posición única dentro de Location
    UNIQUE KEY `uq_slot_position` (location_id, face, level, section),

    FOREIGN KEY (location_id) REFERENCES locations(id)
        ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE SET NULL,
    FOREIGN KEY (last_inventarie_id) REFERENCES inventaries(id)
        ON DELETE SET NULL,

    INDEX `idx_location_id` (location_id),
    INDEX `idx_product_id` (product_id),
    INDEX `idx_is_occupied` (is_occupied),
    INDEX `idx_location_occupied` (location_id, is_occupied)
);
```

### 5.4 Nueva Tabla: `warehouse_inventory_movements`

```sql
CREATE TABLE warehouse_inventory_movements (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uid UUID UNIQUE NOT NULL,

    slot_id BIGINT NOT NULL,
    product_id BIGINT,

    movement_type ENUM('add', 'subtract', 'clear', 'move', 'count'),
    from_quantity INT DEFAULT 0,
    to_quantity INT DEFAULT 0,
    quantity_delta INT,

    from_weight DECIMAL(10, 2) DEFAULT 0,
    to_weight DECIMAL(10, 2) DEFAULT 0,
    weight_delta DECIMAL(10, 2),

    reason VARCHAR(255),
    inventarie_id BIGINT,
    inventarie_location_item_id BIGINT,        -- ⭐ Link a producto contado
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

---

## 6. BASE DE DATOS - TABLAS

### Tabla Relacional Completa

```
inventaries                          (Sedes)
├─ floors                           (Pisos de sede)
├─ locations                        (Ubicaciones/Secciones) ⭐ MODIFICADA
├─ inventarie_operations            (Operaciones de conteo)
│  └─ inventarie_locations          (Distribución de ubicaciones)
│     └─ inventarie_locations_items (Productos contados)
│
└─ warehouse_inventory_movements    (Auditoría de movimientos)

locations                           (Ubicaciones físicas)
└─ warehouse_inventory_slots        (Posiciones dentro) ⭐ MODIFICADA
   └─ warehouse_inventory_movements (Historial)
   └─ products                      (Productos almacenados)

warehouse_floors                    (Pisos)
└─ locations                        (Ubicaciones en piso)

warehouse_stand_styles              (Tipos de ubicación)
└─ locations                        (Ubicaciones de este tipo)
```

---

## 7. FLUJOS DE CREACIÓN

### Flujo 1: Crear Ubicación (Location/Stand)

```
1️⃣ Crear Location
   ├─ Especificar: Inventarie, Floor, código, nombre
   ├─ Especificar: tipo (style), caras, niveles, secciones
   └─ Validar: código único en inventarie

2️⃣ Location creada
   └─ Trigger automático: crear InventorySlots

3️⃣ Generar InventorySlots
   ├─ Calcular: caras × niveles × secciones
   ├─ Crear posición para cada combinación
   ├─ Asignar: face, level, section
   └─ Guardar: todos con location_id

📊 Resultado: 1 Location → 30 InventorySlots
   (Ej: 2 caras × 3 niveles × 5 secciones)
```

**Código:**
```php
// En Location.php o migration
public static function boot()
{
    parent::boot();

    static::created(function ($location) {
        // Generar InventorySlots automáticamente
        $location->generateSlots();
    });
}

public function generateSlots()
{
    $totalSlots = $this->total_faces * $this->total_levels * $this->total_sections;

    $faces = ['left', 'right', 'front', 'back'];
    $faceCount = 0;

    for ($level = 1; $level <= $this->total_levels; $level++) {
        for ($section = 1; $section <= $this->total_sections; $section++) {
            for ($i = 0; $i < $this->total_faces; $i++) {
                InventorySlot::create([
                    'location_id' => $this->id,
                    'face' => $faces[$i] ?? 'front',
                    'level' => $level,
                    'section' => $section,
                    'barcode' => "SLOT-{$this->code}-{$level}-{$section}-{$i}",
                    'is_occupied' => false,
                    'quantity' => 0,
                    'weight_current' => 0,
                ]);
            }
        }
    }
}
```

---

### Flujo 2: Crear Operación de Inventario

```
1️⃣ Crear InventarieOperation
   ├─ Especificar: Inventarie, fecha, usuario
   └─ Estado: abierta

2️⃣ Crear InventarieLocations (automático)
   ├─ Por cada Location en Inventarie:
   │  └─ Crear InventarieLocation vinculada
   └─ Estado: lista para contar

3️⃣ Usuario cuenta productos
   ├─ Buscar InventarieLocation (por ubicación)
   ├─ Contar productos
   ├─ Crear InventarieLocationItem por producto
   └─ Registrar: cantidad, condición, usuario

4️⃣ Cerrar InventarieOperation
   ├─ Validar: todas las ubicaciones contadas
   ├─ Sincronizar: InventarieLocationItem → InventorySlot
   └─ Crear: InventoryMovements (auditoría)

📊 Resultado: Inventario cerrado + Warehouse actualizado
```

**Código:**
```php
// En InventarieOperation.php
public static function boot()
{
    parent::boot();

    static::created(function ($operation) {
        // Generar InventarieLocations automáticamente
        $operation->generateLocations();
    });
}

public function generateLocations()
{
    $locations = Location::where('inventarie_id', $this->inventarie_id)
        ->where('available', true)
        ->get();

    foreach ($locations as $location) {
        InventarieLocation::create([
            'inventarie_id' => $this->inventarie_id,
            'location_id' => $location->id,
            'operation_id' => $this->id,  // ⭐ NUEVA: link a operación
            'available' => true,
        ]);
    }
}

// Cerrar operación y sincronizar
public function close($userId = null)
{
    foreach ($this->locations as $invLocation) {
        foreach ($invLocation->items as $item) {
            $item->syncToInventorySlot($userId, $this->inventarie_id);
        }
    }

    $this->update([
        'closed_at' => now(),
        'closed_by' => $userId,
    ]);
}
```

---

## 8. FLUJOS DE OPERACIÓN

### Flujo A: Agregar Cantidad a Posición

```
POST /slots/{uid}/add-quantity
Parámetros: { quantity: 5, reason?: "Reposición" }

1️⃣ Validar cantidad disponible
   └─ InventorySlot.canAddQuantity(5)?

2️⃣ Actualizar InventorySlot
   ├─ quantity = quantity + 5
   ├─ is_occupied = true
   └─ last_movement = now()

3️⃣ Crear InventoryMovement (auditoría)
   ├─ movement_type = 'add'
   ├─ from_quantity = X, to_quantity = X+5
   ├─ reason = "Reposición"
   ├─ user_id = auth()->id()
   └─ inventarie_id = (si aplica)

4️⃣ Respuesta JSON
   ├─ success = true
   ├─ data = InventorySlot.getSummary()
   └─ message = "Cantidad agregada exitosamente"
```

---

### Flujo B: Sincronizar Inventario Contado

```
POST /inventaries/{uid}/sync-operation/{operationId}

1️⃣ Obtener operación
   └─ InventarieOperation.find(operationId)

2️⃣ Para cada InventarieLocationItem
   ├─ Obtener InventorySlot correspondiente
   │  └─ Location.id → InventorySlot.location_id
   ├─ Comparar cantidades
   │  └─ Si son diferentes: actualizar
   └─ Crear InventoryMovement
      ├─ movement_type = 'count'
      ├─ from_quantity = slot.quantity
      ├─ to_quantity = item.count
      ├─ inventarie_location_item_id = item.id
      └─ reason = "Sincronización de inventario"

3️⃣ Marcar como sincronizado
   └─ InventarieLocationItem.synced_at = now()

4️⃣ Respuesta
   ├─ success = true
   ├─ synced = N (cantidad de items sincronizados)
   └─ movements = [...]
```

---

## 9. CAMBIOS EN MODELOS

### 9.1 Modificar: `Location.php`

```php
namespace App\Models;

use App\Models\Warehouse\WarehouseInventorySlot;

class Location extends Model
{
    use HasFactory, HasUid;

    protected $fillable = [
        'uid', 'location_id', 'floor_id', 'inventarie_id',  // ⭐ NUEVOS
        'code', 'title', 'description',
        'style_id', 'total_faces', 'total_levels', 'total_sections',
        'capacity', 'available', 'shop_id', 'count'
    ];

    // ⭐ NUEVA: Inventarie que contiene esta ubicación
    public function inventarie()
    {
        return $this->belongsTo('App\Models\Warehouse\Warehouse', 'inventarie_id');
    }

    // ⭐ NUEVA: Floor del almacén
    public function floor()
    {
        return $this->belongsTo('App\Models\Warehouse\WarehouseFloor', 'floor_id');
    }

    // ⭐ NUEVA: Tipo/estilo de ubicación
    public function style()
    {
        return $this->belongsTo('App\Models\Warehouse\WarehouseLocationStyle', 'style_id');
    }

    // Existente: Self-reference
    public function parent()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    // Existente: Shop
    public function shop()
    {
        return $this->belongsTo('App\Models\Shop', 'shop_id');
    }

    // ⭐ NUEVA: InventorySlots dentro de esta ubicación
    public function slots()
    {
        return $this->hasMany(WarehouseInventorySlot::class, 'location_id');
    }

    // ⭐ NUEVA: Generar posiciones automáticamente
    public function generateSlots()
    {
        $faces = ['left', 'right', 'front', 'back'];
        $usableFaces = array_slice($faces, 0, $this->total_faces);

        for ($level = 1; $level <= $this->total_levels; $level++) {
            for ($section = 1; $section <= $this->total_sections; $section++) {
                foreach ($usableFaces as $face) {
                    WarehouseInventorySlot::create([
                        'location_id' => $this->id,
                        'face' => $face,
                        'level' => $level,
                        'section' => $section,
                        'barcode' => "SLOT-{$this->code}-L{$level}S{$section}{$face[0]}",
                        'is_occupied' => false,
                    ]);
                }
            }
        }
    }

    // ⭐ NUEVA: Boot para generar slots al crear
    protected static function boot()
    {
        parent::boot();

        static::created(function ($location) {
            if ($location->total_faces && $location->total_levels && $location->total_sections) {
                $location->generateSlots();
            }
        });
    }

    // ⭐ NUEVA: Obtener información completa
    public function getHierarchy()
    {
        return [
            'location' => [
                'id' => $this->id,
                'code' => $this->code,
                'title' => $this->title,
                'inventarie' => $this->inventarie?->name,
                'floor' => $this->floor?->name,
            ],
            'configuration' => [
                'faces' => $this->total_faces,
                'levels' => $this->total_levels,
                'sections' => $this->total_sections,
                'total_slots' => $this->total_faces * $this->total_levels * $this->total_sections,
            ],
            'status' => [
                'occupied_slots' => $this->slots()->occupied()->count(),
                'available_slots' => $this->slots()->available()->count(),
                'occupancy_percentage' => round(
                    ($this->slots()->occupied()->count() / ($this->total_faces * $this->total_levels * $this->total_sections)) * 100,
                    2
                ),
            ]
        ];
    }

    // Scopes
    public function scopeByInventarie($query, $inventarieId)
    {
        return $query->where('inventarie_id', $inventarieId);
    }

    public function scopeByFloor($query, $floorId)
    {
        return $query->where('floor_id', $floorId);
    }
}
```

---

### 9.2 Modificar: `InventorySlot.php`

```php
namespace App\Models\Warehouse;

use App\Models\Location;

class InventorySlot extends Model
{
    use HasFactory, HasUid;

    protected $fillable = [
        'uid', 'location_id',  // ⭐ CAMBIO: de stand_id a location_id
        'product_id', 'face', 'level', 'section', 'barcode',
        'quantity', 'max_quantity', 'weight_current', 'weight_max',
        'is_occupied', 'last_movement', 'last_inventarie_id'
    ];

    // ⭐ CAMBIO: Relación a Location en lugar de Stand
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function product()
    {
        return $this->belongsTo('App\Models\Product\Product', 'product_id');
    }

    public function movements()
    {
        return $this->hasMany(WarehouseInventoryMovement::class, 'slot_id');
    }

    // ⭐ NUEVA: Obtener dirección amigable
    public function getAddress(): string
    {
        return "{$this->location?->code} / {$this->getFaceLabel()} / N{$this->level} / S{$this->section}";
    }

    // Métodos existentes de validación y operación...
    // (addQuantity, subtractQuantity, canAddQuantity, etc.)
}
```

---

### 9.3 Modificar: `InventarieLocation.php`

```php
namespace App\Models\Inventarie;

use App\Models\Location;use App\Models\Warehouse\Warehouse;use App\Models\Warehouse\InventarieLocationItem;

class InventarieLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid', 'available',
        'location_id', 'inventarie_id',
        'operation_id',  // ⭐ NUEVA: Link a operación
    ];

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function inventarie()
    {
        return $this->belongsTo(Warehouse::class, 'inventarie_id');
    }

    // ⭐ NUEVA: Operación de inventario
    public function operation()
    {
        return $this->belongsTo('App\Models\Warehouse\WarehouseInventoryOperation', 'operation_id');
    }

    public function items()
    {
        return $this->hasMany(InventarieLocationItem::class, 'location_id');
    }
}
```

---

### 9.4 Crear: `InventarieOperation.php`

```php
namespace App\Models\Inventarie;

use App\Models\Warehouse\Warehouse;use App\Models\Warehouse\InventarieLocation;use Illuminate\Database\Eloquent\Model;

class InventarieOperation extends Model
{
    protected $fillable = [
        'uid', 'inventarie_id', 'user_id', 'started_at', 'closed_at', 'closed_by'
    ];

    public function inventarie()
    {
        return $this->belongsTo(Warehouse::class, 'inventarie_id');
    }

    public function locations()
    {
        return $this->hasMany(InventarieLocation::class, 'operation_id');
    }

    // ⭐ NUEVA: Generar ubicaciones automáticamente
    public function generateLocations()
    {
        $locations = Location::where('inventarie_id', $this->inventarie_id)
            ->where('available', true)
            ->get();

        foreach ($locations as $location) {
            InventarieLocation::create([
                'inventarie_id' => $this->inventarie_id,
                'location_id' => $location->id,
                'operation_id' => $this->id,
                'available' => true,
            ]);
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($operation) {
            $operation->generateLocations();
        });
    }

    // ⭐ NUEVA: Cerrar operación
    public function close($userId = null)
    {
        foreach ($this->locations as $location) {
            foreach ($location->items as $item) {
                $item->syncToInventorySlot($userId, $this->inventarie_id);
            }
        }

        $this->update([
            'closed_at' => now(),
            'closed_by' => $userId,
        ]);
    }
}
```

---

### 9.5 Crear: `InventoryMovement.php`

```php
namespace App\Models\Warehouse;

use App\Library\Traits\HasUid;use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory, HasUid;

    protected $table = 'warehouse_inventory_movements';

    protected $fillable = [
        'uid', 'slot_id', 'product_id', 'movement_type',
        'from_quantity', 'to_quantity', 'quantity_delta',
        'from_weight', 'to_weight', 'weight_delta',
        'reason', 'inventarie_id', 'inventarie_location_item_id',
        'user_id', 'notes', 'recorded_at'
    ];

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
        return $this->belongsTo('App\Models\Warehouse\Warehouse', 'inventarie_id');
    }

    public function inventarieItem()
    {
        return $this->belongsTo('App\Models\Warehouse\InventarieLocationItem', 'inventarie_location_item_id');
    }

    // Scopes
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
}
```

---

## 10. PLAN DE IMPLEMENTACIÓN

### Fase 1: Preparación (1 día)

#### Paso 1: Crear migraciones
```
CreateInventarieOperationsTable
ModifyLocationsTable (agregar FK a inventarie, floor, style)
ModifyInventorySlots Table (cambiar stand_id a location_id)
CreateWarehouseInventoryMovementsTable
ModifyInventarieLocationsItemsTable (agregar operation_id, sync fields)
```

#### Paso 2: Crear modelos nuevos
```
InventarieOperation.php
InventoryMovement.php
```

#### Paso 3: Actualizar modelos existentes
```
Location.php (agregar relaciones y métodos)
InventorySlot.php (cambiar a location_id)
InventarieLocation.php (agregar operation_id)
InventarieLocationItem.php (agregar sync)
```

---

### Fase 2: Migración de Datos (1-2 días)

```sql
-- 1. Actualizar locations existentes
UPDATE locations
SET inventarie_id = 1  -- Asumir sede principal
WHERE inventarie_id IS NULL;

-- 2. Generar InventorySlots para locations existentes
-- (Usar seeder o comando artisan)

-- 3. Migrar datos de Stand a Location si existen
-- (Si hay stands previos, convertirlos a locations)
```

---

### Fase 3: Testing (1-2 días)

- Test unitarios de modelos
- Test de relaciones
- Test de sincronización
- Test de auditoría

---

### Fase 4: Actualización de Código (2-3 días)

- Actualizar controladores
- Actualizar vistas
- Actualizar rutas

---

## RESUMEN VISUAL FINAL

```
┌─────────────────────────────────────────────────────────────┐
│                    ARQUITECTURA INTEGRADA                   │
└─────────────────────────────────────────────────────────────┘

       INVENTARIE (Sede)
            │
    ┌───────┼───────┐
    │       │       │
  FLOOR  LOCATION  INVENTARIE_OPERATION
    │    (Stand)       │
    │       │          └─ INVENTARIE_LOCATION
    │    INVENTORY_     │
    │      SLOT        └─ INVENTARIE_LOCATION_ITEM
    │       │                │
    │    PRODUCT         syncToInventorySlot()
    │       │                │
    └───────┴────────────────┘
            │
      INVENTORY_MOVEMENT
        (Auditoría)
```

---

**Estado:** ✅ Diseño Final Completo
**Próximo:** Implementación de cambios en código
**Duración estimada:** 5-7 días laborales


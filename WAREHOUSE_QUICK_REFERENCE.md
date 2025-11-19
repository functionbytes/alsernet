# ⚡ WAREHOUSE - QUICK REFERENCE

**Cheat Sheet rápido para usar el sistema de almacén**

---

## 🏢 FLOOR (Piso)

### Obtener
```php
$floor = Floor::find(1);
$floor = Floor::where('code', 'P1')->first();
$floors = Floor::active()->ordered()->get();
```

### Crear
```php
Floor::create([
    'uid' => Str::uuid(),
    'code' => 'P4',
    'name' => 'Planta 4',
    'available' => true,
]);
```

### Scopes
```php
Floor::active()              // Pisos activos
Floor::ordered()             // Ordenado por orden y nombre
Floor::byCode('P1')          // Buscar por código
Floor::search('Planta')      // Búsqueda parcial
```

### Helpers
```php
$floor->getStandCount()             // int - número de stands
$floor->getActiveStandCount()       // int - stands activos
$floor->getTotalSlotsCount()        // int - total posiciones
$floor->getOccupiedSlotsCount()     // int - posiciones ocupadas
$floor->getOccupancyPercentage()    // float - % ocupación
$floor->getSummary()                // array - resumen completo
$floor->stands                      // Collection - todas las estanterías
```

---

## 🏗️ STAND_STYLE (Estilo)

### Obtener
```php
$style = StandStyle::find(1);
$style = StandStyle::where('code', 'ROW')->first();
```

### Crear
```php
StandStyle::create([
    'uid' => Str::uuid(),
    'code' => 'ROW',
    'name' => 'Pasillo Lineal',
    'faces' => ['left', 'right'],
    'default_levels' => 4,
    'default_sections' => 6,
]);
```

### Tipos
```php
'ROW'    // Pasillo lineal (2 caras)
'ISLAND' // Isla (4 caras)
'WALL'   // Pared (1 cara)
```

### Helpers
```php
$style->getTypeName()           // string - descripción
$style->getFacesLabel()         // string - caras en texto
$style->hasValidFaces()         // bool - validez de caras
$style->getStandCount()         // int - número de stands
$style->getActiveStandCount()   // int - stands activos
$style->getSummary()            // array - resumen
```

---

## 📦 STAND (Estantería)

### Obtener
```php
$stand = Stand::find(1);
$stand = Stand::where('code', 'PASILLO13A')->first();
$stands = Stand::byFloor(1)->available()->get();
```

### Crear
```php
$stand = Stand::create([
    'uid' => Str::uuid(),
    'floor_id' => 1,
    'stand_style_id' => 1,
    'code' => 'PASILLO14A',
    'position_x' => 14,
    'position_y' => 2,
    'total_levels' => 4,
    'total_sections' => 6,
    'capacity' => 500.00,
]);

// Crear automáticamente todas las posiciones
$stand->createSlots();  // Returns: 48 (si ROW con 2×4×6)
```

### Scopes
```php
Stand::active()                     // Activas
Stand::byFloor(1)                   // Por piso
Stand::byCode('PASILLO13A')         // Por código
Stand::byBarcode('BAR-P1-13A')      // Por código de barras
Stand::byStyle(1)                   // Por estilo
Stand::search('PASILLO')            // Búsqueda parcial
Stand::ordered()                    // Ordenado por posición
```

### Helpers
```php
$stand->getFullName()               // "PASILLO13A (Planta 1)"
$stand->getTotalSlots()             // int - total posiciones
$stand->getOccupiedSlots()          // int - ocupadas
$stand->getAvailableSlots()         // int - libres
$stand->getOccupancyPercentage()    // float - % ocupación
$stand->getCurrentWeight()          // float - kg actuales
$stand->isNearCapacity()            // bool - ¿cerca del límite?
$stand->isNearCapacity(80)          // bool - ¿al 80% o más?
$stand->getSlot('left', 2, 3)       // InventorySlot - posición específica
$stand->getSlotsByFace('left')      // Collection - todas de una cara
$stand->getSlotsByLevel(2)          // Collection - todas de un nivel
$stand->getSummary()                // array - resumen completo
$stand->floor                       // Floor - piso que contiene
$stand->style                       // StandStyle - estilo
$stand->slots                       // Collection - todas las posiciones
```

---

## 📍 INVENTORY_SLOT (Posición)

### Obtener
```php
$slot = InventorySlot::find(1);
$slot = InventorySlot::where('barcode', 'SLOT-001000')->first();
$slots = InventorySlot::byStand(1)->available()->get();
```

### Crear
```php
InventorySlot::create([
    'uid' => Str::uuid(),
    'stand_id' => 1,
    'face' => 'left',
    'level' => 2,
    'section' => 3,
    'barcode' => 'SLOT-001000',
    'max_quantity' => 100,
    'weight_max' => 50.00,
]);
```

### Scopes
```php
InventorySlot::occupied()                   // Ocupadas
InventorySlot::available()                  // Libres
InventorySlot::byStand(1)                   // De un stand
InventorySlot::byProduct(1)                 // Con un producto
InventorySlot::byFace('left')               // De una cara
InventorySlot::byLevel(2)                   // De un nivel
InventorySlot::byBarcode('SLOT-001000')     // Por código
InventorySlot::search('001')                // Búsqueda
InventorySlot::nearWeightCapacity(90)       // Cerca límite peso
InventorySlot::overCapacity()               // Excede peso
InventorySlot::overQuantity()               // Excede cantidad
```

### Información
```php
$slot->getAddress()                 // "PASILLO13A / Izq. / Nivel 2 / Secc. 3"
$slot->getFaceLabel()               // "Izquierda"
$slot->isOccupied()                 // bool
$slot->isAvailable()                // bool
$slot->stand                        // Stand instance
$slot->product                      // Product instance o null
$slot->face                         // 'left', 'right', 'front', 'back'
$slot->level                        // int (1, 2, 3...)
$slot->section                      // int (1, 2, 3...)
$slot->quantity                     // int
$slot->max_quantity                 // int
$slot->weight_current              // float (kg)
$slot->weight_max                  // float (kg)
$slot->is_occupied                 // bool (cache)
$slot->last_movement               // timestamp
```

### Capacidad
```php
$slot->getAvailableQuantity()       // int - cuánta cantidad puedo agregar
$slot->getAvailableWeight()         // float - cuánto peso puedo agregar
$slot->getWeightPercentage()        // float - % de peso usado (0-100)
$slot->getQuantityPercentage()      // float - % de cantidad usado (0-100)
$slot->canAddQuantity(10)           // bool - ¿se pueden agregar 10?
$slot->canAddWeight(5.5)            // bool - ¿se pueden agregar 5.5 kg?
$slot->isNearQuantityCapacity()     // bool - ¿cerca límite cantidad (90%)?
$slot->isNearQuantityCapacity(80)   // bool - ¿al 80% o más de cantidad?
$slot->isNearWeightCapacity()       // bool - ¿cerca límite peso (90%)?
$slot->isNearWeightCapacity(80)     // bool - ¿al 80% o más de peso?
$slot->isOverQuantity()             // bool - ¿excede cantidad máxima?
$slot->isOverWeight()               // bool - ¿excede peso máximo?
```

### Operaciones
```php
$slot->addQuantity(10)              // bool - agregar cantidad
$slot->subtractQuantity(5)          // bool - restar cantidad
$slot->addWeight(2.5)               // bool - agregar peso (kg)
$slot->subtractWeight(1.0)          // bool - restar peso (kg)
$slot->clear()                      // void - vaciar completamente
```

### Información Completa
```php
$slot->getFullInfo()                // array - información detallada
$slot->getSummary()                 // array - resumen simplificado
```

---

## 🔍 BÚSQUEDAS COMUNES

### Encontrar Posición Disponible
```php
$available = InventorySlot::byStand(1)
    ->available()
    ->first();
```

### Posiciones Ocupadas de un Stand
```php
$occupied = InventorySlot::byStand(1)
    ->occupied()
    ->get();
```

### Posiciones por Cara
```php
$leftSide = Stand::find(1)
    ->getSlotsByFace('left');
```

### Posiciones por Nivel
```php
$level2 = Stand::find(1)
    ->getSlotsByLevel(2);
```

### Posiciones Cerca de Capacidad
```php
$overloaded = InventorySlot::nearWeightCapacity(85)->get();
```

### Posiciones que Exceden Capacidad
```php
$critical = InventorySlot::overCapacity()->get();
```

---

## 💼 OPERACIONES TÍPICAS

### Agregar Producto a una Posición
```php
$slot = InventorySlot::byBarcode('SLOT-001000')->first();

if ($slot->canAddQuantity(50) && $slot->canAddWeight(10.0)) {
    $slot->update(['product_id' => 1]);
    $slot->addQuantity(50);
    $slot->addWeight(10.0);
}
```

### Mover Producto entre Posiciones
```php
$from = InventorySlot::find(1);
$to = InventorySlot::find(2);

if ($to->canAddQuantity($from->quantity)) {
    $from->subtractQuantity($from->quantity);
    $to->update(['product_id' => $from->product_id]);
    $to->addQuantity($from->quantity);
}
```

### Vaciar una Posición
```php
$slot = InventorySlot::find(1);
$slot->clear();  // Elimina producto, cantidad y peso
```

### Ver Ocupación de un Piso
```php
$floor = Floor::find(1);
echo $floor->getOccupancyPercentage();  // 75.5
```

### Crear Estantería Completa
```php
$stand = Stand::create([...]);  // Ver ejemplo en STAND
$stand->createSlots();          // Crear todas las posiciones automáticamente
```

---

## 📊 ESTADÍSTICAS

### Piso
```php
$summary = $floor->getSummary();
// Retorna:
// - stands_count, active_stands_count
// - total_slots, occupied_slots
// - occupancy_percentage
```

### Estantería
```php
$summary = $stand->getSummary();
// Retorna:
// - full_name, floor, style
// - dimensions (levels, sections)
// - capacity, current_weight
// - total_slots, occupied_slots, available_slots
// - occupancy_percentage, near_capacity
```

### Posición
```php
$info = $slot->getFullInfo();
// Retorna información detallada incluyendo:
// - address, position (face, level, section)
// - product info
// - quantity (current, max, available, percentage)
// - weight (current, max, available, percentage)
// - is_occupied, is_available, last_movement
```

---

## ⚙️ CONFIGURACIÓN

### Constantes Disponibles
```php
// Caras
StandStyle::FACE_LEFT    // 'left'
StandStyle::FACE_RIGHT   // 'right'
StandStyle::FACE_FRONT   // 'front'
StandStyle::FACE_BACK    // 'back'

// Tipos de Estilo
StandStyle::TYPE_ROW     // 'ROW'
StandStyle::TYPE_ISLAND  // 'ISLAND'
StandStyle::TYPE_WALL    // 'WALL'
```

---

## 🛠️ INSTALACIÓN RÁPIDA

```bash
# 1. Ejecutar migraciones
php artisan migrate

# 2. Ejecutar seeders (recomendado)
php artisan db:seed --class=WarehouseSeeder

# 3. Verificar
php artisan tinker
>>> App\Models\Warehouse\Floor::count();
4
```

---

## 📚 DOCUMENTACIÓN COMPLETA

Para más detalles:
- **WAREHOUSE_ARCHITECTURE.md** - Documentación completa
- **WAREHOUSE_SETUP_GUIDE.md** - Guía de instalación
- **app/Models/Warehouse/** - Código fuente

---

**Última actualización:** 2025-11-17
**Framework:** Laravel 11.42

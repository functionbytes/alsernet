# 🏭 ARQUITECTURA DE GESTIÓN DE ALMACÉN

**Proyecto:** WebAdmin - A-Álvarez
**Módulo:** Warehouse Management System
**Framework:** Laravel 11.42
**Fecha:** 2025-11-17

---

## 📑 TABLA DE CONTENIDOS

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Modelo Relacional](#modelo-relacional)
3. [Entidades](#entidades)
4. [Relaciones Eloquent](#relaciones-eloquent)
5. [Uso de Modelos](#uso-de-modelos)
6. [Scopes Disponibles](#scopes-disponibles)
7. [Métodos Helpers](#métodos-helpers)
8. [Instalación & Migración](#instalación--migración)
9. [Ejemplos Prácticos](#ejemplos-prácticos)
10. [Próximos Pasos](#próximos-pasos)

---

## 🎯 Resumen Ejecutivo

Sistema de **gestión de almacén modular y escalable** que permite:

- ✅ Organizar pisos/plantas del almacén
- ✅ Definir estilos/tipos de estanterías
- ✅ Ubicar estanterías físicas con coordenadas
- ✅ Gestionar posiciones de inventario (slots) de forma granular
- ✅ Rastrear productos, cantidades y pesos por posición
- ✅ Auditoría completa de movimientos

### Características Técnicas

| Aspecto | Detalles |
|---------|----------|
| **Migraciones** | 4 tablas (floors, stand_styles, stands, inventorie_slots) |
| **Modelos** | 4 modelos Eloquent con relaciones completas |
| **Seeders** | 5 seeders (4 específicos + 1 maestro) |
| **Índices** | Optimizados para búsquedas frecuentes |
| **Auditoría** | Timestamps automáticos (created_at, updated_at) |
| **UID** | UUID universal para URLs/APIs |

---

## 📊 Modelo Relacional

```
┌──────────────────────────────────────────────────────┐
│                 WAREHOUSE SCHEMA                      │
└──────────────────────────────────────────────────────┘

floors (Pisos)
├── id: int (PK)
├── uid: uuid (unique)
├── code: varchar (P1, P2, S0)
├── name: varchar (Planta 1, Sótano)
├── description: text
├── available: boolean
├── order: int
└── timestamps

         1:M
         │
         ▼

stands (Estanterías)
├── id: int (PK)
├── uid: uuid (unique)
├── floor_id: int (FK → floors)
├── stand_style_id: int (FK → stand_styles)
├── code: varchar (PASILLO13A)
├── barcode: varchar (código físico)
├── position_x, y, z: int (coordenadas)
├── total_levels, sections: int
├── capacity: decimal (peso máximo)
├── available: boolean
├── notes: text
└── timestamps

         1:M ◄─┐
         │      │
         │      └─ stand_styles (Estilos)
         │         ├── id: int (PK)
         │         ├── uid: uuid
         │         ├── code: varchar (ROW, ISLAND, WALL)
         │         ├── name: varchar
         │         ├── faces: json (caras disponibles)
         │         ├── default_levels, sections: int
         │         ├── available: boolean
         │         └── timestamps
         │
         ▼

inventorie_slots (Posiciones)
├── id: int (PK)
├── uid: uuid (unique)
├── stand_id: int (FK → stands)
├── product_id: int (FK → products, nullable)
├── face: enum (left, right, front, back)
├── level: int (profundidad)
├── section: int (ancho)
├── barcode: varchar (código de posición)
├── quantity: int (cantidad actual)
├── max_quantity: int (máximo)
├── weight_current: decimal (peso actual)
├── weight_max: decimal (máximo)
├── is_occupied: boolean (cache)
├── last_movement: timestamp
└── timestamps
```

---

## 🏗️ Entidades

### 1. Floor (Piso)

Representa un **nivel/planta del almacén**.

```php
$floor = Floor::find(1);

// Obtener datos básicos
$floor->code;        // "P1"
$floor->name;        // "Planta 1"
$floor->available;   // true

// Relaciones
$floor->stands;      // Collection de Stand

// Helpers
$floor->getStandCount();           // int
$floor->getActiveStandCount();     // int
$floor->getTotalSlotsCount();      // int
$floor->getOccupiedSlotsCount();   // int
$floor->getOccupancyPercentage();  // float (0-100)
$floor->getSummary();              // array con resumen
```

### 2. StandStyle (Estilo de Estantería)

Define el **tipo y características de una estantería**.

```php
$style = StandStyle::where('code', 'ROW')->first();

// Obtener datos
$style->code;                      // "ROW"
$style->name;                      // "Pasillo Lineal"
$style->faces;                     // ["left", "right"]
$style->default_levels;            // 3
$style->default_sections;          // 5

// Helpers
$style->getTypeName();             // "Pasillo Lineal"
$style->getFacesLabel();           // "Izquierda, Derecha"
$style->hasValidFaces();           // bool
$style->getStandCount();           // int
$style->getSummary();              // array
```

### 3. Stand (Estantería Física)

Representa una **estantería concreta en el almacén**.

```php
$stand = Stand::where('code', 'PASILLO13A')->first();

// Obtener datos
$stand->code;                      // "PASILLO13A"
$stand->floor;                     // Floor instance
$stand->style;                     // StandStyle instance
$stand->position_x;                // 3
$stand->position_y;                // 2
$stand->total_levels;              // 4
$stand->total_sections;            // 6
$stand->capacity;                  // 500.00 kg

// Relaciones
$stand->slots;                     // Collection de InventorySlot

// Helpers
$stand->getFullName();             // "PASILLO13A (Planta 1)"
$stand->getTotalSlots();           // 48 (caras × niveles × secciones)
$stand->getOccupiedSlots();        // int
$stand->getAvailableSlots();       // int
$stand->getOccupancyPercentage();  // float
$stand->getCurrentWeight();        // float (kg)
$stand->isNearCapacity();          // bool

// Buscar posiciones específicas
$stand->getSlot('left', 2, 3);     // InventorySlot
$stand->getSlotsByFace('left');    // Collection
$stand->getSlotsByLevel(2);        // Collection

// Crear posiciones (útil al crear una estantería)
$stand->createSlots();             // int (número de creadas)

// Resumen
$stand->getSummary();              // array detallado
```

### 4. InventorySlot (Posición de Inventario)

Una **posición concreta** dentro de una estantería.

```php
$slot = InventorySlot::where('barcode', 'SLOT-001000')->first();

// Ubicación
$slot->stand;                      // Stand instance
$slot->face;                       // "left"
$slot->level;                      // 2
$slot->section;                    // 3
$slot->getAddress();               // "PASILLO13A / Izquierda / Nivel 2 / Sección 3"

// Contenido
$slot->product;                    // Product instance (puede ser null)
$slot->product_id;                 // int
$slot->quantity;                   // int
$slot->max_quantity;               // int

// Peso
$slot->weight_current;             // decimal (kg)
$slot->weight_max;                 // decimal (kg)
$slot->getWeightPercentage();      // float (0-100)

// Estado
$slot->is_occupied;                // bool
$slot->isOccupied();               // bool
$slot->isAvailable();              // bool
$slot->last_movement;              // timestamp

// Verificaciones de capacidad
$slot->getAvailableQuantity();     // int
$slot->getAvailableWeight();       // float (kg)
$slot->canAddQuantity(10);         // bool
$slot->canAddWeight(5.5);          // bool
$slot->isNearQuantityCapacity();   // bool (90% por defecto)
$slot->isNearWeightCapacity();     // bool
$slot->isOverQuantity();           // bool
$slot->isOverWeight();             // bool

// Operaciones
$slot->addQuantity(10);            // bool
$slot->subtractQuantity(5);        // bool
$slot->addWeight(2.5);             // bool
$slot->subtractWeight(1.0);        // bool
$slot->clear();                    // void (vaciar completamente)

// Información
$slot->getFullInfo();              // array detallado
$slot->getSummary();               // array simplificado
```

---

## 🔗 Relaciones Eloquent

### Floor → Stands (1:M)

```php
$floor = Floor::find(1);
$stands = $floor->stands;          // Collection de todos los stands

// Con scopes
$stands = $floor->stands()->available()->get();
```

### StandStyle → Stands (1:M)

```php
$style = StandStyle::where('code', 'ROW')->first();
$stands = $style->stands;          // Collection de stands con este estilo
```

### Stand → Floor (M:1)

```php
$stand = Stand::find(1);
$floor = $stand->floor;            // Floor instance
```

### Stand → StandStyle (M:1)

```php
$stand = Stand::find(1);
$style = $stand->style;            // StandStyle instance
```

### Stand → InventorySlots (1:M)

```php
$stand = Stand::find(1);
$slots = $stand->slots;            // Collection de todas las posiciones

// Con scopes
$occupied = $stand->slots()->occupied()->get();
$available = $stand->slots()->available()->get();
```

### InventorySlot → Stand (M:1)

```php
$slot = InventorySlot::find(1);
$stand = $slot->stand;             // Stand instance
```

### InventorySlot → Product (M:1)

```php
$slot = InventorySlot::find(1);
$product = $slot->product;         // Product instance (puede ser null)
```

---

## 📖 Uso de Modelos

### Crear un Piso

```php
use App\Models\Warehouse\Floor;
use Illuminate\Support\Str;

$floor = Floor::create([
    'uid' => Str::uuid(),
    'code' => 'P4',
    'name' => 'Planta 4',
    'description' => 'Nueva planta de expansión',
    'available' => true,
    'order' => 4,
]);
```

### Crear un Estilo de Estantería

```php
use App\Models\Warehouse\StandStyle;
use Illuminate\Support\Str;

$style = StandStyle::create([
    'uid' => Str::uuid(),
    'code' => 'COMPACT',
    'name' => 'Almacenamiento Compacto',
    'description' => 'Sistema de almacenamiento vertical compacto',
    'faces' => ['front', 'back'],
    'default_levels' => 6,
    'default_sections' => 4,
    'available' => true,
]);
```

### Crear una Estantería

```php
use App\Models\Warehouse\Stand;
use Illuminate\Support\Str;

$stand = Stand::create([
    'uid' => Str::uuid(),
    'floor_id' => 1,
    'stand_style_id' => 1,
    'code' => 'PASILLO14A',
    'barcode' => 'BAR-P1-14A',
    'position_x' => 14,
    'position_y' => 2,
    'position_z' => 0,
    'total_levels' => 4,
    'total_sections' => 6,
    'capacity' => 550.00,
    'available' => true,
    'notes' => 'Nuevo pasillo de acceso rápido',
]);

// Crear automáticamente todas las posiciones
$createdSlots = $stand->createSlots();  // Returns: 48 (2 caras × 4 niveles × 6 secciones)
```

### Crear una Posición Manualmente

```php
use App\Models\Warehouse\InventorySlot;
use Illuminate\Support\Str;

$slot = InventorySlot::create([
    'uid' => Str::uuid(),
    'stand_id' => 1,
    'face' => 'left',
    'level' => 2,
    'section' => 3,
    'barcode' => 'SLOT-001042',
    'quantity' => 0,
    'max_quantity' => 100,
    'weight_current' => 0,
    'weight_max' => 50.00,
]);
```

### Buscar Posiciones

```php
// Por dirección completa
$slot = Stand::find(1)
    ->getSlot('left', 2, 3);

// Todas las posiciones de una cara
$slots = Stand::find(1)
    ->getSlotsByFace('left');

// Todas las posiciones de un nivel
$slots = Stand::find(1)
    ->getSlotsByLevel(2);

// Por código de barras
$slot = InventorySlot::where('barcode', 'SLOT-001000')
    ->first();

// Búsqueda por stand
$slots = InventorySlot::byStand(1)->get();

// Posiciones ocupadas
$occupied = InventorySlot::byStand(1)
    ->occupied()
    ->get();

// Posiciones libres
$available = InventorySlot::byStand(1)
    ->available()
    ->get();
```

### Operaciones con Posiciones

```php
$slot = InventorySlot::find(1);

// Agregar cantidad
$slot->addQuantity(10);            // bool: true si se pudo agregar

// Restar cantidad
$slot->subtractQuantity(5);        // bool: true si se pudo restar

// Agregar peso
$slot->addWeight(2.5);             // bool: true si se pudo agregar

// Restar peso
$slot->subtractWeight(1.0);        // bool: true si se pudo restar

// Vaciar completamente
$slot->clear();                    // void: vacía la posición

// Verificar capacidad antes de agregar
if ($slot->canAddQuantity(10)) {
    $slot->addQuantity(10);
} else {
    // Manejar error: no hay capacidad
}

// Verificar límites
if ($slot->isOverQuantity()) {
    // La cantidad excede el máximo permitido
}

if ($slot->isNearQuantityCapacity(80)) {
    // Está al 80% o más de la capacidad de cantidad
}
```

---

## 🎯 Scopes Disponibles

### Floor Scopes

```php
// Pisos activos
Floor::active()->get();

// Ordenados por orden y nombre
Floor::ordered()->get();

// Buscar por código
Floor::byCode('P1')->first();

// Búsqueda parcial
Floor::search('Planta')->get();
```

### StandStyle Scopes

```php
// Estilos activos
StandStyle::active()->get();

// Buscar por código
StandStyle::byCode('ROW')->first();

// Búsqueda parcial
StandStyle::search('pasillo')->get();
```

### Stand Scopes

```php
// Estanterías activas
Stand::active()->get();

// Buscar por piso
Stand::byFloor(1)->get();

// Buscar por código
Stand::byCode('PASILLO13A')->first();

// Buscar por código de barras
Stand::byBarcode('BAR-P1-13A')->first();

// Buscar por estilo
Stand::byStyle(1)->get();

// Búsqueda general (código o barcode)
Stand::search('PASILLO')->get();

// Ordenados por posición
Stand::ordered()->get();
```

### InventorySlot Scopes

```php
// Posiciones ocupadas
InventorySlot::occupied()->get();

// Posiciones libres
InventorySlot::available()->get();

// Buscar por stand
InventorySlot::byStand(1)->get();

// Buscar por producto
InventorySlot::byProduct(1)->get();

// Buscar por cara
InventorySlot::byFace('left')->get();

// Buscar por nivel
InventorySlot::byLevel(2)->get();

// Buscar por código de barras
InventorySlot::byBarcode('SLOT-001000')->first();

// Búsqueda general
InventorySlot::search('001')->get();

// Posiciones cerca del límite de peso (>= 90%)
InventorySlot::nearWeightCapacity(90)->get();

// Posiciones que exceden peso máximo
InventorySlot::overCapacity()->get();

// Posiciones que exceden cantidad máxima
InventorySlot::overQuantity()->get();
```

---

## 💪 Métodos Helpers

### Floor Helpers

```php
$floor = Floor::find(1);

$floor->getStandCount();           // Total de estanterías
$floor->getActiveStandCount();     // Estanterías activas
$floor->getTotalSlotsCount();      // Total de posiciones
$floor->getOccupiedSlotsCount();   // Posiciones ocupadas
$floor->getOccupancyPercentage();  // Porcentaje ocupado (0-100)
$floor->getSummary();              // Array con toda la información
```

### StandStyle Helpers

```php
$style = StandStyle::find(1);

$style->getTypeName();             // Descripción amigable del tipo
$style->getFacesLabel();           // Caras en texto legible
$style->hasValidFaces();           // Verificar validez de caras
$style->getStandCount();           // Total de stands con este estilo
$style->getActiveStandCount();     // Stands activos
$style->getSummary();              // Array con toda la información
```

### Stand Helpers

```php
$stand = Stand::find(1);

$stand->getFullName();             // "PASILLO13A (Planta 1)"
$stand->getTotalSlots();           // Total de posiciones
$stand->getOccupiedSlots();        // Posiciones ocupadas
$stand->getAvailableSlots();       // Posiciones libres
$stand->getOccupancyPercentage();  // Porcentaje ocupado
$stand->getCurrentWeight();        // Peso total actual (kg)
$stand->isNearCapacity(90);        // ¿Cerca del límite de peso?
$stand->getSlot('left', 2, 3);     // Obtener posición específica
$stand->getSlotsByFace('left');    // Todas las posiciones de una cara
$stand->getSlotsByLevel(2);        // Todas las posiciones de un nivel
$stand->createSlots();             // Crear todas las posiciones
$stand->getSummary();              // Array con toda la información
```

### InventorySlot Helpers

```php
$slot = InventorySlot::find(1);

$slot->getAddress();               // "PASILLO13A / Izquierda / Nivel 2 / Sección 3"
$slot->getFaceLabel();             // "Izquierda"
$slot->isOccupied();               // ¿Está ocupada?
$slot->isAvailable();              // ¿Está libre?
$slot->getAvailableQuantity();     // Cantidad que aún se puede agregar
$slot->getAvailableWeight();       // Peso que aún se puede agregar (kg)
$slot->getWeightPercentage();      // Porcentaje de peso usado (0-100)
$slot->getQuantityPercentage();    // Porcentaje de cantidad usado (0-100)
$slot->canAddQuantity(10);         // ¿Se pueden agregar 10 unidades?
$slot->canAddWeight(5.5);          // ¿Se pueden agregar 5.5 kg?
$slot->isNearQuantityCapacity();   // ¿Está cerca del límite de cantidad?
$slot->isNearWeightCapacity();     // ¿Está cerca del límite de peso?
$slot->isOverQuantity();           // ¿Excede cantidad máxima?
$slot->isOverWeight();             // ¿Excede peso máximo?
$slot->addQuantity(10);            // Agregar cantidad
$slot->subtractQuantity(5);        // Restar cantidad
$slot->addWeight(2.5);             // Agregar peso
$slot->subtractWeight(1.0);        // Restar peso
$slot->clear();                    // Vaciar la posición
$slot->getFullInfo();              // Array detallado
$slot->getSummary();               // Array simplificado
```

---

## 🚀 Instalación & Migración

### 1. Ejecutar Migraciones

```bash
# Ejecutar solo las migraciones del almacén
php artisan migrate --path=database/migrations/aca/2025_11_17_00000*

# O ejecutar todas las migraciones
php artisan migrate
```

### 2. Ejecutar Seeders

```bash
# Opción A: Ejecutar el seeder maestro (recomendado)
php artisan db:seed --class=WarehouseSeeder

# Opción B: Ejecutar seeders individuales en orden
php artisan db:seed --class=FloorSeeder
php artisan db:seed --class=StandStyleSeeder
php artisan db:seed --class=StandSeeder
php artisan db:seed --class=InventorySlotSeeder
```

### 3. Verificar Instalación

```php
// En Tinker
php artisan tinker

// Contar registros
>>> App\Models\Warehouse\Floor::count();           // 4
>>> App\Models\Warehouse\StandStyle::count();      // 3
>>> App\Models\Warehouse\Stand::count();           // ~15
>>> App\Models\Warehouse\InventorySlot::count();   // ~1000+

// Ver datos
>>> App\Models\Warehouse\Floor::first()->getSummary();
>>> App\Models\Warehouse\Stand::first()->getSummary();
```

---

## 💡 Ejemplos Prácticos

### Ejemplo 1: Encontrar una Posición Disponible

```php
$stand = Stand::byCode('PASILLO13A')->first();

// Obtener la primera posición disponible en el stand
$available = $stand->slots()
    ->available()
    ->first();

if ($available) {
    echo "Posición disponible: " . $available->getAddress();
} else {
    echo "No hay posiciones disponibles en este stand";
}
```

### Ejemplo 2: Agregar Producto a una Posición

```php
use App\Models\Product;

$slot = InventorySlot::byBarcode('SLOT-001000')->first();
$product = Product::find(1);

// Verificar capacidad
if ($slot->canAddQuantity(50) && $slot->canAddWeight($product->weight * 50)) {
    // Asignar producto
    $slot->update([
        'product_id' => $product->id,
        'max_quantity' => 100,
        'weight_max' => 50.00,
    ]);

    // Agregar cantidad
    $slot->addQuantity(50);
    $slot->addWeight($product->weight * 50);

    echo "Producto agregado exitosamente";
} else {
    echo "No hay capacidad suficiente";
}
```

### Ejemplo 3: Obtener Estado de un Piso

```php
$floor = Floor::byCode('P1')->first();
$summary = $floor->getSummary();

echo "Piso: " . $summary['name'];
echo "Estanterías: " . $summary['stands_count'];
echo "Posiciones ocupadas: " . $summary['occupied_slots'] . "/" . $summary['total_slots'];
echo "Ocupación: " . $summary['occupancy_percentage'] . "%";
```

### Ejemplo 4: Encontrar Estanterías Cerca de Capacidad

```php
// Estanterías que están al 80% o más de su capacidad de peso
$overloaded = Stand::query()
    ->where('available', true)
    ->get()
    ->filter(fn($stand) => $stand->isNearCapacity(80));

foreach ($overloaded as $stand) {
    echo "{$stand->code} está al {$stand->getOccupancyPercentage()}% de capacidad\n";
}
```

### Ejemplo 5: Mover Producto entre Posiciones

```php
$sourceSlot = InventorySlot::find(1);
$targetSlot = InventorySlot::find(2);
$quantityToMove = 10;

// Verificar capacidad en destino
if ($targetSlot->canAddQuantity($quantityToMove)) {
    // Restar del origen
    $sourceSlot->subtractQuantity($quantityToMove);

    // Agregar al destino
    $targetSlot->update([
        'product_id' => $sourceSlot->product_id,
    ]);
    $targetSlot->addQuantity($quantityToMove);

    echo "Movimiento completado";
} else {
    echo "No hay capacidad en la posición destino";
}
```

### Ejemplo 6: Obtener Estadísticas de Ocupación

```php
$floors = Floor::active()->get();

foreach ($floors as $floor) {
    $total = $floor->getTotalSlotsCount();
    $occupied = $floor->getOccupiedSlotsCount();
    $percentage = $floor->getOccupancyPercentage();

    echo sprintf(
        "%s: %d/%d posiciones ocupadas (%.1f%%)\n",
        $floor->name,
        $occupied,
        $total,
        $percentage
    );
}
```

---

## 📋 Próximos Pasos

Ahora que la base de datos, modelos y seeders están listos, puedes proceder con:

### 1. Crear Endpoints REST API

Crear controladores y rutas siguiendo el patrón del proyecto:

```bash
# Ejemplo de rutas que podrías crear:
POST   /api/manager/warehouse/floors           # Crear piso
GET    /api/manager/warehouse/floors           # Listar pisos
GET    /api/manager/warehouse/floors/{id}      # Ver piso
PUT    /api/manager/warehouse/floors/{id}      # Actualizar piso

POST   /api/manager/warehouse/stands           # Crear estantería
GET    /api/manager/warehouse/stands           # Listar estanterías
GET    /api/manager/warehouse/stands/{id}      # Ver estantería
PUT    /api/manager/warehouse/stands/{id}      # Actualizar

POST   /api/manager/warehouse/slots            # Crear posición
GET    /api/manager/warehouse/slots            # Listar posiciones
GET    /api/manager/warehouse/slots/{id}       # Ver posición
PUT    /api/manager/warehouse/slots/{id}       # Actualizar
POST   /api/manager/warehouse/slots/{id}/add-quantity  # Agregar cantidad
POST   /api/manager/warehouse/slots/{id}/remove-quantity # Restar cantidad
```

### 2. Crear Controllers

En `app/Http/Controllers/Managers/Warehouse/`:

- `FloorsController.php`
- `StandStylesController.php`
- `StandsController.php`
- `InventorySlotsController.php`

### 3. Crear Vistas

En `resources/views/managers/views/warehouse/`:

- `floors/index.blade.php`
- `floors/create.blade.php`
- `stands/index.blade.php`
- `stands/create.blade.php`
- `slots/index.blade.php`

### 4. Crear Validaciones

```php
// App/Http/Requests/Warehouse/
- StoreFloorRequest
- StoreStandRequest
- StoreSlotRequest
- UpdateSlotQuantityRequest
```

### 5. Crear Servicios de Negocio

Para operaciones complejas:

```php
// App/Services/Warehouse/
- WarehouseService
- StandService
- SlotService
```

### 6. Crear Jobs y Events

Para operaciones asincrónicas:

```php
// App/Jobs/Warehouse/
- ProcessSlotMovement
- GenerateWarehouseReport

// App/Events/Warehouse/
- SlotOccupied
- SlotVacated
- StandNearCapacity
```

---

## 📞 Notas Finales

### Convenciones del Código

- ✅ Usa UUID para todas las entidades (propiedad `uid`)
- ✅ Siempre incluye type hints en relaciones
- ✅ Usa constantes para valores enum
- ✅ Implementa scopes para búsquedas comunes
- ✅ Proporciona métodos helpers claros
- ✅ Mantén índices de BD optimizados
- ✅ Documenta con PHPDoc

### Performance

- 🚀 Los índices compostos aceleren búsquedas frecuentes
- 🚀 El caché `is_occupied` evita queries complejas
- 🚀 El timestamp `last_movement` facilita auditoría
- 🚀 Los UUIDs son mejores para URLs que IDs secuenciales

### Seguridad

- 🔒 Valida siempre cantidades y pesos
- 🔒 Verifica capacidades antes de operaciones
- 🔒 Implementa permisos granulares en endpoints
- 🔒 Audita todas las operaciones de movimiento
- 🔒 Usa transacciones para operaciones críticas

---

**Documento generado automáticamente**
**Framework:** Laravel 11.42 | **Fecha:** 2025-11-17

# 🏗️ GUÍA DE INSTALACIÓN - WAREHOUSE MANAGEMENT SYSTEM

**Estado:** ✅ COMPLETADO
**Fecha:** 2025-11-17
**Arquitecto Backend:** Sistema Expert

---

## ✅ RESUMEN DE IMPLEMENTACIÓN

Se ha completado con éxito la **arquitectura de bases de datos, modelos Eloquent y seeders** para el sistema de gestión de almacén.

### 📦 Archivos Creados

#### 1. MIGRACIONES (4 archivos)
```
✅ database/migrations/aca/2025_11_17_000001_create_floors_table.php
✅ database/migrations/aca/2025_11_17_000002_create_stand_styles_table.php
✅ database/migrations/aca/2025_11_17_000003_create_stands_table.php
✅ database/migrations/aca/2025_11_17_000004_create_inventorie_slots_table.php
```

**Lo que hacen:**
- Crean 4 tablas relacionadas para almacenar pisos, estilos, estanterías y posiciones
- Incluyen índices optimizados para búsquedas frecuentes
- Definen foreign keys para integridad referencial
- Implementan campos de auditoría (timestamps)

#### 2. MODELOS ELOQUENT (4 archivos)
```
✅ app/Models/Warehouse/Floor.php
✅ app/Models/Warehouse/StandStyle.php
✅ app/Models/Warehouse/Stand.php
✅ app/Models/Warehouse/InventorySlot.php
```

**Características:**
- Trait `HasUid` para UUID automático
- Type hints en todas las relaciones
- `protected $casts` con tipos adecuados
- 10-30 scopes por modelo para búsquedas
- 15-40 métodos helpers por modelo
- Documentación completa con PHPDoc

#### 3. SEEDERS (5 archivos)
```
✅ database/seeders/FloorSeeder.php
✅ database/seeders/StandStyleSeeder.php
✅ database/seeders/StandSeeder.php
✅ database/seeders/InventorySlotSeeder.php
✅ database/seeders/WarehouseSeeder.php
```

**Datos generados:**
- 4 pisos/plantas del almacén
- 3 estilos de estanterías (ROW, ISLAND, WALL)
- ~15 estanterías físicas distribuidas
- ~1000+ posiciones de inventario

#### 4. DOCUMENTACIÓN (2 archivos)
```
✅ WAREHOUSE_ARCHITECTURE.md     (Documentación técnica completa)
✅ WAREHOUSE_SETUP_GUIDE.md      (Este archivo)
```

---

## 🚀 PASOS DE INSTALACIÓN

### Paso 1: Ejecutar Migraciones

```bash
# Opción A: Ejecutar todas las migraciones
php artisan migrate

# Opción B: Ejecutar solo las migraciones del almacén
php artisan migrate --path=database/migrations/aca/2025_11_17_00000*
```

**Resultado esperado:**
```
✓ Created table floors
✓ Created table stand_styles
✓ Created table stands
✓ Created table inventorie_slots
```

### Paso 2: Ejecutar Seeders

```bash
# RECOMENDADO: Ejecutar el seeder maestro
php artisan db:seed --class=WarehouseSeeder

# O ejecutar individualmente en este orden:
php artisan db:seed --class=FloorSeeder
php artisan db:seed --class=StandStyleSeeder
php artisan db:seed --class=StandSeeder
php artisan db:seed --class=InventorySlotSeeder
```

**Resultado esperado:**
```
✅ 4 pisos creados exitosamente
✅ 3 estilos de estanterías creados exitosamente
✅ [N] estanterías creadas exitosamente
✅ [N] posiciones de inventario creadas exitosamente
```

### Paso 3: Verificar Instalación (en Tinker)

```bash
php artisan tinker
```

```php
// Contar registros
>>> App\Models\Warehouse\Floor::count();
4

>>> App\Models\Warehouse\StandStyle::count();
3

>>> App\Models\Warehouse\Stand::count();
15

>>> App\Models\Warehouse\InventorySlot::count();
1100  // Aproximadamente

// Ver datos
>>> App\Models\Warehouse\Floor::first()->getSummary();
[
  "id" => 1,
  "uid" => "...",
  "code" => "P1",
  "name" => "Planta 1",
  "available" => true,
  "stands_count" => 6,
  "active_stands_count" => 6,
  "total_slots" => 288,
  "occupied_slots" => 0,
  "occupancy_percentage" => 0.0,
]
```

---

## 📊 ESTRUCTURA DE DATOS

### Tabla: `floors` (Pisos)
```sql
id          INT PRIMARY KEY
uid         UUID UNIQUE
code        VARCHAR(50) - P1, P2, S0
name        VARCHAR(100)
description TEXT
available   BOOLEAN DEFAULT true
order       INT - para ordenamiento visual
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

**Índices:** code, available, (available, order)

### Tabla: `stand_styles` (Estilos de Estantería)
```sql
id                  INT PRIMARY KEY
uid                 UUID UNIQUE
code                VARCHAR(50) - ROW, ISLAND, WALL
name                VARCHAR(100)
description         TEXT
faces               JSON - ["left", "right", "front", "back"]
default_levels      INT - 3, 4, 5
default_sections    INT - 5, 6, 8
available           BOOLEAN DEFAULT true
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

**Índices:** code, available

### Tabla: `stands` (Estanterías Físicas)
```sql
id              INT PRIMARY KEY
uid             UUID UNIQUE
floor_id        INT FK → floors.id
stand_style_id  INT FK → stand_styles.id
code            VARCHAR(50) UNIQUE - PASILLO13A, ISLA02
barcode         VARCHAR(100) UNIQUE - código de barras físico
position_x      INT - coordenada X
position_y      INT - coordenada Y
position_z      INT - coordenada Z (altura)
total_levels    INT - 3, 4, 5
total_sections  INT - 5, 6, 8
capacity        DECIMAL(10,2) - peso máximo en kg
available       BOOLEAN DEFAULT true
notes           TEXT
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

**Índices:** code, barcode, floor_id, stand_style_id, available, (floor_id, available), (position_x, position_y)

### Tabla: `inventorie_slots` (Posiciones de Inventario)
```sql
id              INT PRIMARY KEY
uid             UUID UNIQUE
stand_id        INT FK → stands.id
product_id      INT FK → products.id (nullable)
face            ENUM - left, right, front, back
level           INT - 1, 2, 3... (profundidad)
section         INT - 1, 2, 3... (ancho)
barcode         VARCHAR(100) UNIQUE - SLOT-001000
quantity        INT - cantidad actual
max_quantity    INT - máximo permitido (nullable)
weight_current  DECIMAL(8,2) - kg actuales
weight_max      DECIMAL(8,2) - kg máximo (nullable)
is_occupied     BOOLEAN - cache para búsquedas
last_movement   TIMESTAMP - última operación
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

**Índices:** stand_id, product_id, barcode, is_occupied, last_movement, UNIQUE(stand_id, face, level, section), (stand_id, is_occupied), (stand_id, face, level)

---

## 🔗 RELACIONES

```
Floor (1) ──────M──── Stand
   ↓
   └─ has: stands()
       └─ BelongsTo: floor()

StandStyle (1) ──────M──── Stand
   ↓
   └─ has: stands()
       └─ BelongsTo: style()

Stand (1) ──────M──── InventorySlot
   ↓
   └─ has: slots()
       └─ BelongsTo: stand()

Product (1) ──────M──── InventorySlot
   ↓
   └─ has: slots()  [implícito]
       └─ BelongsTo: product()
```

---

## 💻 EJEMPLOS DE USO

### Obtener un Piso Completo

```php
$floor = Floor::where('code', 'P1')->first();

// Información
echo $floor->name;                 // "Planta 1"
echo $floor->getStandCount();      // 6 estanterías

// Relaciones
$stands = $floor->stands;          // Collection de Stand

// Estadísticas
$summary = $floor->getSummary();   // Array con métricas
```

### Buscar una Posición Disponible

```php
$available = InventorySlot::byStand(1)
    ->available()
    ->first();

if ($available) {
    echo $available->getAddress();  // "PASILLO13A / Izquierda / Nivel 2 / Sección 3"
}
```

### Agregar Producto a una Posición

```php
$slot = InventorySlot::byBarcode('SLOT-001000')->first();

if ($slot->canAddQuantity(50)) {
    $slot->addQuantity(50);
    $slot->update(['product_id' => 1]);
    echo "Agregado exitosamente";
}
```

### Obtener Estadísticas de Ocupación

```php
$floors = Floor::active()->get();

foreach ($floors as $floor) {
    $percentage = $floor->getOccupancyPercentage();
    echo "{$floor->name}: {$percentage}% ocupado";
}
```

Ver más ejemplos en **WAREHOUSE_ARCHITECTURE.md**

---

## 📋 SCOPES DISPONIBLES

### Floor
- `active()` - solo pisos activos
- `ordered()` - ordenado por orden y nombre
- `byCode('P1')` - buscar por código
- `search('Planta')` - búsqueda parcial

### StandStyle
- `active()` - solo estilos activos
- `byCode('ROW')` - buscar por código
- `search('pasillo')` - búsqueda parcial

### Stand
- `active()` - solo estanterías activas
- `byFloor(1)` - filtrar por piso
- `byCode('PASILLO13A')` - buscar por código
- `byBarcode('BAR-...')` - buscar por código de barras
- `byStyle(1)` - filtrar por estilo
- `search('PASILLO')` - búsqueda general
- `ordered()` - ordenado por posición

### InventorySlot
- `occupied()` - solo posiciones ocupadas
- `available()` - solo posiciones libres
- `byStand(1)` - filtrar por estantería
- `byProduct(1)` - filtrar por producto
- `byFace('left')` - filtrar por cara
- `byLevel(2)` - filtrar por nivel
- `byBarcode('SLOT-...')` - buscar por código de barras
- `search('001')` - búsqueda general
- `nearWeightCapacity(90)` - cerca del límite de peso
- `overCapacity()` - excede peso máximo
- `overQuantity()` - excede cantidad máxima

---

## 🎯 PRÓXIMOS PASOS

Ahora que la estructura de datos está lista, puedes proceder con:

### 1. **Crear Controladores REST** ⏭️
Ubicación: `app/Http/Controllers/Managers/Warehouse/`

Controladores sugeridos:
- `FloorsController` - CRUD de pisos
- `StandStylesController` - CRUD de estilos
- `StandsController` - CRUD de estanterías
- `InventorySlotsController` - CRUD y operaciones

### 2. **Definir Rutas API** ⏭️
Ubicación: `routes/managers.php`

Ejemplo:
```php
Route::prefix('warehouse')->group(function () {
    Route::resource('floors', FloorsController::class);
    Route::resource('stands', StandsController::class);
    Route::resource('slots', InventorySlotsController::class);
    Route::post('slots/{id}/add-quantity', [InventorySlotsController::class, 'addQuantity']);
    // ... más rutas
});
```

### 3. **Crear Validaciones** ⏭️
Ubicación: `app/Http/Requests/Warehouse/`

Form Requests:
- `StoreFloorRequest`
- `UpdateFloorRequest`
- `StoreStandRequest`
- `UpdateSlotRequest`

### 4. **Crear Vistas (si no es API puro)** ⏭️
Ubicación: `resources/views/managers/views/warehouse/`

### 5. **Crear Servicios de Negocio** ⏭️
Para lógica compleja:
- `WarehouseService`
- `StandService`
- `SlotService`

### 6. **Crear Jobs y Events** ⏭️
Para operaciones asincrónicas:
- `ProcessSlotMovement` (Job)
- `SlotOccupied` (Event)
- `StandNearCapacity` (Event)

---

## 🔒 NOTAS DE SEGURIDAD

- ✅ Todas las entidades tienen UUID (no expongas IDs secuenciales)
- ✅ Valida siempre cantidades y pesos antes de operaciones
- ✅ Verifica capacidades antes de agregar productos
- ✅ Implementa permisos granulares en endpoints
- ✅ Audita todas las operaciones de movimiento
- ✅ Usa transacciones para operaciones críticas

---

## 🎓 REFERENCIA RÁPIDA

| Acción | Código |
|--------|--------|
| Obtener piso | `Floor::byCode('P1')->first()` |
| Listar estanterías activas | `Stand::active()->get()` |
| Buscar posición | `InventorySlot::byBarcode('SLOT-001000')->first()` |
| Posiciones libres | `InventorySlot::byStand(1)->available()->get()` |
| Agregar cantidad | `$slot->addQuantity(10)` |
| Verificar capacidad | `$slot->canAddWeight(5.5)` |
| Obtener dirección | `$slot->getAddress()` |
| Estadísticas piso | `$floor->getSummary()` |
| Crear posiciones | `$stand->createSlots()` |

---

## 📞 SOPORTE

Para preguntas sobre la arquitectura:
1. Consulta **WAREHOUSE_ARCHITECTURE.md** (documentación completa)
2. Revisa ejemplos en **Ejemplos Prácticos**
3. Inspecciona el código fuente de los modelos

---

**Implementación completada correctamente** ✅

Todos los archivos están listos para ser usados. La estructura sigue los patrones y convenciones del proyecto WebAdmin.

**Próximo paso:** Crear los endpoints REST API según tus necesidades.

# 🔄 PLAN DE REFACTORING: UNIFICACIÓN WAREHOUSE

**Fecha:** 17 de Noviembre de 2025
**Objetivo:** Unificar todo bajo App/Models/Warehouse/ y renombrar conceptos
**Estado:** Plan de Refactoring

---

## 📋 CAMBIOS DE NOMENCLATURA

### Mapeo de Conceptos

| Concepto Anterior | Nuevo Concepto | Tabla BD | Modelo |
|------------------|----------------|----------|--------|
| Inventaries (sede) | Warehouse | `warehouses` | `Warehouse.php` |
| Location (ubicación) | Stand | `stands` | `Stand.php` |
| InventorySlot (posición) | InventorySlot | `inventory_slots` | `InventorySlot.php` |
| InventarieOperation (evento) | WarehouseOperation | `warehouse_operations` | `WarehouseOperation.php` |
| InventarieLocation (distribución) | WarehouseLocation | `warehouse_locations` | `WarehouseLocation.php` |
| InventarieLocationItem (producto) | WarehouseLocationItem | `warehouse_location_items` | `WarehouseLocationItem.php` |
| InventarieCondition (condición) | WarehouseCondition | `warehouse_conditions` | `WarehouseCondition.php` |
| InventoryMovement (auditoría) | InventoryMovement | `inventory_movements` | `InventoryMovement.php` |
| Floor (piso) | Floor | `floors` | `Floor.php` |
| StandStyle (tipo) | StandStyle | `stand_styles` | `StandStyle.php` |

---

## 🗂️ NUEVA ESTRUCTURA DE CARPETAS

### Antes
```
app/Models/
├── Location.php
├── Inventarie/
│   ├── Inventarie.php
│   ├── InventarieOperation.php
│   ├── InventarieLocation.php
│   ├── InventarieLocationItem.php
│   └── InventarieCondition.php
└── Warehouse/
    ├── Floor.php
    ├── Stand.php (antes, era para estanterías)
    ├── StandStyle.php
    ├── InventorySlot.php
    └── InventoryMovement.php
```

### Después
```
app/Models/Warehouse/
├── Warehouse.php                (antes Inventarie)
├── Floor.php                    (piso de warehouse)
├── Stand.php                    (antes Location - ubicación física)
├── StandStyle.php               (tipo de stand)
├── InventorySlot.php            (posición en stand)
├── InventoryMovement.php        (auditoría)
├── WarehouseOperation.php       (antes InventarieOperation)
├── WarehouseLocation.php        (antes InventarieLocation)
├── WarehouseLocationItem.php    (antes InventarieLocationItem)
└── WarehouseCondition.php       (antes InventarieCondition)
```

---

## 📊 RELACIONES FINALES

```
Warehouse (1)
    │
    ├─ Floor (N) [Pisos]
    │  │
    │  └─ Stand (N) [Ubicaciones/Secciones]
    │     │
    │     ├─ StandStyle (1) [Tipo de stand]
    │     │
    │     └─ InventorySlot (N) [Posiciones]
    │        │
    │        ├─ Product (1) [Producto almacenado]
    │        │
    │        └─ InventoryMovement (N) [Auditoría]
    │
    ├─ WarehouseOperation (N) [Operaciones de conteo]
    │  │
    │  └─ WarehouseLocation (N) [Distribución durante operación]
    │     │
    │     └─ WarehouseLocationItem (N) [Productos contados]
    │        │
    │        └─ WarehouseCondition (1) [Condición del producto]
    │
    └─ WarehouseCondition (N) [Catálogo de condiciones]
```

---

## 🔧 TAREAS DE REFACTORING

### Fase 1: Crear Nuevos Modelos en Warehouse

#### 1.1 Renombrar Inventarie → Warehouse
```
Cambiar:
- app/Models/Inventarie/Inventarie.php
  → app/Models/Warehouse/Warehouse.php

- Cambiar namespace y class name
- Actualizar todas las relaciones
- Actualizar referencias
```

#### 1.2 Renombrar Location → Stand
```
Cambiar:
- app/Models/Location.php
  → app/Models/Warehouse/Stand.php

- Cambiar namespace y class name
- Cambiar relación: location() → warehouse()
- Actualizar referencias
```

#### 1.3 Mover InventarieOperation → WarehouseOperation
```
Cambiar:
- app/Models/Inventarie/InventarieOperation.php
  → app/Models/Warehouse/WarehouseOperation.php

- Cambiar namespace y class name
- Cambiar relaciones:
  - inventarie() → warehouse()
  - InventarieLocation → WarehouseLocation
```

#### 1.4 Mover InventarieLocation → WarehouseLocation
```
Cambiar:
- app/Models/Inventarie/InventarieLocation.php
  → app/Models/Warehouse/WarehouseLocation.php

- Cambiar namespace y class name
- Cambiar relaciones:
  - location() → stand()
  - inventarie() → warehouse()
  - operation() → operation()
```

#### 1.5 Mover InventarieLocationItem → WarehouseLocationItem
```
Cambiar:
- app/Models/Inventarie/InventarieLocationItem.php
  → app/Models/Warehouse/WarehouseLocationItem.php

- Cambiar namespace y class name
- Cambiar relaciones:
  - location() → warehouseLocation()
  - Actualizar método syncToInventorySlot()
```

#### 1.6 Mover InventarieCondition → WarehouseCondition
```
Cambiar:
- app/Models/Inventarie/InventarieCondition.php
  → app/Models/Warehouse/WarehouseCondition.php

- Cambiar namespace y class name
```

#### 1.7 Actualizar InventorySlot
```
Cambiar:
- Relación: stand() (ya correcta, pero cambiar a FK correcto)
- Relación: lastWarehouse() (antes lastInventarie)
- Cambiar referencias de Inventarie a Warehouse
```

#### 1.8 Actualizar InventoryMovement
```
Cambiar:
- Relación: warehouse() (antes inventarie())
- Relación: warehouseLocationItem() (antes inventarieLocationItem())
- Actualizar scopes
```

### Fase 2: Actualizar Migraciones

```sql
-- Cambiar nombre de tabla
inventaries → warehouses
inventarie_operations → warehouse_operations
inventarie_locations → warehouse_locations
inventarie_locations_items → warehouse_location_items
inventarie_conditions → warehouse_conditions

locations → stands (cambio importante!)
```

#### 2.1 Nueva migración: Renombrar Tablas
```php
Schema::rename('inventaries', 'warehouses');
Schema::rename('locations', 'stands');
Schema::rename('inventarie_operations', 'warehouse_operations');
Schema::rename('inventarie_locations', 'warehouse_locations');
Schema::rename('inventarie_locations_items', 'warehouse_location_items');
Schema::rename('inventarie_conditions', 'warehouse_conditions');
```

#### 2.2 Actualizar FKs en Migraciones
```php
// En warehouse_floors
inventarie_id → warehouse_id

// En stands
inventarie_id → warehouse_id
floor_id (igual)
style_id → stand_style_id (para claridad)

// En inventory_slots
location_id → stand_id
last_inventarie_id → last_warehouse_id

// En warehouse_operations
inventarie_id → warehouse_id

// En warehouse_locations
inventarie_id → warehouse_id
location_id → stand_id

// En warehouse_location_items
condition_id → warehouse_condition_id

// En inventory_movements
inventarie_id → warehouse_id
inventarie_location_item_id → warehouse_location_item_id
```

---

## 📝 CAMBIOS EN CADA MODELO

### Warehouse (antes Inventarie)
```php
// Namespace
namespace App\Models\Warehouse;

// Relaciones
public function floors() // igual
public function stands() // antes locations()
public function warehouseOperations() // antes inventarieOperations()
public function inventoryMovements() // igual
public function warehouseConditions() // antes inventarieConditions()

// Propiedades tabla
protected $table = 'warehouses'; // antes 'inventaries'
```

### Stand (antes Location)
```php
// Namespace
namespace App\Models\Warehouse;

// Relaciones
public function warehouse() // antes inventarie()
public function floor() // igual
public function style() // antes style() pero referencia a StandStyle
public function inventorySlots() // antes slots()

// Propiedades tabla
protected $table = 'stands'; // antes 'locations'

// Métodos
public function generateInventorySlots() // antes generateSlots()
```

### InventorySlot
```php
// Relaciones
public function stand() // antes location() [FK cambió a stand_id]
public function lastWarehouse() // antes lastInventarie()
public function movements() // igual

// Métodos operación
addQuantity(..., $warehouseId = null) // parámetro cambió
subtractQuantity(..., $warehouseId = null)
addWeight(..., $warehouseId = null)
clear(..., $warehouseId = null)

// Método getAddress()
return "{$warehouse}/{$floor}/{$stand}/{$face}/L{$level}/S{$section}"
```

### WarehouseOperation (antes InventarieOperation)
```php
// Namespace
namespace App\Models\Warehouse;

// Relaciones
public function warehouse() // antes inventarie()
public function warehouseLocations() // antes locations()

// Métodos
public function generateLocations() // genera WarehouseLocations
public function close(...) // igual
```

### WarehouseLocation (antes InventarieLocation)
```php
// Namespace
namespace App\Models\Warehouse;

// Relaciones
public function stand() // antes location()
public function warehouse() // antes inventarie()
public function operation() // igual
public function items() // genera WarehouseLocationItems
```

### WarehouseLocationItem (antes InventarieLocationItem)
```php
// Namespace
namespace App\Models\Warehouse;

// Relaciones
public function warehouseLocation() // antes location()
public function product() // igual
public function user() // igual
public function condition() // antes condition()
public function inventoryMovement() // igual

// Métodos
public function syncToInventorySlot(...) // igual, referencia a InventorySlot correcta
```

---

## 🔄 ORDEN DE EJECUCIÓN

### Paso 1: Crear Migraciones de Renombrado
1. Nueva migración para renombrar tablas
2. Nueva migración para cambiar FKs
3. Nueva migración para cambiar índices

### Paso 2: Crear Nuevos Modelos
1. Crear Warehouse.php (copiar de Inventarie, cambiar)
2. Crear Stand.php (copiar de Location, cambiar)
3. Crear WarehouseOperation.php (mover y cambiar)
4. Crear WarehouseLocation.php (mover y cambiar)
5. Crear WarehouseLocationItem.php (mover y cambiar)
6. Crear WarehouseCondition.php (mover y cambiar)

### Paso 3: Actualizar Modelos Existentes
1. Actualizar Floor.php (cambiar FK references)
2. Actualizar StandStyle.php (cambiar referencias)
3. Actualizar InventorySlot.php (cambiar relaciones)
4. Actualizar InventoryMovement.php (cambiar relaciones)

### Paso 4: Actualizar Código Existente
1. Controladores (cambiar nombres y referencias)
2. Rutas (cambiar rutas)
3. Vistas (cambiar referencias)
4. Seeders (actualizar)

### Paso 5: Limpiar
1. Eliminar carpeta App/Models/Inventarie/
2. Eliminar archivo App/Models/Location.php
3. Actualizar importaciones en toda la aplicación

---

## 📊 MAPEO DE TABLAS Y FKs

### Antes
```sql
inventaries (sede)
  ├─ warehouse_floors (piso)
  │  └─ warehouses_floors.inventarie_id FK
  │
  └─ warehouse_inventory_slots
     └─ warehouse_inventory_slots.location_id FK (referencia a locations)

locations (ubicación)
  ├─ location_id (self-reference)
  ├─ inventarie_id FK
  └─ floor_id FK
  └─ style_id FK

warehouse_floors
  └─ inventarie_id FK

inventarie_operations
  └─ inventarie_id FK

inventarie_locations
  ├─ location_id FK
  ├─ inventarie_id FK
  └─ operation_id FK

inventory_movements
  ├─ inventarie_id FK
  └─ inventarie_location_item_id FK
```

### Después
```sql
warehouses (sede)
  ├─ warehouse_floors (piso)
  │  └─ warehouse_floors.warehouse_id FK
  │
  └─ warehouse_inventory_movements
     └─ inventory_movements.warehouse_id FK

stands (ubicación)
  ├─ stand_id (self-reference)
  ├─ warehouse_id FK
  └─ floor_id FK
  └─ stand_style_id FK

warehouse_floors
  └─ warehouse_id FK

warehouse_operations
  └─ warehouse_id FK

warehouse_locations
  ├─ stand_id FK
  ├─ warehouse_id FK
  └─ warehouse_operation_id FK

inventory_movements
  ├─ warehouse_id FK
  └─ warehouse_location_item_id FK
```

---

## 🎯 RESULTADOS ESPERADOS

Después del refactoring:

✅ Todo en `App\Models\Warehouse\`
✅ Nomenclatura consistente: warehouse_* para tablas
✅ Conceptos claros:
  - Warehouse = Sede/Almacén
  - Floor = Piso
  - Stand = Ubicación/Sección
  - InventorySlot = Posición
✅ Operaciones de conteo (WarehouseOperation, WarehouseLocation, WarehouseLocationItem)
✅ Auditoría completa (InventoryMovement)

---

## ⚠️ CONSIDERACIONES

### 1. Backward Compatibility
- Eliminar tablas/modelos antiguos puede romper código existente
- Necesario actualizar TODO el código que las referencia
- Ejecutar migraciones antes de cambios de código

### 2. Datos Existentes
- Las migraciones de renombrado preservarán datos
- Las FKs cambiarán automáticamente

### 3. Testing
- Necesario testing exhaustivo después
- Verificar todas las relaciones
- Verificar sincronización

---

## 📋 CHECKLIST DE EJECUCIÓN

- [ ] Crear migraciones de renombrado de tablas
- [ ] Crear modelo Warehouse.php
- [ ] Crear modelo Stand.php
- [ ] Crear modelo WarehouseOperation.php
- [ ] Crear modelo WarehouseLocation.php
- [ ] Crear modelo WarehouseLocationItem.php
- [ ] Crear modelo WarehouseCondition.php
- [ ] Actualizar Floor.php
- [ ] Actualizar StandStyle.php
- [ ] Actualizar InventorySlot.php
- [ ] Actualizar InventoryMovement.php
- [ ] Ejecutar migraciones
- [ ] Actualizar controladores
- [ ] Actualizar rutas
- [ ] Actualizar vistas
- [ ] Actualizar seeders
- [ ] Ejecutar tests
- [ ] Eliminar modelos/carpetas antiguas

---

## 🚀 PRÓXIMO PASO

¿Deseas que comience con el refactoring?

Propongo este orden:
1. Crear migraciones de renombrado
2. Crear nuevos modelos en Warehouse/
3. Ejecutar migraciones
4. Actualizar referencias en modelos existentes
5. Actualizar controladores y rutas

---

**Estimado:** 6-8 horas de trabajo
**Complejidad:** Alta
**Riesgo:** Medio (muchos cambios simultáneos)


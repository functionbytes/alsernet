# 📋 AUDITORÍA DE MODELOS WAREHOUSE

**Fecha:** 17 de Noviembre de 2025
**Revisión:** Estructura actual de modelos en app/Models/Warehouse/

---

## 📊 MODELOS ENCONTRADOS

### 1. WarehouseInventorySlot.php
- **Tabla:** warehouse_inventory_slots
- **Propósito:** Posición dentro de una ubicación
- **Campos clave:** location_id, product_id, face, level, section, quantity, weight_current
- **Fillable:** uid, location_id, product_id, face, level, section, barcode, quantity, max_quantity, weight_current, weight_max, is_occupied, last_movement, last_inventarie_id
- **Estado:** ✅ Correcto

### 2. InventarieLocationItem.php
- **Tabla:** inventarie_locations_items
- **Propósito:** Producto contado durante operación de inventario
- **Campos clave:** product_id, count, location_id, user_id, condition_id, original_id, validate_id
- **Fillable:** uid, count, product_id, location_id, original_id, validate_id, condition_id, user_id, synced_to_warehouse, inventory_movement_id
- **Estado:** ✅ Correcto

### 3. Warehouse.php
- **Tabla:** warehouses
- **Propósito:** Sede/Almacén principal
- **Campos clave:** uid, available, shop_id, closet_at
- **Fillable:** uid, available, shop_id, created_at, closet_at, updated_at
- **Relaciones:** shop(), locations()
- **Estado:** ⚠️ INCOMPLETO - Faltan relaciones a floors, operations

### 4. WarehouseLocation.php
- **Tabla:** warehouse_stands ❌ PROBLEMA
- **Propósito:** Debería ser Ubicación (Stand)
- **Nombre confuso:** Se llama WarehouseLocation pero apunta a warehouse_stands
- **Estado:** ❌ CONFUSO - Nombre vs tabla no coinciden

### 5. WarehouseFloor.php
- **Tabla:** warehouse_floors
- **Propósito:** Piso del almacén
- **Estado:** ✅ Necesita revisar relaciones

### 6. WarehouseInventoryOperation.php
- **Tabla:** warehouse_operations
- **Propósito:** Operación de conteo de inventario
- **Estado:** ✅ Necesita revisar relaciones

### 7. WarehouseInventoryMovement.php
- **Tabla:** warehouse_inventory_movements
- **Propósito:** Auditoría de movimientos
- **Estado:** ✅ Necesita revisar relaciones

### 8. WarehouseLocationStyle.php
- **Tabla:** warehouse_stand_styles
- **Propósito:** Tipos de ubicación
- **Estado:** ✅ Probablemente correcto

### 9. WarehouseLocationCondition.php
- **Tabla:** warehouse_conditions
- **Propósito:** Condiciones de productos
- **Estado:** ✅ Probablemente correcto

### 10. InventarieLocation.php
- **Ubicación:** app/Models/Warehouse/InventarieLocation.php
- **Estado:** ⚠️ Duplicado en carpeta equivocada

---

## 🚨 PROBLEMAS IDENTIFICADOS

### Problema 1: Nomenclatura Confusa
```
WarehouseLocation.php → tabla warehouse_stands (debería llamarse Stand o Location)
warehouse_stands → debería ser locations
```

### Problema 2: Estructura de Tablas
```
Esperado según diseño:
- locations (ubicaciones físicas)
- warehouse_inventory_slots (posiciones en ubicaciones)

Actual parece tener:
- warehouse_stands (¿ubicaciones?)
- warehouse_inventory_slots (posiciones)
```

### Problema 3: Modelos Incompletos
```
Warehouse.php - Faltan relaciones:
  - hasMany(Floor)
  - hasMany(WarehouseOperation)
  - hasMany(WarehouseLocationItem) - para sincronización

WarehouseLocation.php - Faltan métodos:
  - generateInventorySlots()
  - getHierarchy()
```

### Problema 4: Ubicación de Modelos
```
InventarieLocation.php está en app/Models/Warehouse/
Debería estar en app/Models/Inventarie/ o solo una versión
```

---

## 🔄 ESTRUCTURA CORRECTA ESPERADA

```
app/Models/Warehouse/
├── Warehouse.php                    (Sede/Almacén)
├── WarehouseFloor.php              (Piso)
├── WarehouseLocation.php           (Ubicación/Stand) - RENAME
├── WarehouseLocationStyle.php       (Tipo de ubicación)
├── WarehouseInventorySlot.php      (Posición)
├── WarehouseInventoryMovement.php  (Auditoría)
├── WarehouseInventoryOperation.php (Operación de conteo)
├── WarehouseLocationCondition.php  (Condición de producto)
└── WarehouseLocationItem.php       (Producto contado) - RENAME

app/Models/Location.php             (DEPRECATED - use WarehouseLocation)
```

---

## 📝 COMPARACIÓN: WarehouseInventorySlot vs InventarieLocationItem

### WarehouseInventorySlot
```php
- Tabla: warehouse_inventory_slots
- Representa: Posición dentro de ubicación
- Campos: location_id, product_id, face, level, section, quantity, weight
- Estado: Permanente
- Uso: Almacén actual
```

### InventarieLocationItem
```php
- Tabla: inventarie_locations_items
- Representa: Producto contado durante operación
- Campos: product_id, count, condition_id, user_id, original_id, validate_id
- Estado: Temporal (durante operación)
- Uso: Sincronizar a InventorySlot después
```

**Conclusión:** NO son iguales. Son conceptos diferentes:
- InventorySlot = Estado actual permanente
- InventarieLocationItem = Conteo temporal que se sincroniza

---

## 🔧 ACCIONES REQUERIDAS

### 1. Renombrar Tablas (Migración)
```sql
warehouse_stands → locations
warehouse_conditions → warehouse_conditions (ok)
warehouse_stand_styles → warehouse_location_styles
```

### 2. Actualizar Modelos
```
WarehouseLocation.php → Location.php
- Apunte a tabla locations
- Agregue relaciones completas
- Agregue método generateInventorySlots()

WarehouseInventoryOperation.php
- Agregue relación a Warehouse

Warehouse.php
- Agregue hasMany(Floor)
- Agregue hasMany(WarehouseInventoryOperation)
```

### 3. Eliminar Duplicados
```
app/Models/Warehouse/InventarieLocation.php → ELIMINAR
app/Models/Location.php → ACTUALIZAR o ELIMINAR
```

### 4. Tablas BD
```
Crear: warehouse_locations (renombrado de warehouse_stands)
Crear: warehouse_location_items (renombrado de inventarie_locations_items)
Crear: warehouse_operations (renombrado de inventarie_operations)
```

---

## 📋 CHECKLIST DE CORRECCIÓN

- [ ] Crear migración unificada que renombre todas las tablas
- [ ] Actualizar WarehouseLocation.php a tabla locations
- [ ] Actualizar Warehouse.php con relaciones completas
- [ ] Agregar método generateInventorySlots() a WarehouseLocation
- [ ] Eliminar duplicados de modelos
- [ ] Ejecutar migraciones
- [ ] Verificar todas las relaciones funcionan
- [ ] Actualizar controladores
- [ ] Testing

---

## 🎯 ESTADO FINAL ESPERADO

```
Warehouse (1)
    │
    ├─ WarehouseFloor (N)
    │  │
    │  └─ WarehouseLocation (N) - tabla locations
    │     │
    │     ├─ WarehouseLocationStyle (1)
    │     │
    │     └─ WarehouseInventorySlot (N) - tabla warehouse_inventory_slots
    │        │
    │        ├─ Product (1)
    │        │
    │        └─ WarehouseInventoryMovement (N)
    │
    ├─ WarehouseInventoryOperation (N)
    │  │
    │  └─ WarehouseLocationItem (N) - tabla warehouse_location_items
    │     │
    │     ├─ Product (1)
    │     ├─ WarehouseLocationCondition (1)
    │     └─ User (1) - quién contó
    │
    └─ WarehouseLocationCondition (N) - catálogo de condiciones
```

---

**Conclusión:** La estructura existe pero necesita reorganización y renombrado de tablas para consistencia.

# 📋 Guía de Migraciones Warehouse

**Fecha:** 17 de Noviembre de 2025
**Estado:** Nuevas migraciones reorganizadas en `database/migrations/warehouse/`

---

## 📁 Estructura de Migraciones

Las migraciones están en `database/migrations/` según el orden de dependencias:

### 1. `2025_11_17_000001_create_warehouses_table.php`
- **Tabla:** `warehouses`
- **Propósito:** Tabla principal de almacenes/sedes
- **Campos:** id, uid, shop_id, available, closet_at, deleted_at, timestamps
- **Dependencias:** shops
- **Estado:** ✅ Crear tabla base

### 2. `2025_11_17_000002_create_warehouse_location_conditions_table.php`
- **Tabla:** `warehouse_location_conditions`
- **Propósito:** Condiciones de productos (Nuevo, Usado, Dañado, etc.)
- **Campos:** id, uid, title, slug, description, available, timestamps
- **Dependencias:** Ninguna (tabla de referencia)
- **Estado:** ✅ Crear tabla base

### 3. `2025_11_17_000003_create_warehouse_stand_styles_table.php`
- **Tabla:** `warehouse_stand_styles`
- **Propósito:** Estilos/tipos de estanterías
- **Campos:** id, uid, name, description, faces (JSON), default_levels, default_sections, available, timestamps
- **Dependencias:** Ninguna (tabla de referencia)
- **Estado:** ✅ Crear tabla base

### 4. `2025_11_17_000004_create_warehouse_floors_table.php`
- **Tabla:** `warehouse_floors`
- **Propósito:** Pisos/niveles del almacén
- **Campos:** id, uid, inventarie_id, name, description, level, available, timestamps
- **Dependencias:** inventaries (FK)
- **Estado:** ✅ Crear tabla base

### 5. `2025_11_17_000005_create_locations_table.php`
- **Tabla:** `locations`
- **Propósito:** Ubicaciones/estanterías (Stand)
- **Campos:** id, uid, inventarie_id, floor_id, style_id, code, title, description, total_faces, total_levels, total_sections, capacity, position_x/y/z, available, notes, timestamps
- **Dependencias:** inventaries, warehouse_floors, warehouse_stand_styles
- **Estado:** ✅ Crear o modificar tabla
- **Nota:** UNIQUE constraint en (inventarie_id, code)

### 6. `2025_11_17_000006_create_warehouse_inventory_slots_table.php`
- **Tabla:** `warehouse_inventory_slots`
- **Propósito:** Posiciones dentro de ubicaciones
- **Campos:** id, uid, location_id, product_id, barcode, face, level, section, quantity, max_quantity, weight_current, weight_max, is_occupied, last_movement, last_inventarie_id, timestamps
- **Dependencias:** locations, products, inventaries
- **Estado:** ✅ Crear tabla base
- **Nota:** UNIQUE constraint en (location_id, face, level, section)

### 7. `2025_11_17_000007_create_warehouse_inventory_movements_table.php`
- **Tabla:** `warehouse_inventory_movements`
- **Propósito:** Auditoría de movimientos de inventario
- **Campos:** id, uid, slot_id, product_id, movement_type (enum: add, subtract, clear, move, count), from_quantity, to_quantity, quantity_delta, from_weight, to_weight, weight_delta, reason, user_id, inventarie_id, inventarie_location_item_id, timestamps
- **Dependencias:** warehouse_inventory_slots, products, users, inventaries, inventarie_locations_items
- **Estado:** ✅ Crear tabla base

### 8. `2025_11_17_000008_create_warehouse_inventory_operations_table.php`
- **Tabla:** `warehouse_inventory_operations`
- **Propósito:** Operaciones de conteo/inventario
- **Campos:** id, uid, inventarie_id, user_id, name, description, started_at, closed_at, closed_by_user_id, status (enum: open, closed, paused), timestamps
- **Dependencias:** inventaries, users
- **Estado:** ✅ Crear tabla base

### 9. `2025_11_17_000009_add_warehouse_relationships.php`
- **Propósito:** Agregar relaciones entre tablas existentes (sincronización)
- **Cambios:**
  - `inventarie_locations`: Agregar `operation_id` (FK a warehouse_inventory_operations)
  - `inventarie_locations_items`: Agregar `synced_to_warehouse` (boolean) e `inventory_movement_id` (FK a warehouse_inventory_movements)
- **Dependencias:** warehouse_inventory_operations, warehouse_inventory_movements
- **Estado:** ✅ Modificar tablas existentes

---

## 🔗 Diagrama de Relaciones

```
inventaries (Sede)
    ├── warehouse_floors (Piso)
    │   └── locations (Ubicación/Stand)
    │       ├── warehouse_stand_styles (Estilo)
    │       └── warehouse_inventory_slots (Posición)
    │           ├── products (Producto)
    │           ├── warehouse_inventory_movements (Auditoría)
    │           └── inventarie_locations_items (vinculación a conteo)
    │
    └── warehouse_inventory_operations (Operación de conteo)
        └── inventarie_locations (Distribución de secciones)
            └── inventarie_locations_items (Productos contados)
```

---

## 🚀 Ejecución de Migraciones

### Opción 1: Ejecutar todas las migraciones de warehouse

```bash
php artisan migrate --path=database/migrations/warehouse
```

### Opción 2: Ejecutar migraciones específicas

```bash
# Una sola migración
php artisan migrate --path=database/migrations/warehouse/2025_11_17_000001_create_warehouse_floors_table.php

# Mostrar estado
php artisan migrate:status --path=database/migrations/warehouse
```

### Opción 3: Rollback

```bash
# Revertir todas las migraciones warehouse
php artisan migrate:rollback --path=database/migrations/warehouse

# Revertir solo una
php artisan migrate:rollback --path=database/migrations/warehouse/2025_11_17_000001_create_warehouse_floors_table.php
```

---

## ✅ Checklist de Ejecución

- [ ] Verificar que `database/migrations/warehouse/` existe y contiene 7 archivos
- [ ] Ejecutar `php artisan migrate:status` para ver si las migraciones se detectan
- [ ] Si no se detectan, mover migraciones a `database/migrations/` (nivel raíz)
- [ ] Ejecutar `php artisan migrate --path=database/migrations/warehouse`
- [ ] Verificar que las tablas se crearon en la base de datos
- [ ] Verificar que las ForeignKeys funcionan correctamente
- [ ] Ejecutar seeders si es necesario
- [ ] Verificar que los modelos Eloquent funcionan con las tablas

---

## 🔧 Solución de Problemas

### Problema: Migraciones no detectadas en carpeta warehouse

**Solución 1:** Mover migraciones a nivel raíz
```bash
mv database/migrations/warehouse/*.php database/migrations/
```

**Solución 2:** Usar ruta completa en comando
```bash
php artisan migrate --path=database/migrations/warehouse
```

### Problema: Foreign Key constraint failed

**Causa probable:** Las migraciones no se ejecutaron en orden
**Solución:**
- Verificar que `inventaries`, `warehouse_floors`, `warehouse_stand_styles` existen primero
- Ejecutar migraciones en secuencia: 1 → 2 → 3 → 4 → 5 → 6 → 7

### Problema: Tabla locations ya existe

**Causa probable:** La tabla ya existe de otro módulo
**Solución:** La migración 3 está diseñada para manejar esto, verifica que no haya conflictos de columnas

---

## 📝 Sincronización con Código Anterior

Las nuevas migraciones están diseñadas para:

1. **Mantener compatibilidad** con tablas existentes (inventaries, locations, etc.)
2. **Agregar ForeignKeys correctas** para la integración entre warehouse e inventarie
3. **Soportar sincronización** entre InventarieLocationItem y WarehouseInventorySlot
4. **Registrar auditoría completa** a través de warehouse_inventory_movements

---

**Estado Final:** Listas para ejecutar

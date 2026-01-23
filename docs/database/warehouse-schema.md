# Esquema de Base de Datos - Módulo Warehouse

## Descripción General

Este documento detalla el esquema completo de base de datos del módulo Warehouse, incluyendo todas las tablas, columnas, índices, relaciones y restricciones.

## Tablas del Sistema

El módulo Warehouse utiliza **14 tablas principales** organizadas en una jerarquía de 5 niveles.

---

## 1. Tabla `warehouses`

Almacena los almacenes/instalaciones principales del sistema.

### Estructura

| Columna | Tipo | Null | Default | Descripción |
|---------|------|------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave primaria |
| `uid` | CHAR(36) | NO | UUID | Identificador único universal |
| `code` | VARCHAR(50) | YES | NULL | Código único del almacén |
| `name` | VARCHAR(100) | NO | - | Nombre del almacén |
| `description` | TEXT | YES | NULL | Descripción detallada |
| `available` | BOOLEAN | NO | 1 | Estado de disponibilidad |
| `deleted_at` | TIMESTAMP | YES | NULL | Fecha de borrado lógico |
| `created_at` | TIMESTAMP | YES | NULL | Fecha de creación |
| `updated_at` | TIMESTAMP | YES | NULL | Fecha de última actualización |

### Índices

```sql
PRIMARY KEY (id)
UNIQUE KEY warehouses_uid_unique (uid)
UNIQUE KEY warehouses_code_unique (code)
INDEX warehouses_available_index (available)
INDEX warehouses_deleted_at_index (deleted_at)
```

### Relaciones

- **hasMany**: `warehouse_floors` (1:N)
- **hasMany**: `warehouse_locations` (1:N)
- **belongsToMany**: `users` via `user_warehouse` (N:M)
- **belongsToMany**: `shops` via `warehouse_shops` (N:M)

### Ejemplo de Datos

```sql
INSERT INTO warehouses (uid, code, name, description, available) VALUES
('550e8400-e29b-41d4-a716-446655440001', 'ALM-COR-01', 'Almacén Central Coruña', 'Almacén principal en Coruña', 1),
('550e8400-e29b-41d4-a716-446655440002', 'ALM-MAD-01', 'Almacén Madrid', 'Almacén secundario en Madrid', 1);
```

---

## 2. Tabla `warehouse_floors`

Almacena los pisos/niveles de cada almacén.

### Estructura

| Columna | Tipo | Null | Default | Descripción |
|---------|------|------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave primaria |
| `uid` | CHAR(36) | NO | UUID | Identificador único |
| `warehouse_id` | BIGINT UNSIGNED | NO | - | FK a `warehouses.id` |
| `code` | VARCHAR(50) | NO | - | Código del piso (ej: P1, P2) |
| `name` | VARCHAR(100) | NO | - | Nombre del piso |
| `level` | INT | NO | 0 | Número de nivel (0=planta baja) |
| `available` | BOOLEAN | NO | 1 | Estado de disponibilidad |
| `created_at` | TIMESTAMP | YES | NULL | Fecha de creación |
| `updated_at` | TIMESTAMP | YES | NULL | Fecha de actualización |

### Índices

```sql
PRIMARY KEY (id)
UNIQUE KEY warehouse_floors_uid_unique (uid)
UNIQUE KEY warehouse_floors_warehouse_code_unique (warehouse_id, code)
INDEX warehouse_floors_warehouse_id_index (warehouse_id)
INDEX warehouse_floors_available_index (available)
```

### Restricciones

```sql
FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
```

### Relaciones

- **belongsTo**: `warehouse` (N:1)
- **hasMany**: `warehouse_locations` (1:N)

### Ejemplo de Datos

```sql
INSERT INTO warehouse_floors (uid, warehouse_id, code, name, level, available) VALUES
('650e8400-e29b-41d4-a716-446655440001', 1, 'P0', 'Planta Baja', 0, 1),
('650e8400-e29b-41d4-a716-446655440002', 1, 'P1', 'Primer Piso', 1, 1),
('650e8400-e29b-41d4-a716-446655440003', 1, 'P2', 'Segundo Piso', 2, 1);
```

---

## 3. Tabla `warehouse_location_styles`

Define los estilos visuales para las ubicaciones (colores, dimensiones, iconos).

### Estructura

| Columna | Tipo | Null | Default | Descripción |
|---------|------|------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave primaria |
| `uid` | CHAR(36) | NO | UUID | Identificador único |
| `name` | VARCHAR(100) | NO | - | Nombre del estilo |
| `code` | VARCHAR(50) | NO | - | Código del estilo |
| `color` | VARCHAR(7) | YES | NULL | Color hexadecimal (#RRGGBB) |
| `icon` | VARCHAR(50) | YES | NULL | Icono Font Awesome |
| `width` | INT | YES | 100 | Ancho en píxeles |
| `height` | INT | YES | 80 | Alto en píxeles |
| `description` | TEXT | YES | NULL | Descripción |
| `created_at` | TIMESTAMP | YES | NULL | Fecha de creación |
| `updated_at` | TIMESTAMP | YES | NULL | Fecha de actualización |

### Índices

```sql
PRIMARY KEY (id)
UNIQUE KEY warehouse_location_styles_uid_unique (uid)
UNIQUE KEY warehouse_location_styles_code_unique (code)
```

### Relaciones

- **hasMany**: `warehouse_locations` (1:N)

### Ejemplo de Datos

```sql
INSERT INTO warehouse_location_styles (uid, name, code, color, icon, width, height) VALUES
('750e8400-e29b-41d4-a716-446655440001', 'Estantería Grande', 'SHELF-LG', '#3498db', 'fa-warehouse', 120, 100),
('750e8400-e29b-41d4-a716-446655440002', 'Estantería Mediana', 'SHELF-MD', '#2ecc71', 'fa-box', 100, 80),
('750e8400-e29b-41d4-a716-446655440003', 'Pallet', 'PALLET', '#e74c3c', 'fa-pallet', 80, 80);
```

---

## 4. Tabla `warehouse_location_conditions`

Define plantillas de condiciones ambientales para ubicaciones.

### Estructura

| Columna | Tipo | Null | Default | Descripción |
|---------|------|------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave primaria |
| `uid` | CHAR(36) | NO | UUID | Identificador único |
| `name` | VARCHAR(100) | NO | - | Nombre de la condición |
| `code` | VARCHAR(50) | NO | - | Código de la condición |
| `temperature_min` | DECIMAL(5,2) | YES | NULL | Temperatura mínima (°C) |
| `temperature_max` | DECIMAL(5,2) | YES | NULL | Temperatura máxima (°C) |
| `humidity_min` | DECIMAL(5,2) | YES | NULL | Humedad mínima (%) |
| `humidity_max` | DECIMAL(5,2) | YES | NULL | Humedad máxima (%) |
| `description` | TEXT | YES | NULL | Descripción |
| `created_at` | TIMESTAMP | YES | NULL | Fecha de creación |
| `updated_at` | TIMESTAMP | YES | NULL | Fecha de actualización |

### Índices

```sql
PRIMARY KEY (id)
UNIQUE KEY warehouse_location_conditions_uid_unique (uid)
UNIQUE KEY warehouse_location_conditions_code_unique (code)
```

### Ejemplo de Datos

```sql
INSERT INTO warehouse_location_conditions (uid, name, code, temperature_min, temperature_max, humidity_min, humidity_max) VALUES
('850e8400-e29b-41d4-a716-446655440001', 'Ambiente Normal', 'NORMAL', 15.00, 25.00, 30.00, 60.00),
('850e8400-e29b-41d4-a716-446655440002', 'Refrigerado', 'COLD', 2.00, 8.00, 40.00, 70.00),
('850e8400-e29b-41d4-a716-446655440003', 'Congelado', 'FROZEN', -25.00, -18.00, NULL, NULL);
```

---

## 5. Tabla `warehouse_locations`

Almacena las ubicaciones/estanterías dentro de cada piso.

### Estructura

| Columna | Tipo | Null | Default | Descripción |
|---------|------|------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave primaria |
| `uid` | CHAR(36) | NO | UUID | Identificador único |
| `warehouse_id` | BIGINT UNSIGNED | NO | - | FK a `warehouses.id` |
| `floor_id` | BIGINT UNSIGNED | NO | - | FK a `warehouse_floors.id` |
| `style_id` | BIGINT UNSIGNED | YES | NULL | FK a `warehouse_location_styles.id` |
| `code` | VARCHAR(50) | NO | - | Código de ubicación (ej: A-01) |
| `barcode` | VARCHAR(100) | YES | NULL | Código de barras |
| `position_x` | INT | YES | 0 | Posición X en el mapa visual |
| `position_y` | INT | YES | 0 | Posición Y en el mapa visual |
| `total_levels` | INT | NO | 1 | Número total de niveles |
| `width` | INT | YES | NULL | Ancho personalizado (píxeles) |
| `height` | INT | YES | NULL | Alto personalizado (píxeles) |
| `available` | BOOLEAN | NO | 1 | Estado de disponibilidad |
| `created_at` | TIMESTAMP | YES | NULL | Fecha de creación |
| `updated_at` | TIMESTAMP | YES | NULL | Fecha de actualización |

### Índices

```sql
PRIMARY KEY (id)
UNIQUE KEY warehouse_locations_uid_unique (uid)
UNIQUE KEY warehouse_locations_floor_code_unique (floor_id, code)
INDEX warehouse_locations_warehouse_id_index (warehouse_id)
INDEX warehouse_locations_floor_id_index (floor_id)
INDEX warehouse_locations_style_id_index (style_id)
INDEX warehouse_locations_available_index (available)
INDEX warehouse_locations_barcode_index (barcode)
```

### Restricciones

```sql
FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
FOREIGN KEY (floor_id) REFERENCES warehouse_floors(id) ON DELETE CASCADE
FOREIGN KEY (style_id) REFERENCES warehouse_location_styles(id) ON DELETE SET NULL
```

### Relaciones

- **belongsTo**: `warehouse` (N:1)
- **belongsTo**: `warehouse_floor` (N:1)
- **belongsTo**: `warehouse_location_style` (N:1)
- **hasMany**: `warehouse_location_sections` (1:N)
- **hasManyThrough**: `warehouse_inventory_slots` via `sections` (1:N:N)

### Ejemplo de Datos

```sql
INSERT INTO warehouse_locations (uid, warehouse_id, floor_id, style_id, code, barcode, position_x, position_y, total_levels) VALUES
('950e8400-e29b-41d4-a716-446655440001', 1, 1, 1, 'A-01', 'LOC-A-01', 100, 100, 5),
('950e8400-e29b-41d4-a716-446655440002', 1, 1, 1, 'A-02', 'LOC-A-02', 220, 100, 5),
('950e8400-e29b-41d4-a716-446655440003', 1, 1, 2, 'B-01', 'LOC-B-01', 100, 220, 3);
```

---

## 6. Tabla `warehouse_location_sections`

Almacena las secciones/caras de cada ubicación (frente, trasera, laterales).

### Estructura

| Columna | Tipo | Null | Default | Descripción |
|---------|------|------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave primaria |
| `uid` | CHAR(36) | NO | UUID | Identificador único |
| `location_id` | BIGINT UNSIGNED | NO | - | FK a `warehouse_locations.id` |
| `code` | VARCHAR(50) | NO | - | Código de sección (ej: F, B, L, R) |
| `barcode` | VARCHAR(100) | YES | NULL | Código de barras de la sección |
| `level` | INT | NO | 1 | Nivel/altura de la sección |
| `face` | VARCHAR(20) | NO | 'front' | Cara (front, back, left, right) |
| `weight_max` | DECIMAL(10,2) | YES | NULL | Peso máximo (kg) |
| `max_quantity` | INT | YES | NULL | Cantidad máxima de productos |
| `available` | BOOLEAN | NO | 1 | Estado de disponibilidad |
| `created_at` | TIMESTAMP | YES | NULL | Fecha de creación |
| `updated_at` | TIMESTAMP | YES | NULL | Fecha de actualización |

### Índices

```sql
PRIMARY KEY (id)
UNIQUE KEY warehouse_location_sections_uid_unique (uid)
UNIQUE KEY warehouse_location_sections_location_code_unique (location_id, code)
INDEX warehouse_location_sections_location_id_index (location_id)
INDEX warehouse_location_sections_barcode_index (barcode)
INDEX warehouse_location_sections_available_index (available)
```

### Restricciones

```sql
FOREIGN KEY (location_id) REFERENCES warehouse_locations(id) ON DELETE CASCADE
```

### Relaciones

- **belongsTo**: `warehouse_location` (N:1)
- **hasMany**: `warehouse_inventory_slots` (1:N)

### Ejemplo de Datos

```sql
INSERT INTO warehouse_location_sections (uid, location_id, code, barcode, level, face, weight_max, max_quantity) VALUES
('a50e8400-e29b-41d4-a716-446655440001', 1, 'A-01-F-L1', 'SEC-A-01-F-L1', 1, 'front', 100.00, 50),
('a50e8400-e29b-41d4-a716-446655440002', 1, 'A-01-F-L2', 'SEC-A-01-F-L2', 2, 'front', 100.00, 50),
('a50e8400-e29b-41d4-a716-446655440003', 1, 'A-01-B-L1', 'SEC-A-01-B-L1', 1, 'back', 100.00, 50);
```

---

## 7. Tabla `warehouse_inventory_slots`

Almacena las posiciones individuales de inventario (slots) donde se guardan productos.

### Estructura

| Columna | Tipo | Null | Default | Descripción |
|---------|------|------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave primaria |
| `uid` | CHAR(36) | NO | UUID | Identificador único |
| `section_id` | BIGINT UNSIGNED | NO | - | FK a `warehouse_location_sections.id` |
| `product_id` | BIGINT UNSIGNED | YES | NULL | FK a `products.id` |
| `quantity` | INT | NO | 0 | Cantidad actual de producto |
| `kardex` | INT | YES | NULL | Referencia al libro mayor |
| `is_occupied` | BOOLEAN | NO | 0 | Estado de ocupación |
| `last_movement` | TIMESTAMP | YES | NULL | Fecha del último movimiento |
| `last_section_id` | BIGINT UNSIGNED | YES | NULL | Sección anterior (para transferencias) |
| `created_at` | TIMESTAMP | YES | NULL | Fecha de creación |
| `updated_at` | TIMESTAMP | YES | NULL | Fecha de actualización |

### Índices

```sql
PRIMARY KEY (id)
UNIQUE KEY warehouse_inventory_slots_uid_unique (uid)
INDEX warehouse_inventory_slots_section_id_index (section_id)
INDEX warehouse_inventory_slots_product_id_index (product_id)
INDEX warehouse_inventory_slots_is_occupied_index (is_occupied)
INDEX warehouse_inventory_slots_last_movement_index (last_movement)
```

### Restricciones

```sql
FOREIGN KEY (section_id) REFERENCES warehouse_location_sections(id) ON DELETE CASCADE
FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
FOREIGN KEY (last_section_id) REFERENCES warehouse_location_sections(id) ON DELETE SET NULL
```

### Relaciones

- **belongsTo**: `warehouse_location_section` (N:1)
- **belongsTo**: `product` (N:1)
- **hasMany**: `warehouse_inventory_movements` (1:N)
- **hasOneThrough**: `warehouse_location` via `section` (N:1:1)

### Ejemplo de Datos

```sql
INSERT INTO warehouse_inventory_slots (uid, section_id, product_id, quantity, is_occupied, last_movement) VALUES
('b50e8400-e29b-41d4-a716-446655440001', 1, 100, 50, 1, NOW()),
('b50e8400-e29b-41d4-a716-446655440002', 2, 101, 30, 1, NOW()),
('b50e8400-e29b-41d4-a716-446655440003', 3, NULL, 0, 0, NULL);
```

---

## 8. Tabla `warehouse_inventory_movements`

Registro completo de auditoría de todos los movimientos de inventario.

### Estructura

| Columna | Tipo | Null | Default | Descripción |
|---------|------|------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave primaria |
| `uid` | CHAR(36) | NO | UUID | Identificador único |
| `slot_id` | BIGINT UNSIGNED | NO | - | FK a `warehouse_inventory_slots.id` |
| `user_id` | BIGINT UNSIGNED | YES | NULL | FK a `users.id` |
| `movement_type` | VARCHAR(20) | NO | - | Tipo: add, subtract, move, clear, count |
| `from_quantity` | INT | NO | 0 | Cantidad antes del movimiento |
| `to_quantity` | INT | NO | 0 | Cantidad después del movimiento |
| `quantity_changed` | INT | NO | 0 | Cantidad modificada (+/-) |
| `reason` | TEXT | YES | NULL | Razón del movimiento |
| `reference` | VARCHAR(100) | YES | NULL | Referencia externa (PO, SO, etc.) |
| `notes` | TEXT | YES | NULL | Notas adicionales |
| `created_at` | TIMESTAMP | YES | NULL | Fecha del movimiento |
| `updated_at` | TIMESTAMP | YES | NULL | Fecha de actualización |

### Índices

```sql
PRIMARY KEY (id)
UNIQUE KEY warehouse_inventory_movements_uid_unique (uid)
INDEX warehouse_inventory_movements_slot_id_index (slot_id)
INDEX warehouse_inventory_movements_user_id_index (user_id)
INDEX warehouse_inventory_movements_movement_type_index (movement_type)
INDEX warehouse_inventory_movements_created_at_index (created_at)
INDEX warehouse_inventory_movements_reference_index (reference)
```

### Restricciones

```sql
FOREIGN KEY (slot_id) REFERENCES warehouse_inventory_slots(id) ON DELETE CASCADE
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
```

### Relaciones

- **belongsTo**: `warehouse_inventory_slot` (N:1)
- **belongsTo**: `user` (N:1)

### Tipos de Movimiento

| Tipo | Descripción | Ejemplo |
|------|-------------|---------|
| `add` | Agregar inventario | Recepción de mercancía |
| `subtract` | Restar inventario | Picking/salida de producto |
| `move` | Mover a otra ubicación | Reorganización de almacén |
| `clear` | Vaciar slot | Retiro por daños |
| `count` | Conteo/ajuste de inventario | Inventario físico |

### Ejemplo de Datos

```sql
INSERT INTO warehouse_inventory_movements (uid, slot_id, user_id, movement_type, from_quantity, to_quantity, quantity_changed, reason) VALUES
('c50e8400-e29b-41d4-a716-446655440001', 1, 5, 'add', 0, 50, 50, 'Recepción orden de compra PO-1234'),
('c50e8400-e29b-41d4-a716-446655440002', 1, 7, 'subtract', 50, 30, -20, 'Picking orden de venta SO-5678'),
('c50e8400-e29b-41d4-a716-446655440003', 1, 5, 'count', 30, 28, -2, 'Ajuste inventario físico');
```

---

## 9. Tabla `warehouse_inventory_operations`

Almacena operaciones masivas de inventario que afectan múltiples slots.

### Estructura

| Columna | Tipo | Null | Default | Descripción |
|---------|------|------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave primaria |
| `uid` | CHAR(36) | NO | UUID | Identificador único |
| `warehouse_id` | BIGINT UNSIGNED | NO | - | FK a `warehouses.id` |
| `user_id` | BIGINT UNSIGNED | YES | NULL | FK a `users.id` |
| `operation_type` | VARCHAR(50) | NO | - | Tipo de operación |
| `status` | VARCHAR(20) | NO | 'pending' | Estado: pending, processing, completed, failed |
| `total_items` | INT | NO | 0 | Total de ítems |
| `processed_items` | INT | NO | 0 | Ítems procesados |
| `description` | TEXT | YES | NULL | Descripción |
| `started_at` | TIMESTAMP | YES | NULL | Fecha de inicio |
| `completed_at` | TIMESTAMP | YES | NULL | Fecha de finalización |
| `created_at` | TIMESTAMP | YES | NULL | Fecha de creación |
| `updated_at` | TIMESTAMP | YES | NULL | Fecha de actualización |

### Índices

```sql
PRIMARY KEY (id)
UNIQUE KEY warehouse_inventory_operations_uid_unique (uid)
INDEX warehouse_inventory_operations_warehouse_id_index (warehouse_id)
INDEX warehouse_inventory_operations_user_id_index (user_id)
INDEX warehouse_inventory_operations_status_index (status)
INDEX warehouse_inventory_operations_created_at_index (created_at)
```

### Restricciones

```sql
FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
```

### Relaciones

- **belongsTo**: `warehouse` (N:1)
- **belongsTo**: `user` (N:1)
- **hasMany**: `warehouse_operation_items` (1:N)

---

## 10. Tabla `warehouse_operation_items`

Almacena los ítems individuales de cada operación masiva.

### Estructura

| Columna | Tipo | Null | Default | Descripción |
|---------|------|------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave primaria |
| `uid` | CHAR(36) | NO | UUID | Identificador único |
| `operation_id` | BIGINT UNSIGNED | NO | - | FK a `warehouse_inventory_operations.id` |
| `slot_id` | BIGINT UNSIGNED | YES | NULL | FK a `warehouse_inventory_slots.id` |
| `product_id` | BIGINT UNSIGNED | YES | NULL | FK a `products.id` |
| `quantity` | INT | NO | 0 | Cantidad |
| `status` | VARCHAR(20) | NO | 'pending' | Estado del ítem |
| `error_message` | TEXT | YES | NULL | Mensaje de error |
| `created_at` | TIMESTAMP | YES | NULL | Fecha de creación |
| `updated_at` | TIMESTAMP | YES | NULL | Fecha de actualización |

### Índices

```sql
PRIMARY KEY (id)
UNIQUE KEY warehouse_operation_items_uid_unique (uid)
INDEX warehouse_operation_items_operation_id_index (operation_id)
INDEX warehouse_operation_items_slot_id_index (slot_id)
INDEX warehouse_operation_items_product_id_index (product_id)
INDEX warehouse_operation_items_status_index (status)
```

### Restricciones

```sql
FOREIGN KEY (operation_id) REFERENCES warehouse_inventory_operations(id) ON DELETE CASCADE
FOREIGN KEY (slot_id) REFERENCES warehouse_inventory_slots(id) ON DELETE SET NULL
FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
```

---

## 11. Tabla `user_warehouse` (Pivote)

Tabla pivote para la relación muchos-a-muchos entre usuarios y almacenes.

### Estructura

| Columna | Tipo | Null | Default | Descripción |
|---------|------|------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave primaria |
| `user_id` | BIGINT UNSIGNED | NO | - | FK a `users.id` |
| `warehouse_id` | BIGINT UNSIGNED | NO | - | FK a `warehouses.id` |
| `is_default` | BOOLEAN | NO | 0 | Almacén predeterminado del usuario |
| `can_transfer` | BOOLEAN | NO | 0 | Permiso de transferencia |
| `can_inventory` | BOOLEAN | NO | 0 | Permiso de inventario |
| `created_at` | TIMESTAMP | YES | NULL | Fecha de creación |
| `updated_at` | TIMESTAMP | YES | NULL | Fecha de actualización |

### Índices

```sql
PRIMARY KEY (id)
UNIQUE KEY user_warehouse_user_id_warehouse_id_unique (user_id, warehouse_id)
INDEX user_warehouse_user_id_index (user_id)
INDEX user_warehouse_warehouse_id_index (warehouse_id)
```

### Restricciones

```sql
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
```

### Ejemplo de Datos

```sql
INSERT INTO user_warehouse (user_id, warehouse_id, is_default, can_transfer, can_inventory) VALUES
(5, 1, 1, 1, 1),  -- Usuario 5: Almacén 1 es default, puede transferir e inventariar
(7, 1, 0, 0, 1),  -- Usuario 7: Solo puede inventariar
(8, 2, 1, 1, 0);  -- Usuario 8: Puede transferir pero no inventariar
```

---

## 12. Tabla `warehouse_shops` (Pivote)

Tabla pivote para la relación muchos-a-muchos entre tiendas y almacenes.

### Estructura

| Columna | Tipo | Null | Default | Descripción |
|---------|------|------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave primaria |
| `shop_id` | BIGINT UNSIGNED | NO | - | FK a `shops.id` |
| `warehouse_id` | BIGINT UNSIGNED | NO | - | FK a `warehouses.id` |
| `is_default` | BOOLEAN | NO | 0 | Almacén predeterminado de la tienda |
| `created_at` | TIMESTAMP | YES | NULL | Fecha de creación |
| `updated_at` | TIMESTAMP | YES | NULL | Fecha de actualización |

### Índices

```sql
PRIMARY KEY (id)
UNIQUE KEY warehouse_shops_shop_id_warehouse_id_unique (shop_id, warehouse_id)
INDEX warehouse_shops_shop_id_index (shop_id)
INDEX warehouse_shops_warehouse_id_index (warehouse_id)
```

### Restricciones

```sql
FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
```

---

## Diagrama de Relaciones (ERD)

```
┌──────────────────┐
│    warehouses    │
│  (almacenes)     │
└────────┬─────────┘
         │
         │ 1:N
         │
┌────────▼─────────┐         ┌───────────────────────────┐
│ warehouse_floors │         │ warehouse_location_styles │
│    (pisos)       │         │       (estilos)           │
└────────┬─────────┘         └────────────┬──────────────┘
         │                                 │
         │ 1:N                             │ 1:N
         │                                 │
┌────────▼─────────────────────────────────▼──────┐
│          warehouse_locations                     │
│            (ubicaciones)                         │
└────────┬─────────────────────────────────────────┘
         │
         │ 1:N
         │
┌────────▼──────────────────┐
│ warehouse_location_sections│
│       (secciones)          │
└────────┬───────────────────┘
         │
         │ 1:N
         │
┌────────▼────────────────────┐        ┌───────────┐
│ warehouse_inventory_slots   │───────>│ products  │
│         (slots)             │  N:1   │           │
└────────┬────────────────────┘        └───────────┘
         │
         │ 1:N
         │
┌────────▼─────────────────────┐       ┌──────────┐
│ warehouse_inventory_movements│──────>│  users   │
│      (movimientos)            │  N:1  │          │
└───────────────────────────────┘       └──────────┘
```

## Consultas SQL Útiles

### 1. Obtener Inventario Completo de un Almacén

```sql
SELECT
    w.name AS warehouse_name,
    wf.name AS floor_name,
    wl.code AS location_code,
    wls.code AS section_code,
    p.name AS product_name,
    wis.quantity,
    wis.last_movement
FROM warehouses w
JOIN warehouse_floors wf ON wf.warehouse_id = w.id
JOIN warehouse_locations wl ON wl.floor_id = wf.id
JOIN warehouse_location_sections wls ON wls.location_id = wl.id
JOIN warehouse_inventory_slots wis ON wis.section_id = wls.id
LEFT JOIN products p ON p.id = wis.product_id
WHERE w.id = 1
  AND wis.is_occupied = 1
ORDER BY wf.level, wl.code, wls.code;
```

### 2. Historial de Movimientos de un Producto

```sql
SELECT
    wim.created_at,
    u.name AS user_name,
    wim.movement_type,
    wim.from_quantity,
    wim.to_quantity,
    wim.quantity_changed,
    wim.reason,
    wls.code AS section_code
FROM warehouse_inventory_movements wim
JOIN warehouse_inventory_slots wis ON wis.id = wim.slot_id
JOIN warehouse_location_sections wls ON wls.id = wis.section_id
LEFT JOIN users u ON u.id = wim.user_id
WHERE wis.product_id = 100
ORDER BY wim.created_at DESC
LIMIT 50;
```

### 3. Estadísticas de Ocupación por Piso

```sql
SELECT
    wf.name AS floor_name,
    COUNT(DISTINCT wl.id) AS total_locations,
    COUNT(wis.id) AS total_slots,
    SUM(CASE WHEN wis.is_occupied = 1 THEN 1 ELSE 0 END) AS occupied_slots,
    ROUND(SUM(CASE WHEN wis.is_occupied = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(wis.id), 2) AS occupancy_percentage
FROM warehouse_floors wf
JOIN warehouse_locations wl ON wl.floor_id = wf.id
JOIN warehouse_location_sections wls ON wls.location_id = wl.id
JOIN warehouse_inventory_slots wis ON wis.section_id = wls.id
WHERE wf.warehouse_id = 1
GROUP BY wf.id, wf.name
ORDER BY wf.level;
```

### 4. Productos con Stock Bajo (menos de 10 unidades)

```sql
SELECT
    p.name AS product_name,
    wl.code AS location,
    wls.code AS section,
    wis.quantity,
    wis.last_movement
FROM warehouse_inventory_slots wis
JOIN products p ON p.id = wis.product_id
JOIN warehouse_location_sections wls ON wls.id = wis.section_id
JOIN warehouse_locations wl ON wl.id = wls.location_id
WHERE wis.quantity < 10
  AND wis.quantity > 0
  AND wl.warehouse_id = 1
ORDER BY wis.quantity ASC;
```

### 5. Usuarios Asignados a un Almacén

```sql
SELECT
    u.name AS user_name,
    u.email,
    uw.is_default,
    uw.can_transfer,
    uw.can_inventory,
    uw.created_at AS assigned_at
FROM user_warehouse uw
JOIN users u ON u.id = uw.user_id
WHERE uw.warehouse_id = 1
ORDER BY uw.is_default DESC, u.name;
```

## Índices Recomendados para Rendimiento

```sql
-- Búsqueda por código de barras
CREATE INDEX idx_locations_barcode ON warehouse_locations(barcode);
CREATE INDEX idx_sections_barcode ON warehouse_location_sections(barcode);

-- Búsqueda de slots ocupados
CREATE INDEX idx_slots_occupied ON warehouse_inventory_slots(is_occupied, section_id);

-- Filtros de movimientos por fecha
CREATE INDEX idx_movements_date_range ON warehouse_inventory_movements(created_at, slot_id);

-- Búsqueda de productos en almacén
CREATE INDEX idx_slots_product_warehouse
ON warehouse_inventory_slots(product_id, is_occupied)
INCLUDE (quantity, section_id);
```

## Mantenimiento de Base de Datos

### Limpieza de Movimientos Antiguos

```sql
-- Archivar movimientos mayores a 2 años
INSERT INTO warehouse_inventory_movements_archive
SELECT * FROM warehouse_inventory_movements
WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR);

DELETE FROM warehouse_inventory_movements
WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR);
```

### Recalcular Estados de Ocupación

```sql
UPDATE warehouse_inventory_slots
SET is_occupied = CASE
    WHEN product_id IS NOT NULL AND quantity > 0 THEN 1
    ELSE 0
END;
```

## Consideraciones de Escalabilidad

1. **Particionamiento**: Considerar particionar `warehouse_inventory_movements` por fecha
2. **Archivado**: Mover movimientos antiguos a tabla de archivo
3. **Índices**: Revisar y optimizar índices regularmente
4. **Estadísticas**: Actualizar estadísticas de PostgreSQL semanalmente
5. **Vacuuming**: Configurar autovacuum apropiadamente

## Referencias

- [Documentación General del Módulo](../backend/warehouse-module.md)
- [Endpoints y API](../api/warehouse-endpoints.md)
- [Guía de Permisos](../backend/warehouse-permissions.md)

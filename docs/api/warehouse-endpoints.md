# API y Endpoints - Módulo Warehouse

## Descripción General

Este documento detalla todos los endpoints HTTP del módulo Warehouse, incluyendo rutas de manager, worker, y API. Incluye métodos, parámetros, respuestas, códigos de estado y ejemplos de uso.

---

## Índice

1. [Endpoints de Manager](#endpoints-de-manager)
2. [Endpoints de Worker](#endpoints-de-worker)
3. [Endpoints de API](#endpoints-de-api)
4. [Autenticación](#autenticación)
5. [Códigos de Estado](#códigos-de-estado)
6. [Ejemplos de Uso](#ejemplos-de-uso)

---

## Endpoints de Manager

**Prefijo Base**: `/settings/warehouse`
**Middleware**: `['web', 'auth', 'role:super-admin|manager', 'permission:warehouse.manage']`

### 1. Gestión de Almacenes

#### 1.1 Listar Almacenes

```http
GET /settings/warehouse
```

**Descripción**: Obtiene listado paginado de todos los almacenes.

**Parámetros de Query**:
- `page` (opcional): Número de página (default: 1)
- `per_page` (opcional): Resultados por página (default: 15)
- `search` (opcional): Búsqueda por nombre o código
- `available` (opcional): Filtrar por disponibilidad (0/1)

**Respuesta Exitosa** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "uid": "550e8400-e29b-41d4-a716-446655440001",
      "code": "ALM-COR-01",
      "name": "Almacén Central Coruña",
      "description": "Almacén principal",
      "available": true,
      "created_at": "2025-01-01T10:00:00.000000Z",
      "updated_at": "2025-01-01T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 5,
    "last_page": 1
  }
}
```

#### 1.2 Crear Almacén

```http
GET /settings/warehouse/create
POST /settings/warehouse/store
```

**Método GET**: Muestra formulario de creación
**Método POST**: Crea nuevo almacén

**Parámetros del Body** (POST):
```json
{
  "code": "ALM-MAD-01",
  "name": "Almacén Madrid",
  "description": "Almacén secundario en Madrid",
  "available": true
}
```

**Validación**:
- `code`: requerido, único, máx 50 caracteres
- `name`: requerido, máx 100 caracteres
- `description`: opcional, texto
- `available`: opcional, booleano

**Respuesta Exitosa** (201 Created):
```json
{
  "message": "Almacén creado exitosamente",
  "data": {
    "id": 2,
    "uid": "650e8400-e29b-41d4-a716-446655440002",
    "code": "ALM-MAD-01",
    "name": "Almacén Madrid"
  }
}
```

#### 1.3 Ver Detalles de Almacén

```http
GET /settings/warehouse/{warehouse_uid}
```

**Parámetros de Ruta**:
- `warehouse_uid`: UUID del almacén

**Respuesta Exitosa** (200 OK):
```json
{
  "data": {
    "id": 1,
    "uid": "550e8400-e29b-41d4-a716-446655440001",
    "code": "ALM-COR-01",
    "name": "Almacén Central Coruña",
    "description": "Almacén principal",
    "available": true,
    "floors_count": 3,
    "locations_count": 120,
    "occupied_slots_count": 450,
    "total_slots_count": 1200,
    "occupancy_percentage": 37.5,
    "floors": [
      {
        "id": 1,
        "name": "Planta Baja",
        "level": 0,
        "locations_count": 40
      }
    ]
  }
}
```

#### 1.4 Editar Almacén

```http
GET /settings/warehouse/{warehouse_uid}/edit
POST /settings/warehouse/{warehouse_uid}/update
```

**Método GET**: Muestra formulario de edición
**Método POST**: Actualiza almacén

**Parámetros del Body** (POST):
```json
{
  "code": "ALM-COR-01",
  "name": "Almacén Central Coruña - Actualizado",
  "description": "Descripción actualizada",
  "available": true
}
```

**Respuesta Exitosa** (200 OK):
```json
{
  "message": "Almacén actualizado exitosamente",
  "data": {
    "uid": "550e8400-e29b-41d4-a716-446655440001",
    "name": "Almacén Central Coruña - Actualizado"
  }
}
```

#### 1.5 Eliminar Almacén

```http
GET /settings/warehouse/{warehouse_uid}/destroy
```

**Parámetros de Ruta**:
- `warehouse_uid`: UUID del almacén

**Respuesta Exitosa** (200 OK):
```json
{
  "message": "Almacén eliminado exitosamente"
}
```

**Nota**: Soft delete - el almacén se marca como eliminado pero no se borra de la base de datos.

#### 1.6 Resumen Estadístico

```http
GET /settings/warehouse/{warehouse_uid}/summary
```

**Respuesta Exitosa** (200 OK):
```json
{
  "data": {
    "warehouse": {
      "uid": "550e8400-e29b-41d4-a716-446655440001",
      "name": "Almacén Central Coruña"
    },
    "statistics": {
      "total_floors": 3,
      "total_locations": 120,
      "total_sections": 480,
      "total_slots": 1200,
      "occupied_slots": 450,
      "occupancy_percentage": 37.5,
      "total_products": 85,
      "total_quantity": 12450
    },
    "recent_movements": [
      {
        "date": "2025-01-04T10:30:00Z",
        "type": "add",
        "quantity": 100,
        "user": "Juan Pérez"
      }
    ]
  }
}
```

---

### 2. Gestión de Pisos

#### 2.1 Listar Pisos de un Almacén

```http
GET /settings/warehouse/{warehouse_uid}/details/floors
```

**Respuesta Exitosa** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "uid": "750e8400-e29b-41d4-a716-446655440001",
      "code": "P0",
      "name": "Planta Baja",
      "level": 0,
      "locations_count": 40,
      "total_slots": 400,
      "occupied_slots": 150,
      "occupancy_percentage": 37.5
    }
  ]
}
```

#### 2.2 Crear Piso

```http
POST /settings/warehouse/{warehouse_uid}/details/floors/store
```

**Parámetros del Body**:
```json
{
  "code": "P3",
  "name": "Tercer Piso",
  "level": 3,
  "available": true
}
```

#### 2.3 Ver Detalles de Piso

```http
GET /settings/warehouse/{warehouse_uid}/details/floors/{floor_uid}
```

**Respuesta Exitosa** (200 OK):
```json
{
  "data": {
    "id": 1,
    "uid": "750e8400-e29b-41d4-a716-446655440001",
    "code": "P0",
    "name": "Planta Baja",
    "level": 0,
    "warehouse": {
      "uid": "550e8400-e29b-41d4-a716-446655440001",
      "name": "Almacén Central Coruña"
    },
    "locations": [
      {
        "code": "A-01",
        "style": "Estantería Grande",
        "sections_count": 4,
        "occupied_slots": 12
      }
    ]
  }
}
```

---

### 3. Gestión de Ubicaciones

#### 3.1 Listar Ubicaciones

```http
GET /settings/warehouse/{warehouse_uid}/details/floors/{floor_uid}/locations
```

**Parámetros de Query**:
- `search`: Buscar por código
- `style_id`: Filtrar por estilo
- `available`: Filtrar por disponibilidad

**Respuesta Exitosa** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "uid": "850e8400-e29b-41d4-a716-446655440001",
      "code": "A-01",
      "barcode": "LOC-A-01",
      "style": {
        "name": "Estantería Grande",
        "color": "#3498db"
      },
      "position": {
        "x": 100,
        "y": 100
      },
      "total_levels": 5,
      "sections_count": 10,
      "occupied_slots": 25,
      "total_slots": 50,
      "occupancy_percentage": 50.0
    }
  ]
}
```

#### 3.2 Crear Ubicación

```http
POST /settings/warehouse/{warehouse_uid}/details/floors/{floor_uid}/locations/store
```

**Parámetros del Body**:
```json
{
  "code": "B-05",
  "style_id": 1,
  "position_x": 250,
  "position_y": 150,
  "total_levels": 4,
  "width": 120,
  "height": 100,
  "available": true
}
```

#### 3.3 Transferir Productos de Ubicación

```http
POST /settings/warehouse/{warehouse_uid}/details/locations/{location_uid}/transfer
```

**Parámetros del Body**:
```json
{
  "target_location_uid": "950e8400-e29b-41d4-a716-446655440002",
  "product_id": 100,
  "quantity": 50,
  "reason": "Reorganización de almacén"
}
```

**Respuesta Exitosa** (200 OK):
```json
{
  "message": "Transferencia completada exitosamente",
  "data": {
    "from_location": "A-01",
    "to_location": "B-02",
    "product": "Producto X",
    "quantity": 50
  }
}
```

#### 3.4 Imprimir Códigos de Barras

```http
GET /settings/warehouse/{warehouse_uid}/details/locations/print-barcodes
```

**Parámetros de Query**:
- `location_ids[]`: Array de IDs de ubicaciones
- `format`: Formato (pdf/png) - default: pdf

**Respuesta**: Archivo PDF o PNG con códigos de barras

---

### 4. Gestión de Secciones

#### 4.1 Listar Secciones de una Ubicación

```http
GET /settings/warehouse/{warehouse_uid}/details/floors/{floor_uid}/locations/{location_uid}/sections
```

**Respuesta Exitosa** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "uid": "a50e8400-e29b-41d4-a716-446655440001",
      "code": "A-01-F-L1",
      "barcode": "SEC-A-01-F-L1",
      "level": 1,
      "face": "front",
      "weight_max": 100.0,
      "max_quantity": 50,
      "slots_count": 1,
      "occupied_slots": 1,
      "current_weight": 45.5,
      "available_capacity": 50
    }
  ]
}
```

#### 4.2 Crear Sección

```http
POST /settings/warehouse/{warehouse_uid}/details/floors/{floor_uid}/locations/{location_uid}/sections/store
```

**Parámetros del Body**:
```json
{
  "code": "A-01-B-L2",
  "level": 2,
  "face": "back",
  "weight_max": 150.0,
  "max_quantity": 75,
  "available": true
}
```

---

### 5. Gestión de Slots de Inventario

#### 5.1 Listar Slots de una Sección

```http
GET /settings/warehouse/{warehouse_uid}/details/floors/{floor_uid}/locations/{location_uid}/sections/{section_uid}/slots
```

**Respuesta Exitosa** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "uid": "b50e8400-e29b-41d4-a716-446655440001",
      "product": {
        "id": 100,
        "name": "Producto X",
        "sku": "PROD-X-001"
      },
      "quantity": 50,
      "is_occupied": true,
      "last_movement": "2025-01-04T10:30:00Z",
      "address": "ALM-COR-01/P0/A-01/F-L1"
    }
  ]
}
```

#### 5.2 Agregar Cantidad a Slot

```http
POST /settings/warehouse/{warehouse_uid}/details/floors/{floor_uid}/locations/{location_uid}/sections/{section_uid}/slots/{slot_uid}/add-quantity
```

**Parámetros del Body**:
```json
{
  "quantity": 25,
  "reason": "Recepción orden de compra PO-1234",
  "reference": "PO-1234"
}
```

**Respuesta Exitosa** (200 OK):
```json
{
  "message": "Cantidad agregada exitosamente",
  "data": {
    "previous_quantity": 50,
    "added_quantity": 25,
    "new_quantity": 75,
    "slot_uid": "b50e8400-e29b-41d4-a716-446655440001"
  }
}
```

#### 5.3 Restar Cantidad de Slot

```http
POST /settings/warehouse/{warehouse_uid}/details/floors/{floor_uid}/locations/{location_uid}/sections/{section_uid}/slots/{slot_uid}/subtract-quantity
```

**Parámetros del Body**:
```json
{
  "quantity": 15,
  "reason": "Picking orden de venta SO-5678",
  "reference": "SO-5678"
}
```

**Respuesta Exitosa** (200 OK):
```json
{
  "message": "Cantidad restada exitosamente",
  "data": {
    "previous_quantity": 75,
    "subtracted_quantity": 15,
    "new_quantity": 60
  }
}
```

#### 5.4 Mover Slot a Otra Sección

```http
POST /settings/warehouse/{warehouse_uid}/details/floors/{floor_uid}/locations/{location_uid}/sections/{section_uid}/slots/{slot_uid}/move-to
```

**Parámetros del Body**:
```json
{
  "target_section_uid": "a50e8400-e29b-41d4-a716-446655440003",
  "quantity": 30,
  "reason": "Reorganización de inventario"
}
```

**Respuesta Exitosa** (200 OK):
```json
{
  "message": "Slot movido exitosamente",
  "data": {
    "from_section": "A-01-F-L1",
    "to_section": "A-01-B-L1",
    "quantity_moved": 30,
    "remaining_quantity": 30
  }
}
```

#### 5.5 Limpiar Slot

```http
POST /settings/warehouse/{warehouse_uid}/details/floors/{floor_uid}/locations/{location_uid}/sections/{section_uid}/slots/{slot_uid}/clear
```

**Parámetros del Body**:
```json
{
  "reason": "Producto dañado - retiro completo"
}
```

**Respuesta Exitosa** (200 OK):
```json
{
  "message": "Slot limpiado exitosamente",
  "data": {
    "previous_quantity": 60,
    "slot_uid": "b50e8400-e29b-41d4-a716-446655440001",
    "is_occupied": false
  }
}
```

---

### 6. Historial de Movimientos

#### 6.1 Ver Historial

```http
GET /settings/warehouse/{warehouse_uid}/details/history
```

**Parámetros de Query**:
- `date_from`: Fecha inicio (YYYY-MM-DD)
- `date_to`: Fecha fin (YYYY-MM-DD)
- `movement_type`: Tipo de movimiento (add/subtract/move/clear/count)
- `user_id`: ID de usuario
- `product_id`: ID de producto
- `page`: Número de página
- `per_page`: Resultados por página

**Respuesta Exitosa** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "uid": "c50e8400-e29b-41d4-a716-446655440001",
      "movement_type": "add",
      "from_quantity": 0,
      "to_quantity": 50,
      "quantity_changed": 50,
      "reason": "Recepción PO-1234",
      "reference": "PO-1234",
      "user": {
        "name": "Juan Pérez",
        "email": "juan@example.com"
      },
      "slot": {
        "address": "ALM-COR-01/P0/A-01/F-L1",
        "product": "Producto X"
      },
      "created_at": "2025-01-04T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 234
  }
}
```

#### 6.2 Filtrar Historial (POST)

```http
POST /settings/warehouse/{warehouse_uid}/details/history/filter
```

**Parámetros del Body**:
```json
{
  "date_from": "2025-01-01",
  "date_to": "2025-01-31",
  "movement_types": ["add", "subtract"],
  "user_ids": [5, 7],
  "product_ids": [100, 101, 102]
}
```

---

### 7. Reportes

#### 7.1 Vista de Reportes

```http
GET /settings/warehouse/{warehouse_uid}/details/reports
```

Muestra interfaz para generar reportes.

#### 7.2 Reporte de Inventario

```http
POST /settings/warehouse/{warehouse_uid}/details/reports/inventory
```

**Parámetros del Body**:
```json
{
  "format": "pdf",
  "include_empty_slots": false,
  "floor_ids": [1, 2],
  "product_ids": []
}
```

**Respuesta**: Archivo PDF o Excel con reporte de inventario

#### 7.3 Reporte de Movimientos

```http
POST /settings/warehouse/{warehouse_uid}/details/reports/movements
```

**Parámetros del Body**:
```json
{
  "format": "excel",
  "date_from": "2025-01-01",
  "date_to": "2025-01-31",
  "movement_types": ["add", "subtract"],
  "group_by": "day"
}
```

**Respuesta**: Archivo Excel con reporte de movimientos

#### 7.4 Reporte de Ocupación

```http
POST /settings/warehouse/{warehouse_uid}/details/reports/occupancy
```

**Parámetros del Body**:
```json
{
  "format": "pdf",
  "floor_ids": [],
  "include_charts": true
}
```

**Respuesta**: Archivo PDF con análisis de ocupación

#### 7.5 Reporte de Capacidad

```http
POST /settings/warehouse/{warehouse_uid}/details/reports/capacity
```

**Parámetros del Body**:
```json
{
  "format": "pdf",
  "group_by": "floor"
}
```

**Respuesta**: Archivo PDF con análisis de capacidad

---

### 8. Mapa Visual

#### 8.1 Ver Mapa del Almacén

```http
GET /settings/warehouse/{warehouse_uid}/details/map
```

Muestra editor visual interactivo del layout del almacén.

#### 8.2 Obtener Ubicaciones para Mapa

```http
GET /settings/warehouse/{warehouse_uid}/details/map/locations
```

**Parámetros de Query**:
- `floor_id`: ID del piso (opcional)

**Respuesta Exitosa** (200 OK):
```json
{
  "data": [
    {
      "uid": "850e8400-e29b-41d4-a716-446655440001",
      "code": "A-01",
      "position": {
        "x": 100,
        "y": 100
      },
      "dimensions": {
        "width": 120,
        "height": 100
      },
      "style": {
        "color": "#3498db",
        "icon": "fa-warehouse"
      },
      "occupancy": 50.0,
      "status": "available"
    }
  ]
}
```

#### 8.3 Guardar Layout del Mapa

```http
POST /settings/warehouse/{warehouse_uid}/details/map/save-layout
```

**Parámetros del Body**:
```json
{
  "floor_id": 1,
  "locations": [
    {
      "uid": "850e8400-e29b-41d4-a716-446655440001",
      "position_x": 150,
      "position_y": 120
    }
  ]
}
```

---

### 9. Gestión de Estilos de Ubicación

#### 9.1 Listar Estilos

```http
GET /settings/warehouse/styles
```

**Respuesta Exitosa** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "uid": "750e8400-e29b-41d4-a716-446655440001",
      "name": "Estantería Grande",
      "code": "SHELF-LG",
      "color": "#3498db",
      "icon": "fa-warehouse",
      "width": 120,
      "height": 100
    }
  ]
}
```

#### 9.2 Crear Estilo

```http
POST /settings/warehouse/styles/store
```

**Parámetros del Body**:
```json
{
  "name": "Estantería Pequeña",
  "code": "SHELF-SM",
  "color": "#9b59b6",
  "icon": "fa-box",
  "width": 80,
  "height": 60,
  "description": "Para productos pequeños"
}
```

#### 9.3 Actualizar Estilo

```http
POST /settings/warehouse/styles/{style_uid}/update
```

#### 9.4 Importar Estilos

```http
POST /settings/warehouse/styles/import
```

**Parámetros**: Archivo CSV o JSON con definiciones de estilos

---

### 10. Gestión de Tiendas

#### 10.1 Listar Tiendas Asignadas

```http
GET /settings/warehouse/{warehouse_uid}/shops
```

**Respuesta Exitosa** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "name": "Tienda Central",
      "is_default": true,
      "assigned_at": "2025-01-01T10:00:00Z"
    }
  ]
}
```

#### 10.2 Asignar Tienda a Almacén

```http
POST /settings/warehouse/{warehouse_uid}/shops/assign
```

**Parámetros del Body**:
```json
{
  "shop_id": 2,
  "is_default": false
}
```

#### 10.3 Ubicaciones de una Tienda

```http
GET /settings/warehouse/{warehouse_uid}/shops/{shop_id}/locations
```

---

## Endpoints de Worker

**Prefijo Base**: `/warehouse`
**Middleware**: `['web', 'auth', 'permission:warehouse.manage|warehouse.inventory|warehouse.transfer']`

### 1. Dashboard de Worker

```http
GET /warehouse
```

**Respuesta Exitosa** (200 OK):
Vista HTML con dashboard operativo del trabajador.

### 2. Almacenes Asignados

```http
GET /warehouse/warehouses
```

**Respuesta Exitosa** (200 OK):
```json
{
  "data": [
    {
      "uid": "550e8400-e29b-41d4-a716-446655440001",
      "name": "Almacén Central Coruña",
      "is_default": true,
      "permissions": {
        "can_transfer": true,
        "can_inventory": true
      }
    }
  ]
}
```

### 3. Ver Almacén

```http
GET /warehouse/warehouses/{warehouse_slack}/view
```

**Parámetros de Ruta**:
- `warehouse_slack`: Código o UID del almacén

---

### 4. Validación de Ubicaciones

#### 4.1 Validar Ubicación

```http
POST /warehouse/locations/validate/location
```

**Parámetros del Body**:
```json
{
  "barcode": "LOC-A-01"
}
```

**Respuesta Exitosa** (200 OK):
```json
{
  "valid": true,
  "data": {
    "location": {
      "code": "A-01",
      "warehouse": "Almacén Central Coruña",
      "floor": "Planta Baja"
    },
    "sections": [
      {
        "code": "A-01-F-L1",
        "barcode": "SEC-A-01-F-L1",
        "available_capacity": 25
      }
    ]
  }
}
```

#### 4.2 Validar Sección

```http
POST /warehouse/locations/validate/section
```

**Parámetros del Body**:
```json
{
  "barcode": "SEC-A-01-F-L1"
}
```

**Respuesta Exitosa** (200 OK):
```json
{
  "valid": true,
  "data": {
    "section": {
      "code": "A-01-F-L1",
      "level": 1,
      "face": "front",
      "available_capacity": 25,
      "weight_available": 55.5
    },
    "location": {
      "code": "A-01",
      "warehouse": "Almacén Central Coruña"
    },
    "slots": [
      {
        "product": "Producto X",
        "quantity": 25
      }
    ]
  }
}
```

---

### 5. Gestión de Sección (Worker)

```http
GET /warehouse/locations/{warehouse_code}/{location_code}/{section_code}
```

**Parámetros de Ruta**:
- `warehouse_code`: Código del almacén
- `location_code`: Código de ubicación
- `section_code`: Código de sección

**Respuesta**: Vista HTML para gestionar la sección (agregar/restar inventario)

---

### 6. Transferencias

#### 6.1 Interfaz de Transferencia

```http
GET /warehouse/transfer
```

**Respuesta**: Vista HTML con interfaz de transferencia

#### 6.2 Buscar Producto

```http
POST /warehouse/transfer/search
```

**Parámetros del Body**:
```json
{
  "query": "Producto X",
  "warehouse_uid": "550e8400-e29b-41d4-a716-446655440001"
}
```

**Respuesta Exitosa** (200 OK):
```json
{
  "data": [
    {
      "product_id": 100,
      "product_name": "Producto X",
      "sku": "PROD-X-001",
      "slots": [
        {
          "slot_uid": "b50e8400-e29b-41d4-a716-446655440001",
          "section_code": "A-01-F-L1",
          "quantity": 50,
          "address": "ALM-COR-01/P0/A-01/F-L1"
        }
      ],
      "total_quantity": 50
    }
  ]
}
```

#### 6.3 Procesar Transferencia

```http
POST /warehouse/transfer/process
```

**Parámetros del Body**:
```json
{
  "from_slot_uid": "b50e8400-e29b-41d4-a716-446655440001",
  "to_section_barcode": "SEC-B-02-F-L1",
  "quantity": 25,
  "reason": "Reorganización de almacén"
}
```

**Respuesta Exitosa** (200 OK):
```json
{
  "message": "Transferencia completada exitosamente",
  "data": {
    "from_address": "ALM-COR-01/P0/A-01/F-L1",
    "to_address": "ALM-COR-01/P0/B-02/F-L1",
    "quantity": 25,
    "remaining_in_source": 25
  }
}
```

#### 6.4 Historial de Transferencias

```http
GET /warehouse/transfer/history
```

**Parámetros de Query**:
- `date_from`: Fecha desde
- `date_to`: Fecha hasta
- `product_id`: Filtrar por producto

---

### 7. Códigos de Barras (Worker)

```http
GET /warehouse/barcode/scan
POST /warehouse/barcode/validate
```

Endpoints para escaneo y validación de códigos de barras en modo worker.

---

## Endpoints de API

**Prefijo Base**: `/api/warehouse`
**Middleware**: `['api', 'auth:sanctum', 'throttle:60,1']`

### Autenticación

Todos los endpoints de API requieren autenticación mediante **Laravel Sanctum**.

**Headers Requeridos**:
```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### 1. Inventario

```http
GET /api/warehouse/{warehouse_uid}/inventory
```

**Respuesta**: Lista completa de inventario del almacén

### 2. Movimientos

```http
GET /api/warehouse/{warehouse_uid}/movements
POST /api/warehouse/{warehouse_uid}/movements
```

**GET**: Listar movimientos
**POST**: Crear nuevo movimiento

### 3. Productos

```http
GET /api/warehouse/{warehouse_uid}/products/{product_id}/locations
```

**Respuesta**: Ubicaciones de un producto específico

---

## Autenticación

### 1. Web (Manager/Worker)

Utiliza sesiones de Laravel con middleware `auth`.

**Login**: `/login` (proporcionado por Laravel)
**Logout**: `/logout`

### 2. API

Utiliza **Laravel Sanctum** con tokens de acceso.

**Obtener Token**:
```http
POST /api/auth/login
```

**Body**:
```json
{
  "email": "usuario@example.com",
  "password": "password"
}
```

**Respuesta**:
```json
{
  "token": "1|abc123def456...",
  "user": {
    "id": 5,
    "name": "Juan Pérez",
    "email": "juan@example.com"
  }
}
```

**Uso del Token**:
```http
GET /api/warehouse/{uid}/inventory
Authorization: Bearer 1|abc123def456...
```

---

## Códigos de Estado

| Código | Significado | Uso |
|--------|-------------|-----|
| **200** | OK | Solicitud exitosa |
| **201** | Created | Recurso creado exitosamente |
| **204** | No Content | Eliminación exitosa |
| **400** | Bad Request | Datos de entrada inválidos |
| **401** | Unauthorized | No autenticado |
| **403** | Forbidden | Sin permisos |
| **404** | Not Found | Recurso no encontrado |
| **422** | Unprocessable Entity | Errores de validación |
| **500** | Internal Server Error | Error del servidor |

---

## Errores de Validación

**Formato de Respuesta** (422):
```json
{
  "message": "Los datos proporcionados no son válidos",
  "errors": {
    "code": [
      "El código ya está en uso"
    ],
    "name": [
      "El nombre es obligatorio"
    ],
    "quantity": [
      "La cantidad debe ser mayor a 0"
    ]
  }
}
```

---

## Ejemplos de Uso

### Ejemplo 1: Crear Almacén y Agregar Inventario

```bash
# 1. Crear almacén
curl -X POST https://alsernet.test/settings/warehouse/store \
  -H "Content-Type: application/json" \
  -H "Cookie: laravel_session=..." \
  -d '{
    "code": "ALM-BCN-01",
    "name": "Almacén Barcelona",
    "description": "Nuevo almacén en Barcelona",
    "available": true
  }'

# 2. Crear piso
curl -X POST https://alsernet.test/settings/warehouse/{warehouse_uid}/details/floors/store \
  -H "Content-Type: application/json" \
  -d '{
    "code": "P0",
    "name": "Planta Baja",
    "level": 0
  }'

# 3. Crear ubicación
curl -X POST https://alsernet.test/settings/warehouse/{warehouse_uid}/details/floors/{floor_uid}/locations/store \
  -H "Content-Type: application/json" \
  -d '{
    "code": "A-01",
    "style_id": 1,
    "total_levels": 5
  }'

# 4. Agregar inventario
curl -X POST https://alsernet.test/settings/warehouse/{warehouse_uid}/details/.../slots/{slot_uid}/add-quantity \
  -H "Content-Type: application/json" \
  -d '{
    "quantity": 100,
    "reason": "Stock inicial",
    "reference": "INV-INICIAL-001"
  }'
```

### Ejemplo 2: Transferir Inventario (Worker)

```bash
# 1. Validar ubicación origen
curl -X POST https://alsernet.test/warehouse/locations/validate/section \
  -H "Content-Type: application/json" \
  -d '{
    "barcode": "SEC-A-01-F-L1"
  }'

# 2. Buscar producto
curl -X POST https://alsernet.test/warehouse/transfer/search \
  -H "Content-Type: application/json" \
  -d '{
    "query": "Producto X",
    "warehouse_uid": "550e8400-e29b-41d4-a716-446655440001"
  }'

# 3. Ejecutar transferencia
curl -X POST https://alsernet.test/warehouse/transfer/process \
  -H "Content-Type: application/json" \
  -d '{
    "from_slot_uid": "b50e8400-e29b-41d4-a716-446655440001",
    "to_section_barcode": "SEC-B-02-F-L1",
    "quantity": 25,
    "reason": "Reorganización"
  }'
```

### Ejemplo 3: Generar Reporte (API)

```bash
# Obtener token de autenticación
TOKEN=$(curl -X POST https://alsernet.test/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }' | jq -r '.token')

# Generar reporte de inventario
curl -X POST https://alsernet.test/settings/warehouse/{warehouse_uid}/details/reports/inventory \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "format": "pdf",
    "include_empty_slots": false
  }' \
  --output inventario.pdf
```

---

## Rate Limiting

- **Web**: Sin límite (sesión autenticada)
- **API**: 60 peticiones por minuto por token

**Headers de Rate Limiting**:
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 57
X-RateLimit-Reset: 1609459200
```

---

## Referencias

- [Documentación General](../backend/warehouse-module.md)
- [Esquema de Base de Datos](../database/warehouse-schema.md)
- [Sistema de Permisos](../backend/warehouse-permissions.md)
- [Guía de Manager](../guides/warehouse-manager-guide.md)
- [Guía de Worker](../guides/warehouse-worker-guide.md)

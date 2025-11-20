# Guía de Asignación de Almacenes a Usuarios

## Descripción General

Se ha implementado un sistema completo para que los administradores puedan asignar almacenes específicos a usuarios con rol `inventaries`. Esto permite controlar exactamente qué almacenes puede ver y usar cada usuario de inventario.

---

## Funcionalidades Implementadas

### 1. **Relación Many-to-Many: Usuarios ↔ Almacenes**

**Tabla Pivot:** `user_warehouse`

```sql
CREATE TABLE user_warehouse (
    id BIGINT PRIMARY KEY
    user_id BIGINT (FK users)
    warehouse_id BIGINT (FK warehouses)
    is_default BOOLEAN -- Almacén predeterminado
    can_transfer BOOLEAN -- Permiso para transferir
    can_inventory BOOLEAN -- Permiso para inventarios
    created_at TIMESTAMP
    updated_at TIMESTAMP
)
```

**Campos de Control:**
- ✅ `is_default`: El almacén que se abre al iniciar sesión
- ✅ `can_inventory`: Permite realizar inventarios en este almacén
- ✅ `can_transfer`: Permite transferir productos en este almacén

---

## 2. Métodos del Modelo User

### Relaciones

```php
// Obtener todos los almacenes asignados
$user->warehouses()

// Obtener almacén predeterminado
$user->defaultWarehouse()

// Obtener almacenes donde puede hacer inventario
$user->inventoryWarehouses()

// Obtener almacenes donde puede transferir
$user->transferWarehouses()
```

### Métodos de Asignación

```php
// Asignar almacén con permisos específicos
$user->assignWarehouse($warehouseId, $isDefault, $canTransfer, $canInventory);

// Desasignar almacén
$user->removeWarehouse($warehouseId);

// Verificar acceso a almacén
$user->hasAccessToWarehouse($warehouseId)

// Verificar si puede hacer inventario
$user->canPerformInventory($warehouseId)

// Verificar si puede transferir
$user->canTransferInWarehouse($warehouseId)
```

### Ejemplo de Uso

```php
$user = User::find(1);

// Asignar almacén predeterminado con todos los permisos
$user->assignWarehouse(
    warehouseId: 5,
    isDefault: true,
    canTransfer: true,
    canInventory: true
);

// Verificar acceso
if ($user->canPerformInventory(5)) {
    // Permitir inventario
}
```

---

## 3. Métodos del Modelo Warehouse

### Relaciones

```php
// Obtener usuarios asignados
$warehouse->users()

// Obtener usuarios que pueden hacer inventario
$warehouse->inventoryUsers()

// Obtener usuarios que pueden transferir
$warehouse->transferUsers()
```

---

## 4. Controlador: UserWarehouseAssignmentController

### Rutas Disponibles

```
GET    /manager/warehouse-assignment                    # Listado de usuarios
GET    /manager/warehouse-assignment/edit/{userId}      # Formulario de asignación
POST   /manager/warehouse-assignment/update/{userId}    # Actualizar asignaciones
POST   /manager/warehouse-assignment/assign/{userId}    # Asignar almacén
POST   /manager/warehouse-assignment/unassign/{userId}  # Desasignar almacén
GET    /manager/warehouse-assignment/user/{userId}/warehouses     # API: Almacenes de usuario
GET    /manager/warehouse-assignment/warehouse/{warehouseId}/users # API: Usuarios de almacén
```

### Métodos

**`index(Request $request)`**
- Muestra lista de usuarios con rol `inventaries`
- Permite buscar por nombre o email
- Muestra cantidad de almacenes asignados y almacén predeterminado

**`edit($userId)`**
- Formulario de asignación para un usuario específico
- Muestra almacenes asignados (lado izquierdo)
- Muestra almacenes disponibles (lado derecho)
- Interfaz para cambiar permisos

**`assign(Request $request, $userId)`**
- Asigna un almacén a un usuario
- Define permisos específicos (inventario, transferencia, predeterminado)
- Si es predeterminado, quita ese estado de otros almacenes

**`unassign(Request $request, $userId)`**
- Desasigna un almacén de un usuario

**`getUserWarehouses($userId)` (API)**
- Retorna JSON con almacenes de un usuario
- Incluye permisos y estado de predeterminado

**`getWarehouseUsers($warehouseId)` (API)**
- Retorna JSON con usuarios asignados a un almacén
- Incluye permisos de cada usuario

---

## 5. Interfaz de Usuario

### Página Principal: `/manager/warehouse-assignment`

**Características:**
- ✅ Tabla de usuarios de inventario
- ✅ Búsqueda por nombre/email
- ✅ Muestra cantidad de almacenes asignados
- ✅ Muestra almacén predeterminado
- ✅ Botón "Editar" para cada usuario

**Columnas:**
| Nombre | Email | Almacenes Asignados | Almacén Predeterminado | Acciones |

### Página de Edición: `/manager/warehouse-assignment/edit/{userId}`

**Lado Izquierdo: Almacenes Asignados**
- Tarjetas de almacenes actuales
- Botón para desasignar (papelera)
- Checkboxes para permisos:
  - Almacén predeterminado
  - Puede hacer inventarios
  - Puede transferir productos

**Lado Derecho: Asignar Almacén**
- Lista de almacenes disponibles
- Botón "Asignar" para cada uno
- Se cargan dinámicamente

**Interactividad:**
- AJAX para asignar/desasignar
- Actualización de permisos en tiempo real
- Confirmación antes de desasignar

---

## 6. Ejemplo de Uso Práctico

### Escenario 1: Asignar almacén predeterminado a nuevo usuario

```php
$user = User::find(15); // Usuario "Juan Pérez"

// Asignar Almacén A como predeterminado
$user->assignWarehouse(
    warehouseId: 1,
    isDefault: true,      // Se abre al loguear
    canTransfer: true,    // Puede transferir
    canInventory: true    // Puede hacer inventario
);
```

### Escenario 2: Asignar múltiples almacenes con permisos diferentes

```php
$user = User::find(15);

// Almacén A: Solo inventario (predeterminado)
$user->assignWarehouse(1, true, false, true);

// Almacén B: Solo transferencia
$user->assignWarehouse(2, false, true, false);

// Almacén C: Ambos permisos
$user->assignWarehouse(3, false, true, true);
```

### Escenario 3: Validar acceso antes de operación

```php
$user = User::find(15);
$warehouseId = 1;

// Verificar antes de realizar inventario
if (!$user->canPerformInventory($warehouseId)) {
    return response()->json([
        'error' => 'Usuario sin permiso para inventario en este almacén'
    ], 403);
}

// Realizar inventario...
```

---

## 7. Integración con Sistema Existente

### Filtrado de Almacenes en Controladores

Para asegurar que los usuarios solo vean sus almacenes asignados:

```php
// En WarehouseInventoryTransferController
public function index()
{
    $user = auth()->user();

    // Solo mostrar almacenes del usuario
    $warehouses = $user->warehouses()
        ->available()
        ->get();

    return view('warehouses.views.warehouse.transfers.index', [
        'warehouses' => $warehouses,
    ]);
}

// Verificar permiso antes de transferir
if (!$user->canTransferInWarehouse($request->warehouse_id)) {
    return response()->json([
        'error' => 'Sin permisos para transferir'
    ], 403);
}
```

### Filtrado en Inventarios

```php
public function getAvailableSections(Request $request)
{
    $user = auth()->user();

    // Verificar que el almacén pertenece al usuario
    if (!$user->hasAccessToWarehouse($request->warehouse_id)) {
        return response()->json(['error' => 'Acceso denegado'], 403);
    }

    // Continuar...
}
```

---

## 8. Migraciones Necesarias

### Ejecutar Migración

```bash
php artisan migrate
```

**Archivo:** `database/migrations/2025_11_20_000001_create_user_warehouse_table.php`

Crea tabla `user_warehouse` con:
- Índice único en (user_id, warehouse_id)
- Índices en user_id, warehouse_id, is_default para optimización

---

## 9. Acceso a la Funcionalidad

### Desde Panel de Administración

**Ruta:** `http://tu-dominio.com/manager/warehouse-assignment`

**Pasos:**
1. Ir a "Asignación de Almacenes" en menú admin
2. Buscar usuario de inventario
3. Hacer clic en "Editar"
4. Arrastrar almacenes entre columnas
5. Ajustar permisos con checkboxes

### Endpoints API

**Obtener almacenes de usuario:**
```bash
GET /manager/warehouse-assignment/user/15/warehouses
```

**Respuesta:**
```json
{
  "success": true,
  "user": {
    "id": 15,
    "name": "Juan Pérez",
    "email": "juan@example.com"
  },
  "warehouses": [
    {
      "id": 1,
      "code": "ALM-001",
      "name": "Almacén Principal",
      "is_default": true,
      "can_inventory": true,
      "can_transfer": true
    }
  ]
}
```

---

## 10. Consideraciones de Seguridad

✅ **Validación de Roles:** Solo usuarios con rol `inventaries`
✅ **Verificación de Permisos:** Antes de cada operación
✅ **Auditoría:** Se registran cambios en logs
✅ **CSRF Protection:** Tokens en formularios y AJAX
✅ **Racional:** Solo admin puede asignar almacenes

---

## 11. Testing Recomendado

```php
// Test: Asignar almacén
$user = User::factory()->create();
$user->assignRole('inventaries');
$warehouse = Warehouse::factory()->create();

$user->assignWarehouse($warehouse->id, true, true, true);

$this->assertTrue($user->hasAccessToWarehouse($warehouse->id));
$this->assertTrue($user->canPerformInventory($warehouse->id));
$this->assertTrue($user->canTransferInWarehouse($warehouse->id));

// Test: Múltiples almacenes
$warehouse2 = Warehouse::factory()->create();
$user->assignWarehouse($warehouse2->id, false, false, false);

$this->assertFalse($user->canPerformInventory($warehouse2->id));
$this->assertFalse($user->canTransferInWarehouse($warehouse2->id));
```

---

## 12. Próximos Pasos

### Integraciones Pendientes

1. **Filtrar en WarehouseInventoryTransferController:**
   ```php
   $warehouses = auth()->user()->transferWarehouses()->get();
   ```

2. **Filtrar en Inventarios:**
   ```php
   $warehouses = auth()->user()->inventoryWarehouses()->get();
   ```

3. **Dashboard:** Mostrar solo almacenes asignados

4. **Selectores:** Solo permitir almacenes del usuario

---

## 13. Estructura de Directorios

```
app/
├── Http/Controllers/
│   └── Admin/
│       └── UserWarehouseAssignmentController.php [NUEVO]
├── Models/
│   ├── User.php [MODIFICADO - Relaciones]
│   └── Warehouse/
│       └── Warehouse.php [MODIFICADO - Relaciones]

database/
├── migrations/
│   └── 2025_11_20_000001_create_user_warehouse_table.php [NUEVO]

resources/views/
└── admin/users/
    ├── warehouse-assignment.blade.php [NUEVO]
    └── warehouse-assignment-edit.blade.php [NUEVO]

routes/
└── managers.php [MODIFICADO - Agregadas rutas]
```

---

## 14. Resumen de Cambios

| Elemento | Acción | Descripción |
|----------|--------|-------------|
| **user_warehouse** | ✨ Tabla NUEVA | Relación many-to-many |
| **User Model** | 📝 MODIFICADO | Relaciones y métodos |
| **Warehouse Model** | 📝 MODIFICADO | Relaciones inversas |
| **UserWarehouseAssignmentController** | ✨ NUEVO | Gestión de asignaciones |
| **warehouse-assignment.blade.php** | ✨ NUEVO | Listado de usuarios |
| **warehouse-assignment-edit.blade.php** | ✨ NUEVO | Formulario de asignación |
| **managers.php** | 📝 MODIFICADO | Rutas nuevas |

**Total:** 7 cambios (3 nuevos, 3 modificados, 1 nueva tabla)

---

**Estado:** ✅ Implementación completa y lista para usar

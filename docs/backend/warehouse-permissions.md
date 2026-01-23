# Sistema de Permisos y Autorización - Módulo Warehouse

## Descripción General

El módulo Warehouse implementa un sistema de autorización granular basado en roles (RBAC - Role-Based Access Control) utilizando el paquete **Spatie Permission**. Este sistema permite control de acceso a nivel de almacén, piso, ubicación y slot de inventario.

---

## Índice

1. [Roles del Sistema](#roles-del-sistema)
2. [Permisos](#permisos)
3. [Políticas de Autorización](#políticas-de-autorización)
4. [Asignación de Usuarios a Almacenes](#asignación-de-usuarios-a-almacenes)
5. [Middleware](#middleware)
6. [Ejemplos de Uso](#ejemplos-de-uso)

---

## Roles del Sistema

El módulo define tres roles principales con diferentes niveles de acceso:

### 1. super-admin

**Descripción**: Acceso completo sin restricciones a todos los almacenes y funcionalidades.

**Permisos**:
- ✅ Acceso total a todos los almacenes
- ✅ Crear, editar y eliminar almacenes
- ✅ Gestionar pisos, ubicaciones y secciones
- ✅ Ver y modificar todo el inventario
- ✅ Generar todos los reportes
- ✅ Asignar usuarios y permisos
- ✅ Configurar estilos y plantillas

**Código de Verificación**:
```php
if ($user->hasRole('super-admin')) {
    // Acceso completo automático
}
```

---

### 2. manager

**Descripción**: Gestión completa de almacenes asignados, requiere permisos específicos.

**Permisos Requeridos**:
- `warehouse.manage` - Para acceder a interfaz de gestión

**Capacidades**:
- ✅ Ver almacenes asignados
- ✅ Gestionar inventario de sus almacenes
- ✅ Ver historial y reportes
- ✅ Configurar ubicaciones y secciones
- ✅ Asignar trabajadores (si tiene permiso)
- ❌ No puede crear nuevos almacenes (solo super-admin)

**Código de Verificación**:
```php
if ($user->hasRole('manager') && $user->hasPermissionTo('warehouse.manage')) {
    // Acceso a gestión de almacenes asignados
}
```

---

### 3. warehouse-worker

**Descripción**: Operaciones diarias de inventario, acceso limitado a funciones operativas.

**Permisos Requeridos** (al menos uno):
- `warehouse.inventory` - Para operaciones de inventario
- `warehouse.transfer` - Para transferencias entre ubicaciones

**Capacidades**:
- ✅ Escanear códigos de barras
- ✅ Validar ubicaciones y secciones
- ✅ Agregar/restar inventario (con permiso `warehouse.inventory`)
- ✅ Transferir productos (con permiso `warehouse.transfer`)
- ✅ Ver historial propio
- ❌ No puede eliminar o modificar estructura
- ❌ No puede generar reportes avanzados
- ❌ No puede asignar permisos

**Código de Verificación**:
```php
if ($user->hasAnyPermission(['warehouse.inventory', 'warehouse.transfer'])) {
    // Acceso a interfaz de worker
}
```

---

## Permisos

Los permisos están definidos en `config/warehouse.php`:

```php
'permissions' => [
    'manage'    => 'warehouse.manage',
    'inventory' => 'warehouse.inventory',
    'transfer'  => 'warehouse.transfer',
    'reports'   => 'warehouse.reports',
],
```

### Tabla de Permisos

| Permiso | Slug | Descripción | Roles Típicos |
|---------|------|-------------|---------------|
| **Gestión** | `warehouse.manage` | Gestión completa de almacenes | super-admin, manager |
| **Inventario** | `warehouse.inventory` | Operaciones de inventario (agregar/restar) | super-admin, manager, warehouse-worker |
| **Transferencia** | `warehouse.transfer` | Transferir productos entre ubicaciones | super-admin, manager, warehouse-worker |
| **Reportes** | `warehouse.reports` | Generar y ver reportes avanzados | super-admin, manager |

### Asignación de Permisos

```php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Crear permisos
Permission::create(['name' => 'warehouse.manage']);
Permission::create(['name' => 'warehouse.inventory']);
Permission::create(['name' => 'warehouse.transfer']);
Permission::create(['name' => 'warehouse.reports']);

// Asignar permisos a rol
$managerRole = Role::findByName('manager');
$managerRole->givePermissionTo(['warehouse.manage', 'warehouse.reports']);

$workerRole = Role::findByName('warehouse-worker');
$workerRole->givePermissionTo(['warehouse.inventory', 'warehouse.transfer']);

// Asignar permiso a usuario específico
$user = User::find(5);
$user->givePermissionTo('warehouse.manage');
```

### Verificación de Permisos

```php
// Verificar un permiso
if ($user->hasPermissionTo('warehouse.manage')) {
    // Usuario tiene permiso
}

// Verificar cualquiera de varios permisos
if ($user->hasAnyPermission(['warehouse.inventory', 'warehouse.transfer'])) {
    // Usuario tiene al menos uno de los permisos
}

// Verificar todos los permisos
if ($user->hasAllPermissions(['warehouse.manage', 'warehouse.reports'])) {
    // Usuario tiene todos los permisos
}

// En Blade
@can('warehouse.manage')
    <a href="/settings/warehouse">Gestionar Almacenes</a>
@endcan
```

---

## Políticas de Autorización

El módulo utiliza **6 políticas (Policy)** para controlar acceso a entidades específicas:

### 1. WarehousePolicy

**Ubicación**: `modules/Warehouse/app/Policies/WarehousePolicy.php`

**Métodos**:

#### viewAny()
```php
public function viewAny(User $user): Response
{
    // super-admin: acceso completo
    if ($user->hasRole('super-admin')) {
        return Response::allow();
    }

    // manager: con permiso warehouse.manage
    if ($user->hasPermissionTo('warehouse.manage')) {
        return Response::allow();
    }

    return Response::deny('No tienes permiso para ver almacenes.');
}
```

#### view()
```php
public function view(User $user, Warehouse $warehouse): Response
{
    if ($user->hasRole('super-admin')) {
        return Response::allow();
    }

    if (!$user->hasPermissionTo('warehouse.manage')) {
        return Response::deny('No tienes permiso para ver almacenes.');
    }

    // Verificar asignación al almacén
    if (!$user->warehouses->contains($warehouse->id)) {
        return Response::deny('No estás asignado a este almacén.');
    }

    return Response::allow();
}
```

#### create()
```php
public function create(User $user): Response
{
    // Solo super-admin puede crear almacenes
    if ($user->hasRole('super-admin')) {
        return Response::allow();
    }

    return Response::deny('Solo super-admin puede crear almacenes.');
}
```

#### update()
```php
public function update(User $user, Warehouse $warehouse): Response
{
    if ($user->hasRole('super-admin')) {
        return Response::allow();
    }

    if (!$user->hasPermissionTo('warehouse.manage')) {
        return Response::deny('No tienes permiso para editar almacenes.');
    }

    if (!$user->warehouses->contains($warehouse->id)) {
        return Response::deny('No estás asignado a este almacén.');
    }

    return Response::allow();
}
```

#### delete()
```php
public function delete(User $user, Warehouse $warehouse): Response
{
    // Solo super-admin puede eliminar
    if ($user->hasRole('super-admin')) {
        return Response::allow();
    }

    return Response::deny('Solo super-admin puede eliminar almacenes.');
}
```

---

### 2. WarehouseFloorPolicy

**Ubicación**: `modules/Warehouse/app/Policies/WarehouseFloorPolicy.php`

**Lógica Similar**:
- Verifica rol super-admin
- Verifica permiso `warehouse.manage`
- Verifica asignación al almacén padre

---

### 3. WarehouseLocationPolicy

**Ubicación**: `modules/Warehouse/app/Policies/WarehouseLocationPolicy.php`

**Métodos Adicionales**:

#### transfer()
```php
public function transfer(User $user, WarehouseLocation $location): Response
{
    if ($user->hasRole('super-admin')) {
        return Response::allow();
    }

    // Verificar permiso de transferencia
    if (!$user->hasPermissionTo('warehouse.transfer')) {
        return Response::deny('No tienes permiso para transferir inventario.');
    }

    // Verificar asignación con capacidad de transferencia
    $assignment = $user->warehouses()
        ->where('warehouse_id', $location->warehouse_id)
        ->first();

    if (!$assignment) {
        return Response::deny('No estás asignado a este almacén.');
    }

    if (!$assignment->pivot->can_transfer) {
        return Response::deny('No tienes capacidad de transferencia en este almacén.');
    }

    return Response::allow();
}
```

---

### 4. WarehouseInventorySlotPolicy

**Ubicación**: `modules/Warehouse/app/Policies/WarehouseInventorySlotPolicy.php`

**Métodos Específicos**:

#### addQuantity()
```php
public function addQuantity(User $user, WarehouseInventorySlot $slot): Response
{
    if ($user->hasRole('super-admin')) {
        return Response::allow();
    }

    if (!$user->hasPermissionTo('warehouse.inventory')) {
        return Response::deny('No tienes permiso para modificar inventario.');
    }

    // Verificar asignación con capacidad de inventario
    $section = $slot->section;
    $location = $section->location;
    $warehouse = $location->warehouse;

    $assignment = $user->warehouses()
        ->where('warehouse_id', $warehouse->id)
        ->first();

    if (!$assignment || !$assignment->pivot->can_inventory) {
        return Response::deny('No tienes capacidad de inventario en este almacén.');
    }

    return Response::allow();
}
```

#### subtractQuantity()
```php
public function subtractQuantity(User $user, WarehouseInventorySlot $slot): Response
{
    // Misma lógica que addQuantity
    return $this->addQuantity($user, $slot);
}
```

#### moveTo()
```php
public function moveTo(User $user, WarehouseInventorySlot $slot): Response
{
    if ($user->hasRole('super-admin')) {
        return Response::allow();
    }

    // Requiere tanto permiso de inventario como de transferencia
    if (!$user->hasAllPermissions(['warehouse.inventory', 'warehouse.transfer'])) {
        return Response::deny('No tienes permisos para mover inventario.');
    }

    $section = $slot->section;
    $location = $section->location;
    $warehouse = $location->warehouse;

    $assignment = $user->warehouses()
        ->where('warehouse_id', $warehouse->id)
        ->first();

    if (!$assignment || !$assignment->pivot->can_transfer || !$assignment->pivot->can_inventory) {
        return Response::deny('No tienes las capacidades necesarias en este almacén.');
    }

    return Response::allow();
}
```

---

### 5. WarehouseInventoryOperationPolicy

**Ubicación**: `modules/Warehouse/app/Policies/WarehouseInventoryOperationPolicy.php`

Controla operaciones masivas de inventario.

---

### 6. WarehouseLocationStylePolicy

**Ubicación**: `modules/Warehouse/app/Policies/WarehouseLocationStylePolicy.php`

Controla gestión de estilos visuales (solo managers y super-admin).

---

## Asignación de Usuarios a Almacenes

### Tabla Pivote: user_warehouse

```sql
CREATE TABLE user_warehouse (
    id BIGINT UNSIGNED PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    is_default BOOLEAN DEFAULT 0,
    can_transfer BOOLEAN DEFAULT 0,
    can_inventory BOOLEAN DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (user_id, warehouse_id)
);
```

### Capacidades Individuales

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `is_default` | boolean | Almacén predeterminado del usuario |
| `can_transfer` | boolean | Puede realizar transferencias en este almacén |
| `can_inventory` | boolean | Puede hacer operaciones de inventario en este almacén |

### Asignar Usuario a Almacén

```php
$user = User::find(5);
$warehouse = Warehouse::findByUid('550e8400-...');

// Asignar con capacidades
$user->warehouses()->attach($warehouse->id, [
    'is_default' => true,
    'can_transfer' => true,
    'can_inventory' => true,
]);

// Actualizar capacidades
$user->warehouses()->updateExistingPivot($warehouse->id, [
    'can_transfer' => false,
]);

// Remover asignación
$user->warehouses()->detach($warehouse->id);
```

### Consultar Asignaciones

```php
// Almacenes del usuario
$warehouses = $user->warehouses;

// Almacén predeterminado
$defaultWarehouse = $user->warehouses()
    ->wherePivot('is_default', true)
    ->first();

// Almacenes donde puede transferir
$transferWarehouses = $user->warehouses()
    ->wherePivot('can_transfer', true)
    ->get();

// Verificar capacidad específica
$canInventory = $user->warehouses()
    ->where('warehouse_id', $warehouse->id)
    ->wherePivot('can_inventory', true)
    ->exists();
```

---

## Middleware

### Middleware de Rutas

#### 1. Rutas de Manager

```php
// routes/web.php
Route::prefix('settings/warehouse')
    ->middleware(['web', 'auth', 'role:super-admin|manager', 'permission:warehouse.manage'])
    ->group(function () {
        Route::get('/', [WarehouseController::class, 'index']);
        Route::get('/create', [WarehouseController::class, 'create']);
        // ...
    });
```

**Middlewares Aplicados**:
- `web` - Sesión web
- `auth` - Usuario autenticado
- `role:super-admin|manager` - Rol requerido
- `permission:warehouse.manage` - Permiso requerido

---

#### 2. Rutas de Worker

```php
// routes/warehouses.php
Route::prefix('warehouse')
    ->middleware(['web', 'auth', 'permission:warehouse.inventory|warehouse.transfer'])
    ->group(function () {
        Route::get('/', [WarehousesController::class, 'index']);
        Route::post('/locations/validate/location', [LocationsController::class, 'validateLocation']);
        // ...
    });
```

**Middlewares Aplicados**:
- `web` - Sesión web
- `auth` - Usuario autenticado
- `permission:warehouse.inventory|warehouse.transfer` - Al menos uno de los permisos

---

#### 3. Rutas de API

```php
// routes/api.php
Route::prefix('warehouse')
    ->middleware(['api', 'auth:sanctum', 'throttle:60,1'])
    ->group(function () {
        Route::get('/{warehouse_uid}/inventory', [ApiController::class, 'inventory']);
        // ...
    });
```

**Middlewares Aplicados**:
- `api` - API (sin sesión)
- `auth:sanctum` - Autenticación mediante token
- `throttle:60,1` - Máximo 60 peticiones por minuto

---

### Middleware Personalizado

#### CheckWarehouseAccess

**Ubicación**: `modules/Warehouse/app/Http/Middleware/CheckWarehouseAccess.php`

```php
<?php

namespace Modules\Warehouse\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Warehouse\Entities\Warehouse;

class CheckWarehouseAccess
{
    public function handle(Request $request, Closure $next)
    {
        $warehouseUid = $request->route('warehouse_uid');

        if (!$warehouseUid) {
            return $next($request);
        }

        $warehouse = Warehouse::where('uid', $warehouseUid)->firstOrFail();

        // Verificar autorización
        if (Gate::denies('view', $warehouse)) {
            abort(403, 'No tienes acceso a este almacén.');
        }

        // Agregar warehouse al request
        $request->merge(['warehouse' => $warehouse]);

        return $next($request);
    }
}
```

**Uso**:
```php
Route::get('/warehouse/{warehouse_uid}', [WarehouseController::class, 'view'])
    ->middleware(CheckWarehouseAccess::class);
```

---

## Ejemplos de Uso

### Ejemplo 1: Verificar Acceso en Controlador

```php
<?php

namespace Modules\Warehouse\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Warehouse\Entities\Warehouse;

class WarehouseController extends Controller
{
    public function view(Request $request, string $warehouseUid)
    {
        $warehouse = Warehouse::where('uid', $warehouseUid)->firstOrFail();

        // Verificar autorización usando Policy
        $this->authorize('view', $warehouse);

        // Si llega aquí, el usuario tiene acceso
        return view('warehouse::settings.warehouses.view', [
            'warehouse' => $warehouse,
        ]);
    }

    public function update(Request $request, string $warehouseUid)
    {
        $warehouse = Warehouse::where('uid', $warehouseUid)->firstOrFail();

        // Verificar autorización
        $this->authorize('update', $warehouse);

        // Validar y actualizar
        $validated = $request->validate([
            'name' => 'required|max:100',
            'code' => 'required|max:50|unique:warehouses,code,' . $warehouse->id,
        ]);

        $warehouse->update($validated);

        return redirect()->back()->with('success', 'Almacén actualizado exitosamente');
    }

    public function destroy(string $warehouseUid)
    {
        $warehouse = Warehouse::where('uid', $warehouseUid)->firstOrFail();

        // Solo super-admin puede eliminar
        $this->authorize('delete', $warehouse);

        $warehouse->delete();

        return redirect()->route('warehouse.index')->with('success', 'Almacén eliminado');
    }
}
```

---

### Ejemplo 2: Verificar Acceso en Blade

```blade
{{-- resources/views/warehouse/settings/warehouses/view.blade.php --}}

@extends('layouts.app')

@section('content')
    <h1>{{ $warehouse->name }}</h1>

    <div class="actions">
        {{-- Mostrar solo si puede editar --}}
        @can('update', $warehouse)
            <a href="{{ route('warehouse.edit', $warehouse->uid) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Editar
            </a>
        @endcan

        {{-- Mostrar solo si puede eliminar --}}
        @can('delete', $warehouse)
            <form action="{{ route('warehouse.destroy', $warehouse->uid) }}" method="POST" style="display:inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro?')">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </form>
        @endcan
    </div>

    {{-- Mostrar contenido solo si puede ver --}}
    @can('view', $warehouse)
        <div class="warehouse-details">
            <!-- Detalles del almacén -->
        </div>
    @endcan
@endsection
```

---

### Ejemplo 3: Verificar Permiso en API

```php
<?php

namespace Modules\Warehouse\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\Warehouse\Entities\Warehouse;
use Modules\Warehouse\Entities\WarehouseInventorySlot;

class InventoryController extends Controller
{
    public function addQuantity(Request $request, string $slotUid)
    {
        $slot = WarehouseInventorySlot::where('uid', $slotUid)->firstOrFail();

        // Verificar autorización
        if (!$request->user()->can('addQuantity', $slot)) {
            return response()->json([
                'message' => 'No tienes permiso para agregar inventario',
            ], 403);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
        ]);

        // Agregar cantidad
        $slot->addQuantity($validated['quantity'], $validated['reason']);

        return response()->json([
            'message' => 'Cantidad agregada exitosamente',
            'data' => [
                'new_quantity' => $slot->quantity,
                'slot_uid' => $slot->uid,
            ],
        ], 200);
    }
}
```

---

### Ejemplo 4: Verificar Capacidad de Usuario

```php
<?php

namespace Modules\Warehouse\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Warehouse\Entities\Warehouse;

class TransferController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Obtener almacenes donde puede transferir
        $warehouses = $user->warehouses()
            ->wherePivot('can_transfer', true)
            ->get();

        if ($warehouses->isEmpty()) {
            return redirect()->back()->with('error', 'No tienes capacidad de transferencia en ningún almacén');
        }

        return view('warehouse::transfers.index', [
            'warehouses' => $warehouses,
        ]);
    }

    public function process(Request $request)
    {
        $validated = $request->validate([
            'from_slot_uid' => 'required|exists:warehouse_inventory_slots,uid',
            'to_section_barcode' => 'required',
            'quantity' => 'required|integer|min:1',
        ]);

        $slot = WarehouseInventorySlot::where('uid', $validated['from_slot_uid'])->firstOrFail();
        $warehouse = $slot->section->location->warehouse;

        // Verificar capacidad de transferencia
        $assignment = $request->user()->warehouses()
            ->where('warehouse_id', $warehouse->id)
            ->first();

        if (!$assignment || !$assignment->pivot->can_transfer) {
            return redirect()->back()->with('error', 'No tienes capacidad de transferencia en este almacén');
        }

        // Procesar transferencia
        // ...

        return redirect()->back()->with('success', 'Transferencia completada');
    }
}
```

---

## Registro de Políticas

Las políticas se registran en el `WarehouseServiceProvider`:

```php
<?php

namespace Modules\Warehouse\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Warehouse\Entities\Warehouse;
use Modules\Warehouse\Entities\WarehouseFloor;
use Modules\Warehouse\Entities\WarehouseLocation;
use Modules\Warehouse\Entities\WarehouseInventorySlot;
use Modules\Warehouse\Policies\WarehousePolicy;
use Modules\Warehouse\Policies\WarehouseFloorPolicy;
use Modules\Warehouse\Policies\WarehouseLocationPolicy;
use Modules\Warehouse\Policies\WarehouseInventorySlotPolicy;

class WarehouseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPolicies();
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Warehouse::class, WarehousePolicy::class);
        Gate::policy(WarehouseFloor::class, WarehouseFloorPolicy::class);
        Gate::policy(WarehouseLocation::class, WarehouseLocationPolicy::class);
        Gate::policy(WarehouseInventorySlot::class, WarehouseInventorySlotPolicy::class);
    }
}
```

---

## Testing de Permisos

### Ejemplo de Test

```php
<?php

namespace Tests\Feature\Warehouse;

use Tests\TestCase;
use Modules\Warehouse\Entities\Warehouse;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class WarehouseAuthorizationTest extends TestCase
{
    public function test_super_admin_can_view_any_warehouse()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $warehouse = Warehouse::factory()->create();

        $this->assertTrue($superAdmin->can('view', $warehouse));
    }

    public function test_manager_can_view_assigned_warehouse()
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $manager->givePermissionTo('warehouse.manage');

        $warehouse = Warehouse::factory()->create();
        $manager->warehouses()->attach($warehouse->id);

        $this->assertTrue($manager->can('view', $warehouse));
    }

    public function test_manager_cannot_view_unassigned_warehouse()
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $manager->givePermissionTo('warehouse.manage');

        $warehouse = Warehouse::factory()->create();

        $this->assertFalse($manager->can('view', $warehouse));
    }

    public function test_worker_cannot_delete_warehouse()
    {
        $worker = User::factory()->create();
        $worker->assignRole('warehouse-worker');

        $warehouse = Warehouse::factory()->create();
        $worker->warehouses()->attach($warehouse->id);

        $this->assertFalse($worker->can('delete', $warehouse));
    }

    public function test_user_with_inventory_permission_can_add_quantity()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('warehouse.inventory');

        $warehouse = Warehouse::factory()->create();
        $user->warehouses()->attach($warehouse->id, [
            'can_inventory' => true,
        ]);

        $slot = WarehouseInventorySlot::factory()->create([
            'section_id' => /* sección del warehouse */
        ]);

        $this->assertTrue($user->can('addQuantity', $slot));
    }
}
```

---

## Mejores Prácticas

### 1. Siempre Usar Políticas

❌ **Incorrecto**:
```php
if ($user->id === $warehouse->owner_id) {
    // Lógica manual de autorización
}
```

✅ **Correcto**:
```php
$this->authorize('update', $warehouse);
```

### 2. Verificar Permisos en Controladores

```php
public function index(Request $request)
{
    // Verificar permiso al inicio
    if (!$request->user()->hasPermissionTo('warehouse.manage')) {
        abort(403, 'No tienes permiso para gestionar almacenes');
    }

    // Continuar con lógica
}
```

### 3. Usar Gates en Blade

```blade
@can('warehouse.manage')
    <a href="{{ route('warehouse.settings') }}">Configuración</a>
@endcan
```

### 4. Validar Capacidades en Operaciones Críticas

```php
public function transfer(Request $request)
{
    $warehouse = Warehouse::findByUid($request->warehouse_uid);

    $assignment = $request->user()->warehouses()
        ->where('warehouse_id', $warehouse->id)
        ->first();

    if (!$assignment || !$assignment->pivot->can_transfer) {
        abort(403, 'No tienes capacidad de transferencia en este almacén');
    }

    // Continuar con transferencia
}
```

---

## Referencias

- [Documentación General](warehouse-module.md)
- [Esquema de Base de Datos](../database/warehouse-schema.md)
- [Endpoints](../api/warehouse-endpoints.md)
- [Spatie Permission Docs](https://spatie.be/docs/laravel-permission)
- [Laravel Authorization](https://laravel.com/docs/authorization)

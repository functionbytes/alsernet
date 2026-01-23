# Warehouse Module - Policy Usage Guide

**Version**: 1.0.0
**Date**: 2025-12-29
**Module**: Warehouse

---

## Overview

This guide provides practical examples for using the enhanced Warehouse module policies in controllers, Blade templates, and API resources.

---

## Available Policies

### WarehousePolicy

**Model**: `Modules\Warehouse\Entities\Warehouse`
**Policy**: `Modules\Warehouse\Policies\WarehousePolicy`

#### Standard CRUD Methods

| Method | Permission | Description |
|--------|------------|-------------|
| `viewAny()` | `warehouse.manage` | List all warehouses |
| `view()` | `warehouse.manage` + assignment | View specific warehouse |
| `create()` | `warehouse.manage` | Create new warehouse |
| `update()` | `warehouse.manage` | Update warehouse |
| `delete()` | `warehouse.manage` | Delete warehouse |
| `restore()` | `warehouse.manage` | Restore soft-deleted warehouse |
| `forceDelete()` | `warehouse.manage` | Permanently delete warehouse |

#### Custom Methods

| Method | Permission | Additional Checks | Description |
|--------|------------|-------------------|-------------|
| `canManageUsers()` | `warehouse.manage` | User assigned to warehouse | Manage warehouse users |

---

### WarehouseLocationPolicy

**Model**: `Modules\Warehouse\Entities\WarehouseLocation`
**Policy**: `Modules\Warehouse\Policies\WarehouseLocationPolicy`

#### Standard CRUD Methods

| Method | Permission | Description |
|--------|------------|-------------|
| `viewAny()` | `warehouse.manage` OR `warehouse.inventory` | List all locations |
| `view()` | `warehouse.manage` OR `warehouse.inventory` + assignment | View specific location |
| `create()` | `warehouse.manage` | Create new location |
| `update()` | `warehouse.manage` | Update location |
| `delete()` | `warehouse.manage` | Delete location |
| `restore()` | `warehouse.manage` | Restore soft-deleted location |
| `forceDelete()` | `warehouse.manage` | Permanently delete location |

#### Custom Methods

| Method | Permission | Additional Checks | Description |
|--------|------------|-------------------|-------------|
| `canAddQuantity()` | `warehouse.inventory` | User assigned + `can_inventory` flag | Add inventory to location |
| `canSubtractQuantity()` | `warehouse.inventory` | User assigned + `can_inventory` flag | Remove inventory from location |
| `canTransferFrom()` | `warehouse.inventory` | User assigned + `can_transfer` flag | Transfer from location |

---

## Usage in Controllers

### Example 1: Basic Authorization Check

```php
namespace Modules\Warehouse\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Modules\Warehouse\Entities\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        // Check if user can view any warehouses
        $this->authorize('viewAny', Warehouse::class);

        // Get only warehouses the user is assigned to
        $warehouses = Warehouse::whereHas('users', function ($query) {
            $query->where('user_id', auth()->id());
        })->get();

        return view('warehouse::theme.warehouses.index', compact('warehouses'));
    }

    public function show(Warehouse $warehouse)
    {
        // Check if user can view this specific warehouse
        $this->authorize('view', $warehouse);

        return view('warehouse::theme.warehouses.show', compact('warehouse'));
    }

    public function create()
    {
        // Check if user can create warehouses
        $this->authorize('create', Warehouse::class);

        return view('warehouse::theme.warehouses.create');
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        // Check if user can update this warehouse
        $this->authorize('update', $warehouse);

        $warehouse->update($request->validated());

        return redirect()->route('theme.warehouses.show', $warehouse)
            ->with('success', 'Almacén actualizado correctamente');
    }

    public function destroy(Warehouse $warehouse)
    {
        // Check if user can delete this warehouse
        $this->authorize('delete', $warehouse);

        $warehouse->delete();

        return redirect()->route('theme.warehouses.index')
            ->with('success', 'Almacén eliminado correctamente');
    }
}
```

---

### Example 2: Custom Policy Method

```php
namespace Modules\Warehouse\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Modules\Warehouse\Entities\Warehouse;
use App\Models\User;
use Illuminate\Http\Request;

class WarehouseUserController extends Controller
{
    public function index(Warehouse $warehouse)
    {
        // Check if user can manage warehouse users
        $this->authorize('canManageUsers', $warehouse);

        $users = $warehouse->users()->with('roles', 'permissions')->get();

        return view('warehouse::theme.warehouses.users.index', [
            'warehouse' => $warehouse,
            'users' => $users,
        ]);
    }

    public function attach(Request $request, Warehouse $warehouse)
    {
        // Check if user can manage warehouse users
        $this->authorize('canManageUsers', $warehouse);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'can_inventory' => 'boolean',
            'can_transfer' => 'boolean',
        ]);

        $warehouse->users()->attach($request->user_id, [
            'can_inventory' => $request->boolean('can_inventory'),
            'can_transfer' => $request->boolean('can_transfer'),
        ]);

        return redirect()->route('theme.warehouses.users.index', $warehouse)
            ->with('success', 'Usuario asignado correctamente');
    }

    public function detach(Warehouse $warehouse, User $user)
    {
        // Check if user can manage warehouse users
        $this->authorize('canManageUsers', $warehouse);

        $warehouse->users()->detach($user->id);

        return redirect()->route('theme.warehouses.users.index', $warehouse)
            ->with('success', 'Usuario desasignado correctamente');
    }
}
```

---

### Example 3: Inventory Operations

```php
namespace Modules\Warehouse\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Modules\Warehouse\Entities\WarehouseLocation;
use Modules\Warehouse\Entities\WarehouseInventoryOperation;
use Illuminate\Http\Request;

class WarehouseInventoryController extends Controller
{
    public function addQuantity(Request $request, WarehouseLocation $location)
    {
        // Check if user can add quantity to this location
        $this->authorize('canAddQuantity', $location);

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        // Create inventory operation
        $operation = WarehouseInventoryOperation::create([
            'warehouse_id' => $location->warehouse_id,
            'location_id' => $location->id,
            'product_id' => $request->product_id,
            'operation_type' => 'entry',
            'quantity' => $request->quantity,
            'user_id' => auth()->id(),
            'notes' => $request->notes,
        ]);

        return redirect()->back()
            ->with('success', 'Inventario agregado correctamente');
    }

    public function subtractQuantity(Request $request, WarehouseLocation $location)
    {
        // Check if user can subtract quantity from this location
        $this->authorize('canSubtractQuantity', $location);

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        // Create inventory operation
        $operation = WarehouseInventoryOperation::create([
            'warehouse_id' => $location->warehouse_id,
            'location_id' => $location->id,
            'product_id' => $request->product_id,
            'operation_type' => 'exit',
            'quantity' => $request->quantity,
            'user_id' => auth()->id(),
            'notes' => $request->notes,
        ]);

        return redirect()->back()
            ->with('success', 'Inventario reducido correctamente');
    }

    public function transfer(Request $request, WarehouseLocation $sourceLocation)
    {
        // Check if user can transfer from source location
        $this->authorize('canTransferFrom', $sourceLocation);

        $request->validate([
            'destination_location_id' => 'required|exists:warehouse_locations,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $destinationLocation = WarehouseLocation::findOrFail($request->destination_location_id);

        // Also check if user can add to destination
        $this->authorize('canAddQuantity', $destinationLocation);

        // Create transfer operations (exit from source, entry to destination)
        WarehouseInventoryOperation::create([
            'warehouse_id' => $sourceLocation->warehouse_id,
            'location_id' => $sourceLocation->id,
            'product_id' => $request->product_id,
            'operation_type' => 'transfer_out',
            'quantity' => $request->quantity,
            'user_id' => auth()->id(),
            'notes' => "Transferencia a ubicación {$destinationLocation->code}",
        ]);

        WarehouseInventoryOperation::create([
            'warehouse_id' => $destinationLocation->warehouse_id,
            'location_id' => $destinationLocation->id,
            'product_id' => $request->product_id,
            'operation_type' => 'transfer_in',
            'quantity' => $request->quantity,
            'user_id' => auth()->id(),
            'notes' => "Transferencia desde ubicación {$sourceLocation->code}",
        ]);

        return redirect()->back()
            ->with('success', 'Transferencia realizada correctamente');
    }
}
```

---

## Usage in Blade Templates

### Example 1: Conditional UI Elements

```blade
{{-- resources/views/warehouse/managers/warehouses/show.blade.php --}}

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">{{ $warehouse->name }}</h5>
    </div>
    <div class="card-body">
        <p>{{ $warehouse->description }}</p>

        <div class="mt-3">
            @can('update', $warehouse)
                <a href="{{ route('managers.warehouses.edit', $warehouse) }}" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>Editar
                </a>
            @endcan

            @can('canManageUsers', $warehouse)
                <a href="{{ route('managers.warehouses.users.index', $warehouse) }}" class="btn btn-success">
                    <i class="fas fa-users me-2"></i>Gestionar Usuarios
                </a>
            @endcan

            @can('delete', $warehouse)
                <form action="{{ route('managers.warehouses.destroy', $warehouse) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" onclick="return confirm('¿Está seguro?')">
                        <i class="fas fa-trash me-2"></i>Eliminar
                    </button>
                </form>
            @endcan
        </div>
    </div>
</div>
```

---

### Example 2: Inventory Operations

```blade
{{-- resources/views/warehouse/managers/locations/show.blade.php --}}

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Ubicación: {{ $location->code }}</h5>
    </div>
    <div class="card-body">
        <p>Capacidad: {{ $location->capacity }}</p>
        <p>Ocupado: {{ $location->current_quantity }}</p>

        <div class="btn-group mt-3" role="group">
            @can('canAddQuantity', $location)
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addQuantityModal">
                    <i class="fas fa-plus me-2"></i>Añadir Inventario
                </button>
            @endcan

            @can('canSubtractQuantity', $location)
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#subtractQuantityModal">
                    <i class="fas fa-minus me-2"></i>Quitar Inventario
                </button>
            @endcan

            @can('canTransferFrom', $location)
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transferModal">
                    <i class="fas fa-exchange-alt me-2"></i>Transferir
                </button>
            @endcan
        </div>
    </div>
</div>

{{-- Add Quantity Modal --}}
@can('canAddQuantity', $location)
    <div class="modal fade" id="addQuantityModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('managers.warehouses.inventory.add', $location) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Añadir Inventario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="product_id" class="form-label">Producto</label>
                            <select name="product_id" id="product_id" class="form-select" required>
                                <option value="">Seleccionar producto...</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Cantidad</label>
                            <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notas (opcional)</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Añadir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
```

---

### Example 3: Warehouse User List

```blade
{{-- resources/views/warehouse/managers/warehouses/users/index.blade.php --}}

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Usuarios del Almacén: {{ $warehouse->name }}</h5>
    </div>
    <div class="card-body">
        @can('canManageUsers', $warehouse)
            <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-user-plus me-2"></i>Asignar Usuario
            </button>
        @endcan

        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Puede Inventariar</th>
                    <th>Puede Transferir</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->pivot->can_inventory)
                                <i class="fas fa-check text-success"></i>
                            @else
                                <i class="fas fa-times text-danger"></i>
                            @endif
                        </td>
                        <td>
                            @if($user->pivot->can_transfer)
                                <i class="fas fa-check text-success"></i>
                            @else
                                <i class="fas fa-times text-danger"></i>
                            @endif
                        </td>
                        <td>
                            @can('canManageUsers', $warehouse)
                                <form action="{{ route('managers.warehouses.users.detach', [$warehouse, $user]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Desasignar usuario?')">
                                        <i class="fas fa-user-times"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
```

---

## Usage in API Resources

### Example 1: Warehouse Resource

```php
namespace Modules\Warehouse\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'address' => $this->address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Include authorization flags
            'can' => [
                'view' => $request->user()?->can('view', $this) ?? false,
                'update' => $request->user()?->can('update', $this) ?? false,
                'delete' => $request->user()?->can('delete', $this) ?? false,
                'manage_users' => $request->user()?->can('canManageUsers', $this) ?? false,
            ],

            // Include relationships when loaded
            'floors' => WarehouseFloorResource::collection($this->whenLoaded('floors')),
            'users' => UserResource::collection($this->whenLoaded('users')),
        ];
    }
}
```

---

### Example 2: Location Resource with Inventory Permissions

```php
namespace Modules\Warehouse\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'floor_id' => $this->floor_id,
            'code' => $this->code,
            'capacity' => $this->capacity,
            'current_quantity' => $this->current_quantity,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Include authorization flags for inventory operations
            'can' => [
                'view' => $request->user()?->can('view', $this) ?? false,
                'update' => $request->user()?->can('update', $this) ?? false,
                'delete' => $request->user()?->can('delete', $this) ?? false,
                'add_quantity' => $request->user()?->can('canAddQuantity', $this) ?? false,
                'subtract_quantity' => $request->user()?->can('canSubtractQuantity', $this) ?? false,
                'transfer_from' => $request->user()?->can('canTransferFrom', $this) ?? false,
            ],

            // Include relationships when loaded
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'floor' => new WarehouseFloorResource($this->whenLoaded('floor')),
            'inventory_slots' => WarehouseInventorySlotResource::collection($this->whenLoaded('inventorySlots')),
        ];
    }
}
```

---

## Middleware Integration

### Example: Route Middleware

```php
// modules/Warehouse/routes/theme.php

Route::middleware(['auth', 'role:super-admin|warehouse-manager'])->prefix('warehouses')->group(function () {

    // Warehouse routes
    Route::get('/', [WarehouseController::class, 'index'])->name('theme.warehouses.index');
    Route::get('/create', [WarehouseController::class, 'create'])->name('theme.warehouses.create');
    Route::post('/', [WarehouseController::class, 'store'])->name('theme.warehouses.store');

    Route::get('/{warehouse}', [WarehouseController::class, 'show'])
        ->name('theme.warehouses.show')
        ->can('view', 'warehouse'); // Policy applied here

    Route::get('/{warehouse}/edit', [WarehouseController::class, 'edit'])
        ->name('theme.warehouses.edit')
        ->can('update', 'warehouse');

    Route::put('/{warehouse}', [WarehouseController::class, 'update'])
        ->name('theme.warehouses.update')
        ->can('update', 'warehouse');

    Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])
        ->name('theme.warehouses.destroy')
        ->can('delete', 'warehouse');

    // Warehouse user management routes
    Route::get('/{warehouse}/users', [WarehouseUserController::class, 'index'])
        ->name('theme.warehouses.users.index')
        ->can('canManageUsers', 'warehouse'); // Custom policy method

    Route::post('/{warehouse}/users', [WarehouseUserController::class, 'attach'])
        ->name('theme.warehouses.users.attach')
        ->can('canManageUsers', 'warehouse');

    Route::delete('/{warehouse}/users/{user}', [WarehouseUserController::class, 'detach'])
        ->name('theme.warehouses.users.detach')
        ->can('canManageUsers', 'warehouse');

    // Inventory operation routes
    Route::post('/locations/{location}/inventory/add', [WarehouseInventoryController::class, 'addQuantity'])
        ->name('theme.warehouses.inventory.add')
        ->can('canAddQuantity', 'location');

    Route::post('/locations/{location}/inventory/subtract', [WarehouseInventoryController::class, 'subtractQuantity'])
        ->name('theme.warehouses.inventory.subtract')
        ->can('canSubtractQuantity', 'location');

    Route::post('/locations/{location}/inventory/transfer', [WarehouseInventoryController::class, 'transfer'])
        ->name('theme.warehouses.inventory.transfer')
        ->can('canTransferFrom', 'location');
});
```

---

## Testing Policies

### Example: Policy Unit Test

```php
// tests/Unit/Policies/WarehousePolicyTest.php

namespace Tests\Unit\Policies;

use App\Models\User;
use Modules\Warehouse\Entities\Warehouse;
use Modules\Warehouse\Policies\WarehousePolicy;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

class WarehousePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected WarehousePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new WarehousePolicy;

        // Create permissions
        Permission::create(['name' => 'warehouse.manage']);
    }

    public function test_super_admin_can_do_everything()
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $warehouse = Warehouse::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->view($user, $warehouse)->allowed());
        $this->assertTrue($this->policy->create($user));
        $this->assertTrue($this->policy->update($user, $warehouse));
        $this->assertTrue($this->policy->delete($user, $warehouse));
        $this->assertTrue($this->policy->canManageUsers($user, $warehouse)->allowed());
    }

    public function test_user_with_permission_can_view_assigned_warehouse()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('warehouse.manage');

        $warehouse = Warehouse::factory()->create();
        $warehouse->users()->attach($user->id);

        $this->assertTrue($this->policy->view($user, $warehouse)->allowed());
    }

    public function test_user_without_assignment_cannot_view_warehouse()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('warehouse.manage');

        $warehouse = Warehouse::factory()->create();
        // Not attached to warehouse

        $this->assertFalse($this->policy->view($user, $warehouse)->allowed());
        $this->assertStringContainsString(
            'No estás asignado a este almacén',
            $this->policy->view($user, $warehouse)->message()
        );
    }

    public function test_user_can_manage_users_when_assigned()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('warehouse.manage');

        $warehouse = Warehouse::factory()->create();
        $warehouse->users()->attach($user->id);

        $this->assertTrue($this->policy->canManageUsers($user, $warehouse)->allowed());
    }

    public function test_user_cannot_manage_users_without_assignment()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('warehouse.manage');

        $warehouse = Warehouse::factory()->create();
        // Not attached

        $this->assertFalse($this->policy->canManageUsers($user, $warehouse)->allowed());
    }
}
```

---

### Example: Location Policy Test

```php
// tests/Unit/Policies/WarehouseLocationPolicyTest.php

namespace Tests\Unit\Policies;

use App\Models\User;
use Modules\Warehouse\Entities\Warehouse;
use Modules\Warehouse\Entities\WarehouseLocation;
use Modules\Warehouse\Policies\WarehouseLocationPolicy;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

class WarehouseLocationPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected WarehouseLocationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new WarehouseLocationPolicy;

        // Create permissions
        Permission::create(['name' => 'warehouse.manage']);
        Permission::create(['name' => 'warehouse.inventory']);
    }

    public function test_user_can_add_quantity_with_inventory_permission_and_flag()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('warehouse.inventory');

        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);

        $warehouse->users()->attach($user->id, ['can_inventory' => true]);

        $this->assertTrue($this->policy->canAddQuantity($user, $location)->allowed());
    }

    public function test_user_cannot_add_quantity_without_inventory_flag()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('warehouse.inventory');

        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);

        $warehouse->users()->attach($user->id, ['can_inventory' => false]);

        $this->assertFalse($this->policy->canAddQuantity($user, $location)->allowed());
        $this->assertStringContainsString(
            'No tienes permiso de inventario',
            $this->policy->canAddQuantity($user, $location)->message()
        );
    }

    public function test_user_can_transfer_with_transfer_flag()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('warehouse.inventory');

        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);

        $warehouse->users()->attach($user->id, ['can_transfer' => true]);

        $this->assertTrue($this->policy->canTransferFrom($user, $location)->allowed());
    }

    public function test_user_cannot_transfer_without_transfer_flag()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('warehouse.inventory');

        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);

        $warehouse->users()->attach($user->id, ['can_transfer' => false]);

        $this->assertFalse($this->policy->canTransferFrom($user, $location)->allowed());
    }
}
```

---

## Common Error Messages

All policies return Spanish error messages for better UX:

| Policy Method | Error Message |
|---------------|---------------|
| `view()` (Warehouse) | "No tienes permiso para ver almacenes." |
| `view()` (Warehouse) | "No estás asignado a este almacén." |
| `canManageUsers()` | "No tienes permiso para gestionar usuarios de almacenes." |
| `canManageUsers()` | "No estás asignado a este almacén." |
| `view()` (Location) | "No tienes permiso para ver ubicaciones de almacén." |
| `view()` (Location) | "No estás asignado al almacén de esta ubicación." |
| `canAddQuantity()` | "No tienes permiso para realizar operaciones de inventario." |
| `canAddQuantity()` | "No estás asignado al almacén de esta ubicación." |
| `canAddQuantity()` | "No tienes permiso de inventario para este almacén." |
| `canSubtractQuantity()` | Same as `canAddQuantity()` |
| `canTransferFrom()` | "No tienes permiso para realizar transferencias." |
| `canTransferFrom()` | "No estás asignado al almacén de esta ubicación." |
| `canTransferFrom()` | "No tienes permiso de transferencia para este almacén." |

---

## Best Practices

### 1. Always Authorize Before Actions

```php
// ✅ GOOD - Always authorize first
public function update(Request $request, Warehouse $warehouse)
{
    $this->authorize('update', $warehouse);
    $warehouse->update($request->validated());
}

// ❌ BAD - Missing authorization
public function update(Request $request, Warehouse $warehouse)
{
    $warehouse->update($request->validated());
}
```

---

### 2. Use Route Model Binding with Policies

```php
// ✅ GOOD - Combine route model binding with policies
Route::get('/warehouses/{warehouse}', [WarehouseController::class, 'show'])
    ->can('view', 'warehouse');

// ❌ BAD - Manual authorization in controller
Route::get('/warehouses/{id}', [WarehouseController::class, 'show']);
```

---

### 3. Check Policies in Blade Templates

```blade
{{-- ✅ GOOD - Only show to authorized users --}}
@can('update', $warehouse)
    <button>Edit</button>
@endcan

{{-- ❌ BAD - Always showing, no authorization --}}
<button>Edit</button>
```

---

### 4. Include Authorization Flags in API Responses

```php
// ✅ GOOD - Include 'can' flags
return [
    'id' => $this->id,
    'name' => $this->name,
    'can' => [
        'update' => $request->user()->can('update', $this),
        'delete' => $request->user()->can('delete', $this),
    ],
];

// ❌ BAD - Frontend has to guess permissions
return [
    'id' => $this->id,
    'name' => $this->name,
];
```

---

### 5. Test All Policy Methods

```php
// ✅ GOOD - Test all scenarios
public function test_authorized_user_can_manage_users() { ... }
public function test_unauthorized_user_cannot_manage_users() { ... }
public function test_unassigned_user_cannot_manage_users() { ... }

// ❌ BAD - Only testing happy path
public function test_user_can_manage_users() { ... }
```

---

## Summary

### Key Points

1. **Always use policies** for authorization checks
2. **Check permissions** in controllers, routes, and Blade templates
3. **Include authorization flags** in API responses
4. **Test all policy methods** with unit tests
5. **Use descriptive error messages** in Spanish
6. **Leverage route model binding** with policy middleware
7. **Check pivot table flags** for fine-grained control

### Quick Reference

- ✅ Controllers: `$this->authorize('method', $model)`
- ✅ Routes: `->can('method', 'model')`
- ✅ Blade: `@can('method', $model)`
- ✅ API Resources: `$request->user()->can('method', $this)`
- ✅ Tests: `$this->assertTrue($policy->method($user, $model)->allowed())`

---

**End of Policy Usage Guide**

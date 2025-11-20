<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Warehouse\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserWarehouseAssignmentController extends Controller
{
    /**
     * Mostrar lista de usuarios con asignación de warehouses
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string',
            'role' => 'nullable|string|in:inventaries',
        ]);

        $search = $request->input('search');
        $role = $request->input('role', 'inventaries');

        // Obtener usuarios con rol inventories
        $users = User::role($role)
            ->with('warehouses')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('firstname', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->paginate(15);

        $warehouses = Warehouse::available()->get();

        return view('admin.users.warehouse-assignment', [
            'users' => $users,
            'warehouses' => $warehouses,
            'search' => $search,
            'role' => $role,
        ]);
    }

    /**
     * Mostrar formulario para asignar warehouses a un usuario
     */
    public function edit($userId)
    {
        $user = User::with('warehouses')->findOrFail($userId);

        // Verificar que el usuario tenga rol inventaries
        if (!$user->hasRole('inventaries')) {
            abort(403, 'Solo usuarios con rol inventaries pueden tener asignaciones de almacén');
        }

        $warehouses = Warehouse::available()->with(['users' => function ($query) use ($userId) {
            $query->where('user_id', $userId);
        }])->get();

        // Agrupar asignados y no asignados
        $assignedWarehouses = $user->warehouses;
        $unassignedWarehouses = $warehouses->whereNotIn('id', $assignedWarehouses->pluck('id'));

        return view('admin.users.warehouse-assignment-edit', [
            'user' => $user,
            'assignedWarehouses' => $assignedWarehouses,
            'unassignedWarehouses' => $unassignedWarehouses,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Actualizar asignaciones de warehouses
     */
    public function update(Request $request, $userId)
    {
        $request->validate([
            'warehouses' => 'nullable|array',
            'warehouses.*.id' => 'integer|exists:warehouses,id',
            'warehouses.*.is_default' => 'boolean',
            'warehouses.*.can_inventory' => 'boolean',
            'warehouses.*.can_transfer' => 'boolean',
        ]);

        $user = User::findOrFail($userId);

        // Verificar que sea usuario con rol inventaries
        if (!$user->hasRole('inventaries')) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no tiene rol de inventario',
            ], 403);
        }

        try {
            $warehouseData = [];
            $defaultSet = false;

            // Procesar datos de warehouses
            if ($request->has('warehouses') && is_array($request->warehouses)) {
                foreach ($request->warehouses as $warehouse) {
                    $warehouseId = $warehouse['id'] ?? null;

                    if (!$warehouseId) {
                        continue;
                    }

                    $isDefault = $warehouse['is_default'] ?? false;
                    $canInventory = $warehouse['can_inventory'] ?? true;
                    $canTransfer = $warehouse['can_transfer'] ?? true;

                    // Solo un almacén puede ser predeterminado
                    if ($isDefault && $defaultSet) {
                        $isDefault = false;
                    }

                    if ($isDefault) {
                        $defaultSet = true;
                    }

                    $warehouseData[$warehouseId] = [
                        'is_default' => $isDefault,
                        'can_inventory' => $canInventory,
                        'can_transfer' => $canTransfer,
                    ];
                }
            }

            // Sincronizar warehouses (elimina los que no estén incluidos)
            $user->warehouses()->sync($warehouseData);

            Log::channel('admin')->info('Asignación de almacenes actualizada', [
                'user_id' => $userId,
                'user_email' => $user->email,
                'warehouses_count' => count($warehouseData),
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Almacenes asignados correctamente',
                'assigned_count' => count($warehouseData),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al asignar almacenes a usuario', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al asignar almacenes: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Asignar un único almacén
     */
    public function assign(Request $request, $userId)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'is_default' => 'boolean',
            'can_inventory' => 'boolean',
            'can_transfer' => 'boolean',
        ]);

        $user = User::findOrFail($userId);

        if (!$user->hasRole('inventaries')) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no válido para esta operación',
            ], 403);
        }

        try {
            $isDefault = $request->input('is_default', false);
            $canInventory = $request->input('can_inventory', true);
            $canTransfer = $request->input('can_transfer', true);

            // Si es predeterminado, quitar estado de otros
            if ($isDefault) {
                $user->warehouses()->update(['is_default' => false]);
            }

            // Asignar o actualizar
            $user->warehouses()->syncWithoutDetaching([
                $request->warehouse_id => [
                    'is_default' => $isDefault,
                    'can_inventory' => $canInventory,
                    'can_transfer' => $canTransfer,
                ]
            ]);

            Log::channel('admin')->info('Almacén asignado a usuario', [
                'user_id' => $userId,
                'warehouse_id' => $request->warehouse_id,
                'is_default' => $isDefault,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Almacén asignado correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al asignar almacén', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Desasignar un almacén
     */
    public function unassign(Request $request, $userId)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $user = User::findOrFail($userId);

        if (!$user->hasRole('inventaries')) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no válido',
            ], 403);
        }

        try {
            $user->warehouses()->detach($request->warehouse_id);

            Log::channel('admin')->info('Almacén desasignado de usuario', [
                'user_id' => $userId,
                'warehouse_id' => $request->warehouse_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Almacén desasignado correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al desasignar almacén', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener almacenes asignados a un usuario (API)
     */
    public function getUserWarehouses($userId)
    {
        $user = User::with('warehouses')->findOrFail($userId);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
            ],
            'warehouses' => $user->warehouses->map(function ($warehouse) {
                return [
                    'id' => $warehouse->id,
                    'uid' => $warehouse->uid,
                    'code' => $warehouse->code,
                    'name' => $warehouse->name,
                    'is_default' => $warehouse->pivot->is_default,
                    'can_inventory' => $warehouse->pivot->can_inventory,
                    'can_transfer' => $warehouse->pivot->can_transfer,
                ];
            }),
        ]);
    }

    /**
     * Obtener usuarios asignados a un almacén
     */
    public function getWarehouseUsers($warehouseId)
    {
        $warehouse = Warehouse::with('users')->findOrFail($warehouseId);

        $users = $warehouse->users()
            ->where(function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->where('name', 'inventaries');
                });
            })
            ->get();

        return response()->json([
            'success' => true,
            'warehouse' => [
                'id' => $warehouse->id,
                'code' => $warehouse->code,
                'name' => $warehouse->name,
            ],
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'is_default' => $user->pivot->is_default,
                    'can_inventory' => $user->pivot->can_inventory,
                    'can_transfer' => $user->pivot->can_transfer,
                ];
            }),
        ]);
    }
}

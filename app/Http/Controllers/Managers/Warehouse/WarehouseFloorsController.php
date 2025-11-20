<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseFloor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WarehouseFloorsController extends Controller
{
    /**
     * Display a listing of floors for a specific warehouse
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors
     */
    public function index($warehouse_uid = null)
    {
        // Si se proporciona warehouse_uid, filtrar pisos de ese warehouse
        $query = WarehouseFloor::ordered();
        $warehouse = null;

        if ($warehouse_uid) {
            $warehouse = Warehouse::uid($warehouse_uid);
            if ($warehouse) {
                $query = $query->byWarehouse($warehouse->id);
            }
        }

        $floors = $query->paginate(15);

        return view('managers.views.warehouse.floors.index', [
            'floors' => $floors,
            'warehouse_uid' => $warehouse_uid,
            'warehouse' => $warehouse,
        ]);
    }

    /**
     * Show the form for creating a new floor
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/create
     */
    public function create($warehouse_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid);
        if (!$warehouse) {
            abort(404, 'Almacén no encontrado');
        }

        return view('managers.views.warehouse.floors.create')->with([
            'warehouse' => $warehouse,
            'warehouse_uid' => $warehouse_uid,
        ]);
    }

    /**
     * Store a newly created floor in storage
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/store
     */
    public function store(Request $request)
    {
        $warehouse = Warehouse::uid($request->warehouse_uid);
        if (!$warehouse) {
            abort(404, 'Almacén no encontrado');
        }

        $validated = $request->validate([
            'warehouse_uid' => 'required|exists:warehouses,uid',
            'code' => 'required|string|max:50|unique:warehouse_floors,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'level' => 'nullable|integer|min:1',
            'available' => 'boolean',
        ]);

        $validated['uid'] = Str::uuid();
        $validated['available'] = $validated['available'] ?? true;
        $validated['level'] = $validated['level'] ?? 1;
        $validated['warehouse_id'] = $warehouse->id;

        $floor = WarehouseFloor::create($validated);

        // Registrar en activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($floor)
            ->event('created')
            ->log('Piso creado: ' . $floor->name);

        return redirect()->route('manager.warehouse.floors', ['warehouse_uid' => $warehouse->uid])->with('success', 'Piso creado exitosamente');
    }

    /**
     * Display the specified floor
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}
     */
    public function view($warehouse_uid, $floor_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid);
        if (!$warehouse) {
            abort(404, 'Almacén no encontrado');
        }

        $floor = WarehouseFloor::where('uid', $floor_uid)
            ->where('warehouse_id', $warehouse->id)
            ->first();
        if (!$floor) {
            abort(404, 'Piso no encontrado');
        }

        return view('managers.views.warehouse.floors.view', [
            'warehouse' => $warehouse,
            'floor' => $floor,
        ]);
    }

    /**
     * Show the form for editing the specified floor
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/edit
     */
    public function edit($warehouse_uid, $floor_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid);
        if (!$warehouse) {
            abort(404, 'Almacén no encontrado');
        }

        $floor = WarehouseFloor::where('uid', $floor_uid)
            ->where('warehouse_id', $warehouse->id)
            ->first();
        if (!$floor) {
            abort(404, 'Piso no encontrado');
        }

        return view('managers.views.warehouse.floors.edit', [
            'warehouse' => $warehouse,
            'floor' => $floor,
        ]);
    }

    /**
     * Update the specified floor in storage
     * Ruta: POST /manager/warehouse/warehouses/{warehouse_uid}/floors/update
     */
    public function update(Request $request)
    {
        $warehouse = Warehouse::uid($request->warehouse_uid);

        if (!$warehouse) {
            abort(404, 'Almacén no encontrado');
        }

        $floor = WarehouseFloor::where('uid', $request->uid)
            ->where('warehouse_id', $warehouse->id)
            ->first();
        if (!$floor) {
            abort(404, 'Piso no encontrado');
        }

        $validated = $request->validate([
            'warehouse_uid' => 'required|exists:warehouses,uid',
            'code' => 'required|string|max:50|unique:warehouse_floors,code,' . $floor->id,
            'uid' => 'required|exists:warehouse_floors,uid',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'level' => 'nullable|integer|min:1',
            'available' => 'boolean',
        ]);

        $oldData = $floor->only(['name', 'description', 'level', 'available']);

        $floor->update($validated);

        // Registrar en activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($floor)
            ->event('updated')
            ->withProperties(['old' => $oldData, 'attributes' => $floor->getChanges()])
            ->log('Piso actualizado: ' . $floor->name);

        return redirect()->route('manager.warehouse.floors', ['warehouse_uid' => $warehouse->uid])->with('success', 'Piso actualizado exitosamente');
    }

    /**
     * Remove the specified floor from storage
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/destroy
     * Jerarquía: Floor -> Locations -> Inventory Slots
     */
    public function destroy($warehouse_uid, $floor_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid);
        if (!$warehouse) {
            abort(404, 'Almacén no encontrado');
        }

        // Buscar floor por uid y validar que pertenece a este warehouse
        $floor = WarehouseFloor::uid($floor_uid);
        if (!$floor || $floor->warehouse_id !== $warehouse->id) {
            abort(404, 'Piso no encontrado');
        }

        // Validar que no hay inventory slots usando la relación
        $totalSlots = $floor->locations()
            ->withCount('slots')
            ->get()
            ->sum('slots_count');

        if ($totalSlots > 0) {
            return redirect()->route('manager.warehouse.floors', ['warehouse_uid' => $warehouse->uid])
                ->with('error', 'No se puede eliminar un piso que contiene espacios de inventario. Primero debe vaciar o eliminar todos los espacios.');
        }

        // Registrar en activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($floor)
            ->event('deleted')
            ->log('Piso eliminado: ' . $floor->name);

        // Eliminar cascada: locations y sus slots
        $floor->locations()->delete();
        $floor->delete();

        return redirect()->route('manager.warehouse.floors', ['warehouse_uid' => $warehouse->uid])->with('success', 'Piso eliminado exitosamente');
    }
}

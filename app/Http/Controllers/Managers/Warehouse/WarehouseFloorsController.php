<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\WarehouseFloor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FloorsController extends Controller
{
    /**
     * Display a listing of floors
     */
    public function index()
    {
        $floors = WarehouseFloor::ordered()->paginate(15);

        return view('managers.views.warehouse.floors.index', [
            'floors' => $floors,
        ]);
    }

    /**
     * Show the form for creating a new floor
     */
    public function create()
    {
        return view('managers.views.warehouse.floors.create')->with([
        ]);

    }

    /**
     * Store a newly created floor in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:warehouse_floors,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'available' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        $validated['uid'] = Str::uuid();
        $validated['available'] = $validated['available'] ?? true;

        $floor = WarehouseFloor::create($validated);

        // Registrar en activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($floor)
            ->event('created')
            ->log('Piso creado: ' . $floor->name);

        return redirect()->route('manager.warehouse.floors')->with('success', 'Piso creado exitosamente');
    }

    /**
     * Display the specified floor
     */
    public function view($uid)
    {
        $floor = WarehouseFloor::where('uid', $uid)->firstOrFail();

        return view('managers.views.warehouse.floors.view', [
            'floor' => $floor,
        ]);
    }

    /**
     * Show the form for editing the specified floor
     */
    public function edit($uid)
    {
        $floor = WarehouseFloor::where('uid', $uid)->firstOrFail();

        return view('managers.views.warehouse.floors.edit', [
            'floor' => $floor,
        ]);
    }

    /**
     * Update the specified floor in storage
     */
    public function update(Request $request)
    {
        $floor = WarehouseFloor::where('uid', $request->uid)->firstOrFail();

        $validated = $request->validate([
            'uid' => 'required|exists:warehouse_floors,uid',
            'code' => 'required|string|max:50|unique:warehouse_floors,code,' . $floor->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'available' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        $oldData = $floor->only(['code', 'name', 'description', 'available', 'order']);

        $floor->update($validated);

        // Registrar en activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($floor)
            ->event('updated')
            ->withProperties(['old' => $oldData, 'attributes' => $floor->getChanges()])
            ->log('Piso actualizado: ' . $floor->name);

        return redirect()->route('manager.warehouse.floors')->with('success', 'Piso actualizado exitosamente');
    }

    /**
     * Remove the specified floor from storage
     */
    public function destroy($uid)
    {
        $floor = WarehouseFloor::where('uid', $uid)->firstOrFail();

        // Check if floor has associated locations
        if ($floor->locations()->count() > 0) {
            return redirect()->route('manager.warehouse.floors')->with('error', 'No se puede eliminar un piso que contiene ubicaciones');
        }

        // Registrar en activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($floor)
            ->event('deleted')
            ->log('Piso eliminado: ' . $floor->name);

        $floor->delete();

        return redirect()->route('manager.warehouse.floors')->with('success', 'Piso eliminado exitosamente');
    }
}

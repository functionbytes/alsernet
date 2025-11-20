<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseLocationStyle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WarehouseLocationStylesController extends Controller
{
    /**
     * Display a listing of stand styles for a specific warehouse
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/styles
     */
    public function index($warehouse_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $styles = WarehouseLocationStyle::where('warehouse_id', $warehouse->id)
            ->available()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('managers.views.warehouse.styles.index', [
            'warehouse' => $warehouse,
            'styles' => $styles,
        ]);
    }

    /**
     * Show the form for creating a new stand style
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/styles/create
     */
    public function create($warehouse_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $faces = ['left', 'right', 'front', 'back'];
        $types = ['ROW' => 'Pasillo Lineal', 'ISLAND' => 'Isla', 'WALL' => 'Pared'];

        return view('managers.views.warehouse.styles.create', [
            'warehouse' => $warehouse,
            'faces' => $faces,
            'types' => $types,
        ]);
    }

    /**
     * Store a newly created stand style in storage
     * Ruta: POST /manager/warehouse/warehouses/{warehouse_uid}/styles/store
     */
    public function store(Request $request)
    {
        $warehouse = Warehouse::uid($request->warehouse_uid)->firstOrFail();

        $validated = $request->validate([
            'warehouse_uid' => 'required|exists:warehouses,uid',
            'code' => 'required|string|max:50|unique:warehouse_location_styles,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'faces' => 'required|array|min:1',
            'faces.*' => 'in:left,right,front,back',
            'default_levels' => 'required|integer|min:1|max:20',
            'default_sections' => 'required|integer|min:1|max:30',
            'available' => 'boolean',
        ]);

        $validated['uid'] = Str::uuid();
        $validated['warehouse_id'] = $warehouse->id;
        $validated['available'] = $validated['available'] ?? true;

        $style = WarehouseLocationStyle::create($validated);

        // Registrar en activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($style)
            ->event('created')
            ->log('Estilo de ubicación creado: ' . $style->name);

        return redirect()->route('manager.warehouse.styles', ['warehouse_uid' => $warehouse->uid])->with('success', 'Estilo de ubicación creado exitosamente');
    }

    /**
     * Display the specified stand style
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/styles/{style_uid}
     */
    public function view($warehouse_uid, $style_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $style = WarehouseLocationStyle::where('uid', $style_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();

        $summary = [
            'total_locations' => $style->locations()->count(),
            'active_locations' => $style->locations()->where('available', true)->count(),
        ];

        return view('managers.views.warehouse.styles.view', [
            'warehouse' => $warehouse,
            'style' => $style,
            'summary' => $summary,
        ]);
    }

    /**
     * Show the form for editing the specified stand style
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/styles/{style_uid}/edit
     */
    public function edit($warehouse_uid, $style_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $style = WarehouseLocationStyle::where('uid', $style_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();

        $faces = ['left', 'right', 'front', 'back'];
        $types = ['ROW' => 'Pasillo Lineal', 'ISLAND' => 'Isla', 'WALL' => 'Pared'];

        return view('managers.views.warehouse.styles.edit', [
            'warehouse' => $warehouse,
            'style' => $style,
            'faces' => $faces,
            'types' => $types,
        ]);
    }

    /**
     * Update the specified stand style in storage
     * Ruta: POST /manager/warehouse/warehouses/{warehouse_uid}/styles/update
     */
    public function update(Request $request)
    {
        $warehouse = Warehouse::uid($request->warehouse_uid)->firstOrFail();
        $style = WarehouseLocationStyle::where('uid', $request->uid)->where('warehouse_id', $warehouse->id)->firstOrFail();

        $validated = $request->validate([
            'warehouse_uid' => 'required|exists:warehouses,uid',
            'uid' => 'required|exists:warehouse_location_styles,uid',
            'code' => 'required|string|max:50|unique:warehouse_location_styles,code,' . $style->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'faces' => 'required|array|min:1',
            'faces.*' => 'in:left,right,front,back',
            'default_levels' => 'required|integer|min:1|max:20',
            'default_sections' => 'required|integer|min:1|max:30',
            'available' => 'boolean',
        ]);

        $oldData = $style->only(['code', 'name', 'description', 'faces', 'default_levels', 'default_sections', 'available']);

        $style->update($validated);

        // Registrar en activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($style)
            ->event('updated')
            ->withProperties(['old' => $oldData, 'attributes' => $style->getChanges()])
            ->log('Estilo actualizado: ' . $style->name);

        return redirect()->route('manager.warehouse.styles', ['warehouse_uid' => $warehouse->uid])->with('success', 'Estilo actualizado exitosamente');
    }

    /**
     * Remove the specified stand style from storage
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/styles/{style_uid}/destroy
     */
    public function destroy($warehouse_uid, $style_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $style = WarehouseLocationStyle::where('uid', $style_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();

        // Check if style has associated locations
        if ($style->locations()->count() > 0) {
            return redirect()->route('manager.warehouse.styles', ['warehouse_uid' => $warehouse->uid])->with('error', 'No se puede eliminar un estilo que contiene ubicaciones');
        }

        // Registrar en activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($style)
            ->event('deleted')
            ->log('Estilo eliminado: ' . $style->name);

        $style->delete();

        return redirect()->route('manager.warehouse.styles', ['warehouse_uid' => $warehouse->uid])->with('success', 'Estilo eliminado exitosamente');
    }
}

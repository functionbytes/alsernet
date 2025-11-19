<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\WarehouseLocationStyle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StandStylesController extends Controller
{
    /**
     * Display a listing of stand styles
     */
    public function index()
    {
        $styles = WarehouseLocationStyle::available()->orderBy('created_at', 'desc')->paginate(15);

        return view('managers.views.warehouse.styles.index', [
            'styles' => $styles,
        ]);
    }

    /**
     * Show the form for creating a new stand style
     */
    public function create()
    {
        $faces = ['left', 'right', 'front', 'back'];
        $types = ['ROW' => 'Pasillo Lineal', 'ISLAND' => 'Isla', 'WALL' => 'Pared'];

        return view('managers.views.warehouse.styles.create', [
            'faces' => $faces,
            'types' => $types,
        ]);
    }

    /**
     * Store a newly created stand style in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
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
        $validated['available'] = $validated['available'] ?? true;

        $style = WarehouseLocationStyle::create($validated);

        // Registrar en activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($style)
            ->event('created')
            ->log('Estilo de ubicación creado: ' . $style->name);

        return redirect()->route('manager.warehouse.styles')->with('success', 'Estilo de ubicación creado exitosamente');
    }

    /**
     * Display the specified stand style
     */
    public function view($uid)
    {
        $style = WarehouseLocationStyle::where('uid', $uid)->firstOrFail();

        $summary = [
            'total_locations' => $style->locations()->count(),
            'active_locations' => $style->locations()->where('available', true)->count(),
        ];

        return view('managers.views.warehouse.styles.view', [
            'style' => $style,
            'summary' => $summary,
        ]);
    }

    /**
     * Show the form for editing the specified stand style
     */
    public function edit($uid)
    {
        $style = WarehouseLocationStyle::where('uid', $uid)->firstOrFail();
        $faces = ['left', 'right', 'front', 'back'];
        $types = ['ROW' => 'Pasillo Lineal', 'ISLAND' => 'Isla', 'WALL' => 'Pared'];

        return view('managers.views.warehouse.styles.edit', [
            'style' => $style,
            'faces' => $faces,
            'types' => $types,
        ]);
    }

    /**
     * Update the specified stand style in storage
     */
    public function update(Request $request)
    {
        $style = WarehouseLocationStyle::where('uid', $request->uid)->firstOrFail();

        $validated = $request->validate([
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

        return redirect()->route('manager.warehouse.styles')->with('success', 'Estilo actualizado exitosamente');
    }

    /**
     * Remove the specified stand style from storage
     */
    public function destroy($uid)
    {
        $style = WarehouseLocationStyle::where('uid', $uid)->firstOrFail();

        // Check if style has associated locations
        if ($style->locations()->count() > 0) {
            return redirect()->route('manager.warehouse.styles')->with('error', 'No se puede eliminar un estilo que contiene ubicaciones');
        }

        // Registrar en activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($style)
            ->event('deleted')
            ->log('Estilo eliminado: ' . $style->name);

        $style->delete();

        return redirect()->route('manager.warehouse.styles')->with('success', 'Estilo eliminado exitosamente');
    }
}

<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\WarehouseLocationStyle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WarehouseLocationStylesController extends Controller
{
    /**
     * Display a listing of stand styles for a specific warehouse
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/styles
     */
    public function index()
    {
        $styles = WarehouseLocationStyle::available()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('managers.views.warehouse.styles.index', [
            'styles' => $styles,
        ]);
    }

    /**
     * Show the form for creating a new stand style
     * Ruta: /manager/warehouse/styles/create
     */
    public function create()
    {
        // Key = valor en inglés (se guarda en DB), Value = etiqueta en español (se muestra al usuario)
        $faces = [
            'left' => 'Izquierda',
            'right' => 'Derecha',
            'front' => 'Adelante',
            'back' => 'Fondo'
        ];

        $types = ['row' => 'Pasillo', 'island' => 'Isla', 'wall' => 'Pared'];

        return view('managers.views.warehouse.styles.create', [
            'faces' => $faces,
            'types' => $types,
        ]);
    }

    /**
     * Store a newly created stand style in storage
     * Ruta: POST /manager/warehouse/styles/store
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:warehouse_location_styles,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'type' => 'required|in:row,island,wall',
            'faces' => 'required|array|min:1',
            'faces.*' => 'in:left,right,front,back',
            'width' => 'required|integer|min:1|max:200',
            'height' => 'required|integer|min:1|max:200',
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
     * Ruta: /manager/warehouse/styles/{style_uid}
     */
    public function view($style_uid)
    {
        $style = WarehouseLocationStyle::where('uid', $style_uid)->firstOrFail();

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
     * Ruta: /manager/warehouse/styles/{style_uid}/edit
     */
    public function edit($style_uid)
    {
        $style = WarehouseLocationStyle::where('uid', $style_uid)->firstOrFail();

        // Key = valor en inglés (se guarda en DB), Value = etiqueta en español (se muestra al usuario)
        $faces = [
            'left' => 'Izquierda',
            'right' => 'Derecha',
            'front' => 'Adelante',
            'back' => 'Fondo'
        ];
        $types = ['row' => 'Pasillo', 'island' => 'Isla', 'wall' => 'Pared'];

        return view('managers.views.warehouse.styles.edit', [
            'style' => $style,
            'faces' => $faces,
            'types' => $types,
        ]);
    }

    /**
     * Update the specified stand style in storage
     * Ruta: POST /manager/warehouse/styles/update
     */
    public function update(Request $request)
    {
        $style = WarehouseLocationStyle::where('uid', $request->uid)->firstOrFail();

        $validated = $request->validate([
            'uid' => 'required|exists:warehouse_location_styles,uid',
            'code' => 'required|string|max:50|unique:warehouse_location_styles,code,' . $style->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'type' => 'required|in:row,island,wall',
            'faces' => 'required|array|min:1',
            'faces.*' => 'in:left,right,front,back',
            'width' => 'required|integer|min:1|max:200',
            'height' => 'required|integer|min:1|max:200',
            'default_levels' => 'required|integer|min:1|max:20',
            'default_sections' => 'required|integer|min:1|max:30',
            'available' => 'boolean',
        ]);

        $oldData = $style->only(['code', 'name', 'description', 'type', 'faces', 'height', 'width', 'default_levels', 'default_sections', 'available']);

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
     * Ruta: /manager/warehouse/styles/{style_uid}/destroy
     */
    public function destroy($style_uid)
    {
        $style = WarehouseLocationStyle::where('uid', $style_uid)->firstOrFail();

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

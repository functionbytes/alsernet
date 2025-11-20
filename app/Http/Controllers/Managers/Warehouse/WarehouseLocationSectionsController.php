<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseFloor;
use App\Models\Warehouse\WarehouseLocation;
use App\Models\Warehouse\WarehouseLocationSection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WarehouseLocationSectionsController extends Controller
{
    /**
     * Display all sections for a location
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/{location_uid}/sections
     */
    public function index($warehouse_uid, $floor_uid, $location_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();

        $sections = $location->sections()
            ->with('slots')
            ->orderBy('level', 'asc')
            ->paginate(20);

        return view('managers.views.warehouse.sections.index', [
            'warehouse' => $warehouse,
            'floor' => $floor,
            'location' => $location,
            'sections' => $sections,
        ]);
    }

    /**
     * Show form for creating a new section (Modal)
     * Ruta: GET /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/{location_uid}/sections/create
     */
    public function create($warehouse_uid, $floor_uid, $location_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();

        // Get next available level
        $nextLevel = $location->sections()->max('level') + 1 ?? 1;

        return view('managers.views.warehouse.sections.create', [
            'warehouse' => $warehouse,
            'floor' => $floor,
            'location' => $location,
            'next_level' => $nextLevel,
        ]);
    }

    /**
     * Store a newly created section
     * Ruta: POST /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/{location_uid}/sections/store
     */
    public function store(Request $request)
    {
        $warehouse = Warehouse::uid($request->warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $request->floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $request->location_uid)->where('floor_id', $floor->id)->firstOrFail();

        $validated = $request->validate([
            'warehouse_uid' => 'required|exists:warehouses,uid',
            'floor_uid' => 'required|exists:warehouse_floors,uid',
            'location_uid' => 'required|exists:warehouse_locations,uid',
            'code' => 'required|string|max:50|unique:warehouse_location_sections,code',
            'barcode' => 'nullable|string|max:100|unique:warehouse_location_sections,barcode',
            'level' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check if code is unique per location
        if (WarehouseLocationSection::where('location_id', $location->id)
            ->where('code', $validated['code'])
            ->exists()) {
            return redirect()->back()->with('error', 'El código de sección ya existe en esta ubicación');
        }

        $section = WarehouseLocationSection::create([
            'uid' => Str::uuid(),
            'location_id' => $location->id,
            'code' => $validated['code'],
            'barcode' => $validated['barcode'],
            'level' => $validated['level'],
            'notes' => $validated['notes'],
            'available' => true,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sección creada exitosamente',
                'data' => $section->getSummary(),
            ]);
        }

        return redirect()->route('manager.warehouse.sections', [
            'warehouse_uid' => $warehouse->uid,
            'floor_uid' => $floor->uid,
            'location_uid' => $location->uid,
        ])->with('success', 'Sección creada exitosamente');
    }

    /**
     * Display the specified section
     */
    public function view($warehouse_uid, $floor_uid, $location_uid, $section_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();
        $section = WarehouseLocationSection::where('uid', $section_uid)->where('location_id', $location->id)->firstOrFail();

        $slots = $section->slots()->with('product')->paginate(20);

        return view('managers.views.warehouse.sections.view', [
            'warehouse' => $warehouse,
            'floor' => $floor,
            'location' => $location,
            'section' => $section,
            'slots' => $slots,
        ]);
    }

    /**
     * Show form for editing a section
     */
    public function edit($warehouse_uid, $floor_uid, $location_uid, $section_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();
        $section = WarehouseLocationSection::where('uid', $section_uid)->where('location_id', $location->id)->firstOrFail();

        return view('managers.views.warehouse.sections.edit', [
            'warehouse' => $warehouse,
            'floor' => $floor,
            'location' => $location,
            'section' => $section,
        ]);
    }

    /**
     * Update the specified section
     */
    public function update(Request $request)
    {
        $warehouse = Warehouse::uid($request->warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $request->floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $request->location_uid)->where('floor_id', $floor->id)->firstOrFail();
        $section = WarehouseLocationSection::where('uid', $request->section_uid)->where('location_id', $location->id)->firstOrFail();

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:warehouse_location_sections,code,' . $section->id,
            'barcode' => 'nullable|string|max:100|unique:warehouse_location_sections,barcode,' . $section->id,
            'level' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        $section->update($validated);

        return redirect()->route('manager.warehouse.section.view', [
            'warehouse_uid' => $warehouse->uid,
            'floor_uid' => $floor->uid,
            'location_uid' => $location->uid,
            'section_uid' => $section->uid,
        ])->with('success', 'Sección actualizada exitosamente');
    }

    /**
     * Delete a section
     */
    public function destroy($warehouse_uid, $floor_uid, $location_uid, $section_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();
        $section = WarehouseLocationSection::where('uid', $section_uid)->where('location_id', $location->id)->firstOrFail();

        // Check if section has slots
        if ($section->slots()->exists()) {
            return redirect()->back()->with('error', 'No se puede eliminar una sección que tiene slots');
        }

        $section->delete();

        return redirect()->route('manager.warehouse.sections', [
            'warehouse_uid' => $warehouse->uid,
            'floor_uid' => $floor->uid,
            'location_uid' => $location->uid,
        ])->with('success', 'Sección eliminada exitosamente');
    }

    /**
     * Quick create section via AJAX (for modal)
     * Ruta: POST /manager/warehouse/sections/quick-create
     */
    public function quickCreate(Request $request)
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:warehouse_locations,id',
            'code' => 'required|string|max:50',
            'barcode' => 'nullable|string|max:100',
            'level' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        $location = WarehouseLocation::findOrFail($validated['location_id']);

        // Check unique code per location
        if (WarehouseLocationSection::where('location_id', $location->id)
            ->where('code', $validated['code'])
            ->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El código de sección ya existe en esta ubicación',
            ], 422);
        }

        try {
            $section = WarehouseLocationSection::create([
                'uid' => Str::uuid(),
                'location_id' => $location->id,
                'code' => $validated['code'],
                'barcode' => $validated['barcode'],
                'level' => $validated['level'],
                'notes' => $validated['notes'],
                'available' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sección creada exitosamente',
                'data' => $section->getSummary(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la sección: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sections for a location (AJAX)
     */
    public function getSectionsList($location_id)
    {
        $location = WarehouseLocation::findOrFail($location_id);

        $sections = $location->sections()
            ->select('id', 'uid', 'code', 'level')
            ->orderBy('level', 'asc')
            ->get()
            ->map(fn($section) => [
                'id' => $section->id,
                'uid' => $section->uid,
                'code' => $section->code,
                'level' => $section->level,
                'label' => "{$section->code} (Nivel {$section->level})",
            ]);

        return response()->json([
            'success' => true,
            'data' => $sections,
        ]);
    }
}

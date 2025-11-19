<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\Stand;
use App\Models\Warehouse\Floor;
use App\Models\Warehouse\StandStyle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StandsController extends Controller
{
    /**
     * Display a listing of stands
     */
    public function index(Request $request)
    {
        $query = Stand::with(['floor', 'style']);

        // Filter by floor if provided
        if ($request->filled('floor_id')) {
            $query->byFloor($request->floor_id);
        }

        // Filter by style if provided
        if ($request->filled('stand_style_id')) {
            $query->byStyle($request->stand_style_id);
        }

        // Search if provided
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $stands = $query->ordered()->paginate(15);
        $floors = Floor::available()->ordered()->get();
        $styles = StandStyle::available()->get();

        return view('managers.views.warehouse.stands.index', [
            'stands' => $stands,
            'floors' => $floors,
            'styles' => $styles,
        ]);
    }

    /**
     * Show the form for creating a new stand
     */
    public function create()
    {
        $floors = Floor::available()->ordered()->get();
        $styles = StandStyle::available()->get();

        return view('managers.views.warehouse.stands.create', [
            'floors' => $floors,
            'styles' => $styles,
        ]);
    }

    /**
     * Store a newly created stand in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'floor_id' => 'required|exists:warehouse_floors,id',
            'stand_style_id' => 'required|exists:warehouse_stand_styles,id',
            'code' => 'required|string|max:50|unique:warehouse_stands,code',
            'barcode' => 'nullable|string|max:100|unique:warehouse_stands,barcode',
            'position_x' => 'required|integer|min:0',
            'position_y' => 'required|integer|min:0',
            'position_z' => 'nullable|integer|min:0',
            'total_levels' => 'required|integer|min:1|max:20',
            'total_sections' => 'required|integer|min:1|max:30',
            'capacity' => 'nullable|numeric|min:0',
            'available' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['uid'] = Str::uuid();
        $validated['available'] = $validated['available'] ?? true;

        $stand = Stand::create($validated);

        // Create slots automatically if requested
        if ($request->filled('auto_create_slots')) {
            $stand->createSlots();
        }

        return redirect()->route('managers.views.warehouse.stands')->with('success', 'Estantería creada exitosamente');
    }

    /**
     * Display the specified stand
     */
    public function view($uid)
    {
        $stand = Stand::where('uid', $uid)->with(['floor', 'style', 'slots'])->firstOrFail();
        $summary = $stand->getSummary();

        return view('managers.views.warehouse.stands.view', [
            'stand' => $stand,
            'summary' => $summary,
        ]);
    }

    /**
     * Show the form for editing the specified stand
     */
    public function edit($uid)
    {
        $stand = Stand::where('uid', $uid)->firstOrFail();
        $floors = Floor::available()->ordered()->get();
        $styles = StandStyle::available()->get();

        return view('managers.views.warehouse.stands.edit', [
            'stand' => $stand,
            'floors' => $floors,
            'styles' => $styles,
        ]);
    }

    /**
     * Update the specified stand in storage
     */
    public function update(Request $request)
    {
        $stand = Stand::where('uid', $request->uid)->firstOrFail();

        $validated = $request->validate([
            'uid' => 'required|exists:warehouse_stands,uid',
            'floor_id' => 'required|exists:warehouse_floors,id',
            'stand_style_id' => 'required|exists:warehouse_stand_styles,id',
            'code' => 'required|string|max:50|unique:warehouse_stands,code,' . $stand->id,
            'barcode' => 'nullable|string|max:100|unique:warehouse_stands,barcode,' . $stand->id,
            'position_x' => 'required|integer|min:0',
            'position_y' => 'required|integer|min:0',
            'position_z' => 'nullable|integer|min:0',
            'total_levels' => 'required|integer|min:1|max:20',
            'total_sections' => 'required|integer|min:1|max:30',
            'capacity' => 'nullable|numeric|min:0',
            'available' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $stand->update($validated);

        return redirect()->route('managers.views.warehouse.stands')->with('success', 'Estantería actualizada exitosamente');
    }

    /**
     * Remove the specified stand from storage
     */
    public function destroy($uid)
    {
        $stand = Stand::where('uid', $uid)->firstOrFail();

        // Check if stand has occupied slots
        if ($stand->getOccupiedSlots() > 0) {
            return redirect()->route('managers.views.warehouse.stands')->with('error', 'No se puede eliminar una estantería que contiene productos almacenados');
        }

        $stand->delete();

        return redirect()->route('managers.views.warehouse.stands')->with('success', 'Estantería eliminada exitosamente');
    }
}

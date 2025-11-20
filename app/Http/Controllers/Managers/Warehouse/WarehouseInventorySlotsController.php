<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseFloor;
use App\Models\Warehouse\WarehouseLocation;
use App\Models\Warehouse\WarehouseLocationSection;
use App\Models\Warehouse\WarehouseInventorySlot;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WarehouseInventorySlotsController extends Controller
{
    /**
     * Display a listing of inventory slots for a specific section
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/{location_uid}/sections/{section_uid}/slots
     */
    public function index(Request $request, $warehouse_uid, $floor_uid, $location_uid, $section_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();
        $section = WarehouseLocationSection::where('uid', $section_uid)->where('location_id', $location->id)->firstOrFail();

        $query = WarehouseInventorySlot::where('section_id', $section->id)
            ->with(['section.location.floor', 'product', 'lastSection']);

        // Filter by occupied status
        if ($request->filled('status')) {
            if ($request->status === 'occupied') {
                $query->occupied();
            } elseif ($request->status === 'available') {
                $query->available();
            }
        }

        // Filter by product if provided
        if ($request->filled('product_id')) {
            $query->byProduct($request->product_id);
        }

        // Search if provided
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $slots = $query->orderBy('created_at', 'desc')->paginate(20);
        $products = Product::available()->get();

        return view('managers.views.warehouse.inventory-slots.index', [
            'warehouse' => $warehouse,
            'floor' => $floor,
            'location' => $location,
            'section' => $section,
            'slots' => $slots,
            'products' => $products,
        ]);
    }

    /**
     * Show the form for creating a new inventory slot
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/{location_uid}/sections/{section_uid}/slots/create
     */
    public function create($warehouse_uid, $floor_uid, $location_uid, $section_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();
        $section = WarehouseLocationSection::where('uid', $section_uid)->where('location_id', $location->id)->firstOrFail();

        $products = Product::available()->get();

        return view('managers.views.warehouse.inventory-slots.create', [
            'warehouse' => $warehouse,
            'floor' => $floor,
            'location' => $location,
            'section' => $section,
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created inventory slot in storage
     * Ruta: POST /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/{location_uid}/sections/{section_uid}/slots/store
     */
    public function store(Request $request)
    {
        $warehouse = Warehouse::uid($request->warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $request->floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $request->location_uid)->where('floor_id', $floor->id)->firstOrFail();
        $section = WarehouseLocationSection::where('uid', $request->section_uid)->where('location_id', $location->id)->firstOrFail();

        $validated = $request->validate([
            'warehouse_uid' => 'required|exists:warehouses,uid',
            'floor_uid' => 'required|exists:warehouse_floors,uid',
            'location_uid' => 'required|exists:warehouse_locations,uid',
            'section_uid' => 'required|exists:warehouse_location_sections,uid',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:0',
            'kardex' => 'nullable|integer|min:0',
        ]);

        // Check if slot already exists for this product in this section
        $existingSlot = WarehouseInventorySlot::where('section_id', $section->id)
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($existingSlot) {
            return redirect()->back()->with('error', 'Este producto ya tiene un slot en esta sección');
        }

        $validated['section_id'] = $section->id;
        $validated['uid'] = Str::uuid();

        WarehouseInventorySlot::create($validated);

        return redirect()->route('manager.warehouse.section.slots', [
            'warehouse_uid' => $warehouse->uid,
            'floor_uid' => $floor->uid,
            'location_uid' => $location->uid,
            'section_uid' => $section->uid,
        ])->with('success', 'Slot de inventario creado exitosamente');
    }

    /**
     * Display the specified inventory slot
     */
    public function view($warehouse_uid, $floor_uid, $location_uid, $section_uid, $slot_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();
        $section = WarehouseLocationSection::where('uid', $section_uid)->where('location_id', $location->id)->firstOrFail();
        $slot = WarehouseInventorySlot::where('uid', $slot_uid)->where('section_id', $section->id)
            ->with(['section.location.floor', 'product', 'lastSection', 'movements'])->firstOrFail();

        return view('managers.views.warehouse.inventory-slots.view', [
            'warehouse' => $warehouse,
            'floor' => $floor,
            'location' => $location,
            'section' => $section,
            'slot' => $slot,
        ]);
    }

    /**
     * Show the form for editing the specified inventory slot
     */
    public function edit($warehouse_uid, $floor_uid, $location_uid, $section_uid, $slot_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();
        $section = WarehouseLocationSection::where('uid', $section_uid)->where('location_id', $location->id)->firstOrFail();
        $slot = WarehouseInventorySlot::where('uid', $slot_uid)->where('section_id', $section->id)->firstOrFail();

        $products = Product::available()->get();

        return view('managers.views.warehouse.inventory-slots.edit', [
            'warehouse' => $warehouse,
            'floor' => $floor,
            'location' => $location,
            'section' => $section,
            'slot' => $slot,
            'products' => $products,
        ]);
    }

    /**
     * Update the specified inventory slot in storage
     */
    public function update(Request $request)
    {
        $warehouse = Warehouse::uid($request->warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $request->floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $request->location_uid)->where('floor_id', $floor->id)->firstOrFail();
        $section = WarehouseLocationSection::where('uid', $request->section_uid)->where('location_id', $location->id)->firstOrFail();
        $slot = WarehouseInventorySlot::where('uid', $request->uid)->where('section_id', $section->id)->firstOrFail();

        $validated = $request->validate([
            'uid' => 'required|exists:warehouse_inventory_slots,uid',
            'product_id' => 'nullable|exists:products,id',
            'quantity' => 'nullable|integer|min:0',
            'kardex' => 'nullable|integer|min:0',
        ]);

        $slot->update($validated);

        return redirect()->route('manager.warehouse.section.slots', [
            'warehouse_uid' => $warehouse->uid,
            'floor_uid' => $floor->uid,
            'location_uid' => $location->uid,
            'section_uid' => $section->uid,
        ])->with('success', 'Slot actualizado exitosamente');
    }

    /**
     * Remove the specified inventory slot from storage
     */
    public function destroy($warehouse_uid, $floor_uid, $location_uid, $section_uid, $slot_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();
        $section = WarehouseLocationSection::where('uid', $section_uid)->where('location_id', $location->id)->firstOrFail();
        $slot = WarehouseInventorySlot::where('uid', $slot_uid)->where('section_id', $section->id)->firstOrFail();

        $slot->delete();

        return redirect()->route('manager.warehouse.section.slots', [
            'warehouse_uid' => $warehouse->uid,
            'floor_uid' => $floor->uid,
            'location_uid' => $location->uid,
            'section_uid' => $section->uid,
        ])->with('success', 'Slot eliminado exitosamente');
    }

    /**
     * Add quantity to an inventory slot
     * Ruta: POST /manager/warehouse/.../slots/{slot_uid}/add-quantity
     */
    public function addQuantity(Request $request, $warehouse_uid, $floor_uid, $location_uid, $section_uid, $slot_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();
        $section = WarehouseLocationSection::where('uid', $section_uid)->where('location_id', $location->id)->firstOrFail();
        $slot = WarehouseInventorySlot::where('uid', $slot_uid)->where('section_id', $section->id)->firstOrFail();

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $slot->addQuantity(
            $validated['quantity'],
            $validated['reason'] ?? 'Manual addition',
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Cantidad agregada exitosamente',
            'data' => $slot->fresh()->getSummary(),
        ]);
    }

    /**
     * Subtract quantity from an inventory slot
     * Ruta: POST /manager/warehouse/.../slots/{slot_uid}/subtract-quantity
     */
    public function subtractQuantity(Request $request, $warehouse_uid, $floor_uid, $location_uid, $section_uid, $slot_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();
        $section = WarehouseLocationSection::where('uid', $section_uid)->where('location_id', $location->id)->firstOrFail();
        $slot = WarehouseInventorySlot::where('uid', $slot_uid)->where('section_id', $section->id)->firstOrFail();

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $slot->subtractQuantity(
            $validated['quantity'],
            $validated['reason'] ?? 'Manual subtraction',
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Cantidad restada exitosamente',
            'data' => $slot->fresh()->getSummary(),
        ]);
    }

    /**
     * Clear an inventory slot completely
     * Ruta: POST /manager/warehouse/.../slots/{slot_uid}/clear
     */
    public function clear(Request $request, $warehouse_uid, $floor_uid, $location_uid, $section_uid, $slot_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();
        $section = WarehouseLocationSection::where('uid', $section_uid)->where('location_id', $location->id)->firstOrFail();
        $slot = WarehouseInventorySlot::where('uid', $slot_uid)->where('section_id', $section->id)->firstOrFail();

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $slot->clear(
            $validated['reason'] ?? 'Manual clearing',
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Slot vaciado exitosamente',
            'data' => $slot->fresh()->getSummary(),
        ]);
    }

    /**
     * Move product to another section
     */
    public function moveTo(Request $request, $warehouse_uid, $floor_uid, $location_uid, $section_uid, $slot_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();
        $section = WarehouseLocationSection::where('uid', $section_uid)->where('location_id', $location->id)->firstOrFail();
        $slot = WarehouseInventorySlot::where('uid', $slot_uid)->where('section_id', $section->id)->firstOrFail();

        $validated = $request->validate([
            'new_section_id' => 'required|exists:warehouse_location_sections,id',
            'quantity' => 'nullable|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $newSection = WarehouseLocationSection::findOrFail($validated['new_section_id']);

        if (!$slot->moveTo(
            $newSection,
            $validated['quantity'] ?? null,
            $validated['reason'] ?? 'Transfer between sections',
            auth()->id()
        )) {
            return response()->json([
                'success' => false,
                'message' => 'No hay suficiente cantidad para mover',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Producto movido exitosamente',
            'data' => $slot->fresh()->getSummary(),
        ]);
    }
}

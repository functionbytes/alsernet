<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\WarehouseInventorySlot;
use App\Models\Warehouse\WarehouseInventoryMovement;
use App\Models\Location;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InventorySlotsController extends Controller
{
    /**
     * Display a listing of inventory slots
     */
    public function index(Request $request)
    {
        $query = WarehouseInventorySlot::with(['location.inventarie', 'location.floor', 'location.style', 'product']);

        // Filter by location if provided
        if ($request->filled('location_id')) {
            $query->byLocation($request->location_id);
        }

        // Filter by occupied status
        if ($request->filled('status')) {
            if ($request->status === 'occupied') {
                $query->occupied();
            } elseif ($request->status === 'available') {
                $query->available();
            }
        }

        // Filter by face if provided
        if ($request->filled('face')) {
            $query->byFace($request->face);
        }

        // Search if provided
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $slots = $query->orderBy('created_at', 'desc')->paginate(20);
        $locations = Location::available()->byInventarie(auth()->user()->current_warehouse_id ?? 1)->get();
        $faces = ['left', 'right', 'front', 'back'];

        return view('managers.views.warehouse.inventory-slots.index', [
            'slots' => $slots,
            'locations' => $locations,
            'faces' => $faces,
        ]);
    }

    /**
     * Show the form for creating a new inventory slot
     */
    public function create()
    {
        $locations = Location::available()->byInventarie(auth()->user()->current_warehouse_id ?? 1)->get();
        $products = Product::available()->get();

        return view('managers.views.warehouse.inventory-slots.create', [
            'locations' => $locations,
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created inventory slot in storage
     */
    public function store(Request $request)
    {
        $location = Location::findOrFail($request->location_id);

        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'product_id' => 'nullable|exists:products,id',
            'face' => 'required|in:left,right,front,back',
            'level' => 'required|integer|min:1',
            'section' => 'required|integer|min:1',
            'quantity' => 'nullable|integer|min:0',
            'max_quantity' => 'nullable|integer|min:1',
            'weight_current' => 'nullable|numeric|min:0',
            'weight_max' => 'nullable|numeric|min:0',
        ]);

        $validated['uid'] = Str::uuid();
        $validated['barcode'] = 'SLOT-' . strtoupper(Str::random(8));
        $validated['is_occupied'] = $request->filled('product_id');

        WarehouseInventorySlot::create($validated);

        return redirect()->route('managers.views.warehouse.slots')->with('success', 'Posición de inventario creada exitosamente');
    }

    /**
     * Display the specified inventory slot
     */
    public function view($uid)
    {
        $slot = WarehouseInventorySlot::where('uid', $uid)->with(['location.floor', 'location.style', 'product', 'movements'])->firstOrFail();

        return view('managers.views.warehouse.inventory-slots.view', [
            'slot' => $slot,
        ]);
    }

    /**
     * Show the form for editing the specified inventory slot
     */
    public function edit($uid)
    {
        $slot = WarehouseInventorySlot::where('uid', $uid)->firstOrFail();
        $products = Product::available()->get();

        return view('managers.views.warehouse.inventory-slots.edit', [
            'slot' => $slot,
            'products' => $products,
        ]);
    }

    /**
     * Update the specified inventory slot in storage
     */
    public function update(Request $request)
    {
        $slot = WarehouseInventorySlot::where('uid', $request->uid)->firstOrFail();

        $validated = $request->validate([
            'uid' => 'required|exists:warehouse_inventory_slots,uid',
            'product_id' => 'nullable|exists:products,id',
            'quantity' => 'nullable|integer|min:0',
            'max_quantity' => 'nullable|integer|min:1',
            'weight_current' => 'nullable|numeric|min:0',
            'weight_max' => 'nullable|numeric|min:0',
        ]);

        $validated['is_occupied'] = $request->filled('product_id');

        $slot->update($validated);

        return redirect()->route('managers.views.warehouse.slots')->with('success', 'Posición actualizada exitosamente');
    }

    /**
     * Remove the specified inventory slot from storage
     */
    public function destroy($uid)
    {
        $slot = WarehouseInventorySlot::where('uid', $uid)->firstOrFail();
        $slot->delete();

        return redirect()->route('managers.views.warehouse.slots')->with('success', 'Posición eliminada exitosamente');
    }

    /**
     * Add quantity to an inventory slot
     */
    public function addQuantity(Request $request, $uid)
    {
        $slot = WarehouseInventorySlot::where('uid', $uid)->firstOrFail();

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
            'inventarie_id' => 'nullable|integer|exists:inventaries,id',
        ]);

        if ($slot->canAddQuantity($validated['quantity'])) {
            $slot->addQuantity(
                $validated['quantity'],
                $validated['reason'] ?? 'Manual addition',
                auth()->id(),
                $validated['inventarie_id']
            );
            return response()->json([
                'success' => true,
                'message' => 'Cantidad agregada exitosamente',
                'data' => $slot->fresh()->getSummary(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No hay suficiente espacio para esta cantidad',
        ], 400);
    }

    /**
     * Subtract quantity from an inventory slot
     */
    public function subtractQuantity(Request $request, $uid)
    {
        $slot = WarehouseInventorySlot::where('uid', $uid)->firstOrFail();

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
            'inventarie_id' => 'nullable|integer|exists:inventaries,id',
        ]);

        if ($slot->subtractQuantity(
            $validated['quantity'],
            $validated['reason'] ?? 'Manual subtraction',
            auth()->id(),
            $validated['inventarie_id']
        )) {
            return response()->json([
                'success' => true,
                'message' => 'Cantidad restada exitosamente',
                'data' => $slot->fresh()->getSummary(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se puede restar más cantidad de la que existe',
        ], 400);
    }

    /**
     * Add weight to an inventory slot
     */
    public function addWeight(Request $request, $uid)
    {
        $slot = WarehouseInventorySlot::where('uid', $uid)->firstOrFail();

        $validated = $request->validate([
            'weight' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
            'inventarie_id' => 'nullable|integer|exists:inventaries,id',
        ]);

        if ($slot->canAddWeight($validated['weight'])) {
            $slot->addWeight(
                $validated['weight'],
                $validated['reason'] ?? 'Manual weight addition',
                auth()->id(),
                $validated['inventarie_id']
            );
            return response()->json([
                'success' => true,
                'message' => 'Peso agregado exitosamente',
                'data' => $slot->fresh()->getSummary(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No hay suficiente capacidad de peso',
        ], 400);
    }

    /**
     * Clear an inventory slot completely
     */
    public function clear(Request $request, $uid)
    {
        $slot = WarehouseInventorySlot::where('uid', $uid)->firstOrFail();

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
            'inventarie_id' => 'nullable|integer|exists:inventaries,id',
        ]);

        $slot->clear(
            $validated['reason'] ?? 'Manual clearing',
            auth()->id(),
            $validated['inventarie_id']
        );

        return response()->json([
            'success' => true,
            'message' => 'Posición vaciada exitosamente',
            'data' => $slot->fresh()->getSummary(),
        ]);
    }
}

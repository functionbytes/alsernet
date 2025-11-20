<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\WarehouseInventorySlot;
use App\Models\Warehouse\WarehouseInventoryMovement;
use App\Models\Warehouse\WarehouseLocation;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InventorySlotsController extends Controller
{
    /**
     * Display a listing of inventory slots
     */
    public function index(Request $request)
    {
        $query = WarehouseInventorySlot::with(['location.warehouse', 'location.floor', 'location.style', 'product']);

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
        $locations = WarehouseLocation::available()->with(['warehouse', 'floor', 'style'])->get();
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
        $locations = WarehouseLocation::available()->with(['warehouse', 'floor', 'style'])->get();
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
        $location = WarehouseLocation::findOrFail($request->location_id);

        $validated = $request->validate([
            'location_id' => 'required|exists:warehouse_locations,id',
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

        $slot = WarehouseInventorySlot::create($validated);

        // Registrar en activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($slot)
            ->event('created')
            ->log('Slot creado: ' . $slot->getAddress());

        return redirect()->route('manager.warehouse.slots')->with('success', 'Posición de inventario creada exitosamente');
    }

    /**
     * Display the specified inventory slot
     */
    public function view($uid)
    {
        $slot = WarehouseInventorySlot::where('uid', $uid)->with(['location.warehouse', 'location.floor', 'location.style', 'product', 'movements'])->firstOrFail();

        $summary = $slot->getSummary();

        return view('managers.views.warehouse.inventory-slots.view', [
            'slot' => $slot,
            'summary' => $summary,
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

        $oldData = $slot->only(['product_id', 'quantity', 'max_quantity', 'weight_current', 'weight_max']);

        $validated['is_occupied'] = $request->filled('product_id');

        $slot->update($validated);

        // Registrar en activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($slot)
            ->event('updated')
            ->withProperties(['old' => $oldData, 'attributes' => $slot->getChanges()])
            ->log('Slot actualizado: ' . $slot->getAddress());

        return redirect()->route('manager.warehouse.slots')->with('success', 'Posición actualizada exitosamente');
    }

    /**
     * Remove the specified inventory slot from storage
     */
    public function destroy($uid)
    {
        $slot = WarehouseInventorySlot::where('uid', $uid)->firstOrFail();

        // Registrar en activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($slot)
            ->event('deleted')
            ->log('Slot eliminado: ' . $slot->getAddress());

        $slot->delete();

        return redirect()->route('manager.warehouse.slots')->with('success', 'Posición eliminada exitosamente');
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
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ]);

        if ($slot->canAddQuantity($validated['quantity'])) {
            $slot->addQuantity(
                $validated['quantity'],
                $validated['reason'] ?? 'Adición manual',
                auth()->id(),
                $validated['warehouse_id'] ?? $slot->location->warehouse_id
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
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ]);

        if ($slot->subtractQuantity(
            $validated['quantity'],
            $validated['reason'] ?? 'Sustracción manual',
            auth()->id(),
            $validated['warehouse_id'] ?? $slot->location->warehouse_id
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
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ]);

        if ($slot->canAddWeight($validated['weight'])) {
            $slot->addWeight(
                $validated['weight'],
                $validated['reason'] ?? 'Adición de peso manual',
                auth()->id(),
                $validated['warehouse_id'] ?? $slot->location->warehouse_id
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
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ]);

        $slot->clear(
            $validated['reason'] ?? 'Limpieza manual de posición',
            auth()->id(),
            $validated['warehouse_id'] ?? $slot->location->warehouse_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Posición vaciada exitosamente',
            'data' => $slot->fresh()->getSummary(),
        ]);
    }
}

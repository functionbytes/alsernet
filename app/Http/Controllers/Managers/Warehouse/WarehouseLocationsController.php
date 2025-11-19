<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseFloor;
use App\Models\Warehouse\WarehouseLocation;
use App\Models\Warehouse\WarehouseInventorySlot;
use App\Models\Warehouse\WarehouseLocationStyle;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WarehouseLocationsController extends Controller
{
    /**
     * Listar ubicaciones de un piso específico
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations
     */
    public function index(Request $request, $warehouse_uid, $floor_uid)
    {

        $warehouse = Warehouse::uid($warehouse_uid);
        $floor = WarehouseFloor::uid($floor_uid);

        $searchKey = $request->search ?? null;
        $locations = $floor->locations();

        if ($searchKey) {
            $locations = $locations->where(function ($query) use ($searchKey) {
                $query->where('code', 'like', '%' . $searchKey . '%')
                    ->orWhere('barcode', 'like', '%' . $searchKey . '%')
                    ->orWhere('name', 'like', '%' . $searchKey . '%');
            });
        }

        $locations = $locations->with(['floor', 'style', 'slots'])->paginate(paginationNumber());

        return view('managers.views.warehouse.locations.index')->with([
            'warehouse' => $warehouse,
            'floor' => $floor,
            'locations' => $locations,
            'searchKey' => $searchKey,
        ]);
    }

    /**
     * Ver detalles de una ubicación con sus slots
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/{location_uid}
     */
    public function view($warehouse_uid, $floor_uid, $location_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::uid($floor_uid);
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();

        $slots = $location->slots()->paginate(paginationNumber());

        $summary = [
            'total_slots' => $location->getTotalSlots(),
            'occupied_slots' => $location->getOccupiedSlots(),
            'available_slots' => $location->getAvailableSlots(),
            'occupancy_percentage' => $location->getOccupancyPercentage(),
            'current_weight' => $location->getCurrentWeight(),
            'capacity' => $location->capacity,
        ];

        return view('managers.views.warehouse.locations.view')->with([
            'warehouse' => $warehouse,
            'floor' => $floor,
            'location' => $location,
            'slots' => $slots,
            'summary' => $summary,
        ]);
    }

    /**
     * Crear nueva ubicación dentro de un piso
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/create
     */
    public function create($warehouse_uid, $floor_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid);
        $floor = WarehouseFloor::uid($floor_uid);

        $styles = WarehouseLocationStyle::available()->pluck('name', 'id');

        return view('managers.views.warehouse.locations.create')->with([
            'warehouse' => $warehouse,
            'floor' => $floor,
            'styles' => $styles,
        ]);
    }

    /**
     * Guardar nueva ubicación
     * Ruta: POST /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/store
     */
    public function store(Request $request)
    {
        $warehouse = Warehouse::uid($request->warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $request->floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();

        $validated = $request->validate([
            'warehouse_uid' => 'required|exists:warehouses,uid',
            'floor_uid' => 'required|exists:warehouse_floors,uid',
            'style_id' => 'required|exists:warehouse_location_styles,id',
            'code' => 'required|string|unique:warehouse_locations',
            'barcode' => 'nullable|string|unique:warehouse_locations',
            'position_x' => 'required|integer',
            'position_y' => 'required|integer',
            'position_z' => 'nullable|integer',
            'total_levels' => 'required|integer|min:1',
            'total_sections' => 'required|integer|min:1',
            'capacity' => 'nullable|numeric',
            'available' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $location = WarehouseLocation::create([
            'uid' => Str::uuid(),
            'warehouse_id' => $warehouse->id,
            'floor_id' => $floor->id,
            'style_id' => $validated['style_id'],
            'code' => $validated['code'],
            'barcode' => $validated['barcode'],
            'position_x' => $validated['position_x'],
            'position_y' => $validated['position_y'],
            'position_z' => $validated['position_z'] ?? 0,
            'total_levels' => $validated['total_levels'],
            'total_sections' => $validated['total_sections'],
            'capacity' => $validated['capacity'],
            'available' => $validated['available'] ?? true,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Crear los slots automáticamente según el estilo
        $location->createSlots();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($location)
            ->event('created')
            ->log('Ubicación creada: ' . $location->code);

        return redirect()->route('manager.warehouse.locations', ['warehouse_uid' => $warehouse->uid, 'floor_uid' => $floor->uid])->with('success', 'Ubicación creada exitosamente');
    }

    /**
     * Formulario para editar ubicación
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/{location_uid}/edit
     */
    public function edit($warehouse_uid, $floor_uid, $location_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::uid($floor_uid);
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();

        $styles = WarehouseLocationStyle::where('warehouse_id', $warehouse->id)->available()->pluck('name', 'id');

        return view('managers.views.warehouse.locations.edit')->with([
            'warehouse' => $warehouse,
            'floor' => $floor,
            'location' => $location,
            'styles' => $styles,
        ]);
    }

    /**
     * Actualizar ubicación
     * Ruta: POST /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/update
     */
    public function update(Request $request)
    {
        $warehouse = Warehouse::uid($request->warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::where('uid', $request->floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $request->location_uid)->where('floor_id', $floor->id)->firstOrFail();

        $validated = $request->validate([
            'warehouse_uid' => 'required|exists:warehouses,uid',
            'floor_uid' => 'required|exists:warehouse_floors,uid',
            'location_uid' => 'required|exists:warehouse_locations,uid',
            'code' => 'required|string|unique:warehouse_locations,code,' . $location->id,
            'barcode' => 'nullable|string|unique:warehouse_locations,barcode,' . $location->id,
            'total_levels' => 'required|integer|min:1',
            'total_sections' => 'required|integer|min:1',
            'capacity' => 'nullable|numeric',
        ]);

        $oldData = $location->only(['code', 'barcode', 'total_levels', 'total_sections', 'capacity']);

        $location->update([
            'code' => $validated['code'],
            'barcode' => $validated['barcode'],
            'total_levels' => $validated['total_levels'],
            'total_sections' => $validated['total_sections'],
            'capacity' => $validated['capacity'],
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($location)
            ->event('updated')
            ->withProperties(['old' => $oldData, 'attributes' => $location->getChanges()])
            ->log('Ubicación actualizada: ' . $location->code);

        return redirect()->route('manager.warehouse.locations', ['warehouse_uid' => $warehouse->uid, 'floor_uid' => $floor->uid])->with('success', 'Ubicación actualizada exitosamente');
    }

    /**
     * Eliminar ubicación y todos sus slots
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/{location_uid}/destroy
     */
    public function destroy($warehouse_uid, $floor_uid, $location_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::uid($floor_uid);
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();

        // Eliminar todos los slots asociados
        $location->slots()->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($location)
            ->event('deleted')
            ->log('Ubicación eliminada: ' . $location->code);

        $location->delete();

        return redirect()->route('manager.warehouse.locations', ['warehouse_uid' => $warehouse->uid, 'floor_uid' => $floor->uid])->with('success', 'Ubicación eliminada exitosamente');
    }

    /**
     * Eliminar un slot específico
     * Ruta: DELETE /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/{location_uid}/slots/{slot_uid}
     */
    public function destroySlot($warehouse_uid, $floor_uid, $location_uid, $slot_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::uid($floor_uid);
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();
        $slot = WarehouseInventorySlot::where('uid', $slot_uid)->where('location_id', $location->id)->firstOrFail();

        // Limpiar el slot antes de eliminarlo
        if ($slot->is_occupied) {
            $slot->clear('Eliminación de posición', auth()->user()->id, $slot->last_warehouse_id);
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($slot)
            ->event('deleted')
            ->log('Slot eliminado: ' . $slot->getAddress());

        $slot->delete();

        return redirect()->route('manager.warehouse.locations.view', ['warehouse_uid' => $warehouse->uid, 'floor_uid' => $floor->uid, 'location_uid' => $location->uid])->with('success', 'Slot eliminado exitosamente');
    }

    /**
     * API: Obtener ubicaciones por almacén (para AJAX)
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/api/warehouse
     */
    public function getByWarehouse($warehouse_uid, $floor_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::uid($floor_uid);

        $locations = WarehouseLocation::where('floor_id', $floor->id)
            ->with(['floor', 'style'])
            ->get()
            ->map(function ($location) {
                return [
                    'id' => $location->id,
                    'uid' => $location->uid,
                    'code' => $location->code,
                    'full_name' => $location->getFullName(),
                ];
            });

        return response()->json($locations);
    }

    /**
     * API: Obtener ubicación por código de barras
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/api/barcode/{barcode}
     */
    public function getByBarcode($warehouse_uid, $floor_uid, $barcode)
    {
        $warehouse = Warehouse::uid($warehouse_uid)->firstOrFail();
        $floor = WarehouseFloor::uid($floor_uid);

        $location = WarehouseLocation::byBarcode($barcode)
            ->where('floor_id', $floor->id)
            ->with(['floor', 'style', 'slots'])
            ->first();

        if (!$location) {
            return response()->json(['error' => 'Ubicación no encontrada'], 404);
        }

        return response()->json([
            'id' => $location->id,
            'uid' => $location->uid,
            'code' => $location->code,
            'full_name' => $location->getFullName(),
            'summary' => $location->getSummary(),
        ]);
    }
}

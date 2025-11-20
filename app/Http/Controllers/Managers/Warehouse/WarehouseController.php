<?php

namespace App\Http\Controllers\Managers\Warehouse;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Http\Controllers\Controller;
use App\Models\Warehouse\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WarehouseController extends Controller
{
    /**
     * Listar todos los almacenes con búsqueda y filtros
     */
    public function index(Request $request)
    {
        $searchKey = $request->search ?? null;
        $available = $request->available ?? null;

        $warehouses = Warehouse::latest();

        if ($searchKey != null) {
            $warehouses = $warehouses->where('name', 'like', '%' . $searchKey . '%');
        }

        if ($available != null) {
            $warehouses = $warehouses->where('available', $available);
        }

        $warehouses = $warehouses->paginate(paginationNumber());

        return view('managers.views.warehouse.warehouses.index')->with([
            'warehouses' => $warehouses,
            'available' => $available,
            'searchKey' => $searchKey,
        ]);
    }

    /**
     * Mostrar formulario para crear nuevo almacén
     */
    public function create()
    {

        return view('managers.views.warehouse.warehouses.create')->with([
        ]);
    }

    /**
     * Guardar nuevo almacén
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:warehouses,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'available' => 'boolean',
        ]);

        $warehouse = Warehouse::create($validated);

        // Registrar en activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($warehouse)
            ->event('created')
            ->log('Almacén creado: ' . $warehouse->name);

        return redirect()->route('manager.warehouse.index')->with('success', 'Almacén creado exitosamente');

    }

    /**
     * Mostrar formulario para editar almacén
     */
    public function edit($uid)
    {
        $warehouse = Warehouse::uid($uid);

        return view('managers.views.warehouse.warehouses.edit')->with([
            'warehouse' => $warehouse,
        ]);
    }

    /**
     * Actualizar almacén
     */
    public function update(Request $request, $uid)
    {
        $warehouse = Warehouse::uid($uid);
        if (!$warehouse) {
            abort(404, 'Almacén no encontrado');
        }

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:warehouses,code,' . $warehouse->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'available' => 'boolean',
        ]);

        $oldData = $warehouse->only(['code', 'name', 'description', 'available']);

        $warehouse->update($validated);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($warehouse)
            ->event('updated')
            ->withProperties(['old' => $oldData, 'attributes' => $warehouse->getChanges()])
            ->log('Almacén actualizado: ' . $warehouse->name);

        return redirect()->route('manager.warehouse.index')->with('success', 'Almacén actualizado correctamente');
    }

    /**
     * Ver detalles de un almacén
     */
    public function view($uid)
    {
        $warehouse = Warehouse::uid($uid);
        if (!$warehouse) {
            abort(404, 'Almacén no encontrado');
        }

        $summary = [
            'total_floors' => $warehouse->locations()->distinct('floor_id')->count(),
            'total_locations' => $warehouse->locations()->count(),
            'total_slots' => $warehouse->locations()->withCount('slots')->get()->sum('slots_count'),
            'occupied_slots' => 0, // Calcular dinámicamente si es necesario
        ];

        return view('managers.views.warehouse.warehouses.view')->with([
            'warehouse' => $warehouse,
            'summary' => $summary,
        ]);
    }

    /**
     * Eliminar almacén
     * Jerarquía: Warehouse -> Floors -> Locations -> Inventory Slots
     */
    public function destroy($uid)
    {
        $warehouse = Warehouse::uid($uid);

        if (!$warehouse) {
            abort(404, 'Almacén no encontrado');
        }

        // Validar que no hay floors
        $totalFloors = $warehouse->floors()->count();
        if ($totalFloors > 0) {
            return response()->json([
                'status' => false,
                'message' => 'No se puede eliminar un almacén que contiene pisos. Primero debe eliminar todos los pisos.',
            ], 422);
        }

        // Registrar en activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($warehouse)
            ->event('deleted')
            ->log('Almacén eliminado: ' . $warehouse->name);

        // Eliminar el almacén (sin floors ni locations)
        $warehouse->delete();

        return redirect()->route('manager.warehouse.index')->with('success', 'Almacén eliminado exitosamente');
    }

    /**
     * Obtener miniaturas del almacén
     */
    public function getThumbnails($uid)
    {
        $warehouse = Warehouse::uid($uid);
        if (!$warehouse) {
            return response()->json([], 404);
        }

        $thumbnails = $warehouse->getMedia('thumbnail');

        if ($thumbnails->count() > 0) {
            $images = $thumbnails->map(function ($thumbnail) {
                return [
                    'id' => $thumbnail->id,
                    'uuid' => $thumbnail->uuid,
                    'name' => $thumbnail->name,
                    'file' => $thumbnail->file_name,
                    'path' => $thumbnail->getFullUrl(),
                    'size' => $thumbnail->size
                ];
            })->toArray();

            return response()->json($images);
        }

        return response()->json([]);
    }

    /**
     * Guardar miniatura para el almacén
     */
    public function storeThumbnails(Request $request)
    {
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $uid = Str::remove('"', $request->warehouse);
            $warehouse = Warehouse::uid($uid);
            if (!$warehouse) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Almacén no encontrado'
                ], 404);
            }

            $warehouse->addMediaFromRequest('file')->toMediaCollection('thumbnail');

            return response()->json([
                'status' => "success",
                'warehouse' => $warehouse->uid
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Archivo inválido'
        ], 400);
    }

    /**
     * Eliminar miniatura
     */
    public function deleteThumbnails($id)
    {
        Media::find($id)->delete();
        return response()->json(['status' => "success"]);
    }

    /**
     * Generar resumen/estadísticas del almacén (API)
     */
    public function getSummary($uid)
    {
        $warehouse = Warehouse::uid($uid);
        if (!$warehouse) {
            return response()->json(['error' => 'Almacén no encontrado'], 404);
        }

        return response()->json([
            'warehouse_id' => $warehouse->id,
            'warehouse_uid' => $warehouse->uid,
            'name' => $warehouse->name,
            'total_floors' => $warehouse->locations()->distinct('floor_id')->count(),
            'total_locations' => $warehouse->locations()->count(),
            'total_slots' => $warehouse->locations()->with('slots')->get()->sum(function ($location) {
                return $location->slots()->count();
            }),
            'occupied_slots' => $warehouse->locations()->with('slots')->get()->sum(function ($location) {
                return $location->slots()->where('is_occupied', true)->count();
            }),
        ]);
    }

}

<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseFloor;
use App\Models\Warehouse\WarehouseLocation;
use App\Models\Warehouse\WarehouseInventorySlot;
use App\Models\Warehouse\WarehouseLocationStyle;
use App\Models\Product\Product;
use App\Services\BarcodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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

        $locations = $locations->with(['floor', 'style', 'sections'])->paginate(paginationNumber());

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
        $warehouse = Warehouse::uid($warehouse_uid);
        $floor = WarehouseFloor::uid($floor_uid);
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();

        $slots = $location->slots()->paginate(paginationNumber());

        $summary = [
            'total_slots' => $location->getTotalSlots(),
            'occupied_slots' => $location->getOccupiedSlots(),
            'available_slots' => $location->getAvailableSlots(),
            'occupancy_percentage' => $location->getOccupancyPercentage(),
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
        $warehouse = Warehouse::uid($request->warehouse_uid);
        $floor = WarehouseFloor::uid($request->floor_uid);

        $validated = $request->validate([
            'warehouse_uid' => 'required|exists:warehouses,uid',
            'floor_uid' => 'required|exists:warehouse_floors,uid',
            'code' => 'required|string|max:50|unique:warehouse_locations,code,NULL,id,floor_id,' . $floor->id,
            'style_id' => 'required|exists:warehouse_location_styles,id',
            'position_x' => 'required|numeric|min:0',
            'position_y' => 'required|numeric|min:0',
            'available' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
            'sections' => 'required|array|min:1',
            'sections.*.code' => 'required|string|max:50',
            'sections.*.barcode' => 'nullable|string|max:100',
            'sections.*.face' => 'nullable|in:front,back,left,right',
            'sections.*.level' => 'required|integer|min:1',
        ]);

        // Get style to determine face requirements
        $style = WarehouseLocationStyle::findOrFail($validated['style_id']);
        $facesCount = count($style->faces ?? []);

        // Validate face field based on style
        foreach ($validated['sections'] as $index => $section) {
            if ($facesCount == 2 && empty($section['face'])) {
                throw new \Illuminate\Validation\ValidationException(
                    \Illuminate\Validation\Validator::make([], [])
                        ->addFailure("sections.$index.face", 'Face is required for 2-cara styles')
                );
            }
        }

        $location = WarehouseLocation::create([
            'uid' => Str::uuid(),
            'warehouse_id' => $warehouse->id,
            'floor_id' => $floor->id,
            'style_id' => $validated['style_id'],
            'code' => $validated['code'],
            'position_x' => $validated['position_x'],
            'position_y' => $validated['position_y'],
            'available' => $validated['available'] ?? true,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Create sections from the validated sections array
        foreach ($validated['sections'] as $sectionData) {
            // Generate barcode if not provided
            $barcode = $sectionData['barcode'] ?? BarcodeService::generateFromLocationAndSection(
                $location->code,
                $sectionData['code'],
                $sectionData['level']
            );

            $sectionCreate = [
                'code' => $sectionData['code'],
                'barcode' => $barcode,
                'level' => $sectionData['level'],
                'face' => $sectionData['face'] ?? null,
                'available' => true,
            ];

            $sectionCreate['uid'] = Str::uuid();

            $location->sections()->create($sectionCreate);
        }

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
        $warehouse = Warehouse::uid($warehouse_uid);
        $floor = WarehouseFloor::uid($floor_uid);
        $location = WarehouseLocation::uid($location_uid);
        $styles = WarehouseLocationStyle::available()->pluck('name', 'id');

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
        $warehouse = Warehouse::uid($request->warehouse_uid);
        $floor = WarehouseFloor::where('uid', $request->floor_uid)->where('warehouse_id', $warehouse->id)->firstOrFail();
        $location = WarehouseLocation::where('uid', $request->location_uid)->where('floor_id', $floor->id)->firstOrFail();

        $validated = $request->validate([
            'warehouse_uid' => 'required|exists:warehouses,uid',
            'floor_uid' => 'required|exists:warehouse_floors,uid',
            'location_uid' => 'required|exists:warehouse_locations,uid',
            'code' => 'required|string|max:50|unique:warehouse_locations,code,' . $location->id . ',id,floor_id,' . $floor->id,
            'style_id' => 'required|exists:warehouse_location_styles,id',
            'position_x' => 'required|numeric|min:0',
            'position_y' => 'required|numeric|min:0',
            'available' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
            'sections' => 'required|array|min:1',
            'sections.*.uid' => 'nullable|string',
            'sections.*.code' => 'required|string|max:50',
            'sections.*.barcode' => 'nullable|string|max:100',
            'sections.*.face' => 'nullable|in:front,back,left,right',
            'sections.*.level' => 'required|integer|min:1',
        ]);

        // Get style to determine face requirements (use new style if changed)
        $style = WarehouseLocationStyle::findOrFail($validated['style_id']);
        $facesCount = count($style->faces ?? []);

        // Validate face field based on style
        foreach ($validated['sections'] as $index => $section) {
            if ($facesCount == 2 && empty($section['face'])) {
                throw new \Illuminate\Validation\ValidationException(
                    \Illuminate\Validation\Validator::make([], [])
                        ->addFailure("sections.$index.face", 'Face is required for 2-cara styles')
                );
            }
        }

        $oldData = $location->only(['code', 'style_id', 'position_x', 'position_y', 'available', 'notes']);

        // Update location basic info
        $location->update([
            'code' => $validated['code'],
            'style_id' => $validated['style_id'],
            'position_x' => $validated['position_x'],
            'position_y' => $validated['position_y'],
            'available' => $validated['available'] ?? $location->available,
            'notes' => $validated['notes'] ?? $location->notes,
        ]);

        // Get existing section UIDs from the request
        $existingUids = collect($validated['sections'])
            ->pluck('uid')
            ->filter()
            ->toArray();

        // Delete sections that are no longer in the list
        $location->sections()
            ->whereNotIn('uid', $existingUids)
            ->delete();

        // Update or create sections
        foreach ($validated['sections'] as $sectionData) {
            // Generate barcode if not provided
            $barcode = $sectionData['barcode'] ?? BarcodeService::generateFromLocationAndSection(
                $location->code,
                $sectionData['code'],
                $sectionData['level']
            );

            if (!empty($sectionData['uid'])) {
                // Update existing section
                $section = $location->sections()->where('uid', $sectionData['uid'])->first();
                if ($section) {
                    $section->update([
                        'code' => $sectionData['code'],
                        'barcode' => $barcode,
                        'level' => $sectionData['level'],
                        'face' => $sectionData['face'] ?? null,
                    ]);
                }
            } else {
                // Create new section
                $sectionCreate = [
                    'code' => $sectionData['code'],
                    'barcode' => $barcode,
                    'level' => $sectionData['level'],
                    'face' => $sectionData['face'] ?? null,
                    'available' => true,
                ];

                $sectionCreate['uid'] = Str::uuid();
                $location->sections()->create($sectionCreate);
            }
        }

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
        $warehouse = Warehouse::uid($warehouse_uid);
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
        $warehouse = Warehouse::uid($warehouse_uid);
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
        $warehouse = Warehouse::uid($warehouse_uid);
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
        $warehouse = Warehouse::uid($warehouse_uid);
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

    /**
     * Print barcodes for all sections of a location
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/{location_uid}/print-barcodes
     */
    public function printBarcodes($warehouse_uid, $floor_uid, $location_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid);
        $floor = WarehouseFloor::uid($floor_uid);
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();

        // Get all sections for this location
        $sections = $location->sections()->get();

        return view('managers.views.warehouse.locations.print-barcodes')->with([
            'warehouse' => $warehouse,
            'floor' => $floor,
            'location' => $location,
            'sections' => $sections,
        ]);
    }

    /**
     * Print barcodes for all locations in a floor
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/print-all-barcodes
     */
    public function printAllBarcodes($warehouse_uid, $floor_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid);
        $floor = WarehouseFloor::uid($floor_uid);

        // Get all sections for all locations in this floor
        $locations = $floor->locations()->with('sections')->get();

        return view('managers.views.warehouse.locations.print-all-barcodes')->with([
            'warehouse' => $warehouse,
            'floor' => $floor,
            'locations' => $locations,
        ]);
    }

    /**
     * Mostrar formulario para trasladar ubicación a otro piso
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/{location_uid}/transfer
     */
    public function transfer($warehouse_uid, $floor_uid, $location_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid);
        $floor = WarehouseFloor::uid($floor_uid);
        $location = WarehouseLocation::where('uid', $location_uid)->where('floor_id', $floor->id)->firstOrFail();

        // Obtener todos los pisos del almacén excepto el actual
        $availableFloors = WarehouseFloor::where('warehouse_id', $warehouse->id)
            ->where('id', '!=', $floor->id)
            ->available()
            ->ordered()
            ->get();

        return view('managers.views.warehouse.locations.transfer')->with([
            'warehouse' => $warehouse,
            'floor' => $floor,
            'location' => $location,
            'availableFloors' => $availableFloors,
        ]);
    }

    /**
     * Ejecutar el traslado de una o múltiples ubicaciones a otro piso
     * Ruta: POST /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/transfer
     */
    public function transferSubmit(Request $request)
    {
        $warehouse = Warehouse::uid($request->warehouse_uid);
        $floor = WarehouseFloor::uid($request->floor_uid);
        $location = WarehouseLocation::where('uid', $request->location_uid)
            ->where('floor_id', $floor->id)
            ->firstOrFail();

        $validated = $request->validate([
            'warehouse_uid' => 'required|exists:warehouses,uid',
            'floor_uid' => 'required|exists:warehouse_floors,uid',
            'location_uid' => 'required|string|exists:warehouse_locations,uid',
            'target_floor_uid' => 'required|exists:warehouse_floors,uid',
        ]);

        // Obtener el piso destino
        $targetFloor = WarehouseFloor::where('uid', $validated['target_floor_uid'])
            ->where('warehouse_id', $warehouse->id)
            ->firstOrFail();

        // Verificar que no exista otra ubicación con el mismo código en el piso destino
        $existingLocation = WarehouseLocation::where('code', $location->code)
            ->where('floor_id', $targetFloor->id)
            ->first();

        if ($existingLocation) {
            return redirect()->back()->with('error', "Ya existe una ubicación con el código '{$location->code}' en el piso destino");
        }

        // Trasladar la ubicación
        $location->update(['floor_id' => $targetFloor->id]);

        // Registrar en el activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($location)
            ->event('transferred')
            ->withProperties([
                'old' => ['floor_id' => $floor->id, 'floor_code' => $floor->code],
                'attributes' => ['floor_id' => $targetFloor->id, 'floor_code' => $targetFloor->code]
            ])
            ->log('Ubicación trasladada: ' . $location->code . ' de ' . $floor->name . ' a ' . $targetFloor->name);

        return redirect()->route('manager.warehouse.locations', [
            'warehouse_uid' => $warehouse->uid,
            'floor_uid' => $targetFloor->uid
        ])->with('success', "Ubicación '{$location->code}' trasladada exitosamente a " . $targetFloor->name);
    }

    /**
     * API: Obtener pisos disponibles para trasladar
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/{location_uid}/api/available-floors
     */
    public function getAvailableFloorsForTransfer($warehouse_uid, $floor_uid, $location_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid);
        $floor = WarehouseFloor::uid($floor_uid);

        $availableFloors = WarehouseFloor::where('warehouse_id', $warehouse->id)
            ->where('id', '!=', $floor->id)
            ->available()
            ->ordered()
            ->get()
            ->map(fn($f) => [
                'uid' => $f->uid,
                'id' => $f->id,
                'code' => $f->code,
                'name' => $f->name,
            ]);

        return response()->json([
            'success' => true,
            'floors' => $availableFloors,
        ]);
    }

    /**
     * Mostrar formulario para trasladar múltiples ubicaciones a otro piso
     * Ruta: GET /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/transfer/bulk
     */
    public function transferBulkForm($warehouse_uid, $floor_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid);
        $floor = WarehouseFloor::uid($floor_uid);

        // Obtener todos los pisos del almacén excepto el actual
        $availableFloors = WarehouseFloor::where('warehouse_id', $warehouse->id)
            ->where('id', '!=', $floor->id)
            ->available()
            ->ordered()
            ->get();

        return view('managers.views.warehouse.locations.transfer-bulk')->with([
            'warehouse' => $warehouse,
            'floor' => $floor,
            'availableFloors' => $availableFloors,
        ]);
    }

    /**
     * Ejecutar el traslado de múltiples ubicaciones a otro piso
     * Ruta: POST /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/transfer/bulk
     */
    public function transferBulkSubmit(Request $request)
    {
        $warehouse = Warehouse::uid($request->warehouse_uid);
        $floor = WarehouseFloor::uid($request->floor_uid);

        $validated = $request->validate([
            'warehouse_uid' => 'required|exists:warehouses,uid',
            'floor_uid' => 'required|exists:warehouse_floors,uid',
            'location_uids' => 'required|array|min:1',
            'location_uids.*' => 'required|string|exists:warehouse_locations,uid',
            'target_floor_uid' => 'required|exists:warehouse_floors,uid',
        ]);

        // Obtener el piso destino
        $targetFloor = WarehouseFloor::where('uid', $validated['target_floor_uid'])
            ->where('warehouse_id', $warehouse->id)
            ->firstOrFail();

        // Obtener ubicaciones a trasladar
        $locations = WarehouseLocation::whereIn('uid', $validated['location_uids'])
            ->where('floor_id', $floor->id)
            ->get();

        if ($locations->isEmpty()) {
            return redirect()->back()->with('error', 'No se encontraron ubicaciones para trasladar');
        }

        $failedLocations = [];
        $successCount = 0;

        foreach ($locations as $location) {
            // Verificar que no exista otra ubicación con el mismo código en el piso destino
            $existingLocation = WarehouseLocation::where('code', $location->code)
                ->where('floor_id', $targetFloor->id)
                ->first();

            if ($existingLocation) {
                $failedLocations[] = $location->code;
                continue;
            }

            // Trasladar la ubicación
            $location->update(['floor_id' => $targetFloor->id]);

            // Registrar en el activity log
            activity()
                ->causedBy(auth()->user())
                ->performedOn($location)
                ->event('transferred')
                ->withProperties([
                    'old' => ['floor_id' => $floor->id, 'floor_code' => $floor->code],
                    'attributes' => ['floor_id' => $targetFloor->id, 'floor_code' => $targetFloor->code]
                ])
                ->log('Ubicación trasladada: ' . $location->code . ' de ' . $floor->name . ' a ' . $targetFloor->name);

            $successCount++;
        }

        $message = "Se trasladaron {$successCount} ubicación(es) exitosamente a " . $targetFloor->name;
        $messageType = 'success';

        if (!empty($failedLocations)) {
            $message .= '. No se pudieron trasladar: ' . implode(', ', $failedLocations) . ' (códigos duplicados en destino)';
            $messageType = $successCount > 0 ? 'warning' : 'error';
        }

        return redirect()->route('manager.warehouse.locations', [
            'warehouse_uid' => $warehouse->uid,
            'floor_uid' => $targetFloor->uid
        ])->with($messageType, $message);
    }

    /**
     * API: Obtener detalles completos de una ubicación para el modal del mapa
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/{location_uid}/api/details
     */
    public function getLocationDetails($warehouse_uid, $floor_uid, $location_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid);
        $floor = WarehouseFloor::uid($floor_uid);
        $location = WarehouseLocation::where('uid', $location_uid)
            ->where('floor_id', $floor->id)
            ->with(['style', 'sections' => function($query) {
                $query->orderBy('level')->orderBy('face');
            }])
            ->firstOrFail();

        // Agrupar secciones por cara
        $sectionsByFace = $location->sections->groupBy('face')->map(function($sections) {
            return $sections->map(function($section) {
                return [
                    'uid' => $section->uid,
                    'code' => $section->code,
                    'barcode' => $section->barcode,
                    'level' => $section->level,
                    'face' => $section->face,
                    'available' => $section->available,
                ];
            })->values();
        });

        return response()->json([
            'success' => true,
            'location' => [
                'uid' => $location->uid,
                'code' => $location->code,
                'name' => $location->getFullName(),
                'position_x' => $location->position_x,
                'position_y' => $location->position_y,
                'available' => $location->available,
                'notes' => $location->notes,
            ],
            'style' => [
                'name' => $location->style->name,
                'code' => $location->style->code,
                'faces' => $location->style->faces ?? [],
                'faces_count' => count($location->style->faces ?? []),
            ],
            'sections_by_face' => $sectionsByFace,
            'floor' => [
                'name' => $floor->name,
                'code' => $floor->code,
            ],
            'warehouse' => [
                'name' => $warehouse->name,
            ],
        ]);
    }

    /**
     * API: Obtener detalles de una sección específica
     * Ruta: /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/{location_uid}/sections/{section_uid}/api/details
     */
    public function getSectionDetails($warehouse_uid, $floor_uid, $location_uid, $section_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid);
        $floor = WarehouseFloor::uid($floor_uid);
        $location = WarehouseLocation::where('uid', $location_uid)
            ->where('floor_id', $floor->id)
            ->firstOrFail();

        $section = $location->sections()
            ->where('uid', $section_uid)
            ->with(['slots.product'])
            ->firstOrFail();

        // Obtener información de los slots
        $slotsInfo = $section->slots->map(function($slot) {
            return [
                'uid' => $slot->uid,
                'barcode' => $slot->barcode,
                'is_occupied' => $slot->is_occupied,
                'quantity' => $slot->quantity,
                'max_quantity' => $slot->max_quantity,
                'weight_current' => $slot->weight_current,
                'weight_max' => $slot->weight_max,
                'product' => $slot->product ? [
                    'title' => $slot->product->title,
                    'sku' => $slot->product->sku,
                    'barcode' => $slot->product->barcode,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'section' => [
                'uid' => $section->uid,
                'code' => $section->code,
                'barcode' => $section->barcode,
                'level' => $section->level,
                'face' => $section->face,
                'available' => $section->available,
                'max_quantity' => $section->max_quantity,
                'notes' => $section->notes,
            ],
            'location' => [
                'code' => $location->code,
                'name' => $location->getFullName(),
            ],
            'slots' => $slotsInfo,
            'slots_count' => $slotsInfo->count(),
            'occupied_slots' => $slotsInfo->where('is_occupied', true)->count(),
        ]);
    }

    /**
     * API: Obtener detalles de un estilo por ID
     * Ruta: /manager/warehouse/locations/api/style/{style_id}
     */
    public function getStyleDetails($style_id)
    {
        $style = WarehouseLocationStyle::findOrFail($style_id);

        return response()->json([
            'success' => true,
            'id' => $style->id,
            'code' => $style->code,
            'name' => $style->name,
            'faces' => $style->faces ?? [],
            'faces_count' => count($style->faces ?? []),
            'default_levels' => $style->default_levels,
            'default_sections' => $style->default_sections,
        ]);
    }
}

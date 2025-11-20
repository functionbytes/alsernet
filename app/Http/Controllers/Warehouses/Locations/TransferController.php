<?php

namespace App\Http\Controllers\Warehouses\Locations;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Models\Warehouse\WarehouseInventorySlot;
use App\Models\Warehouse\WarehouseLocationSection;
use App\Services\Inventories\BarcodeReadingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransferController extends Controller
{
    protected BarcodeReadingService $barcodeService;

    public function __construct(BarcodeReadingService $barcodeService)
    {
        $this->barcodeService = $barcodeService;
    }

    /**
     * Mostrar página de transferencia de productos
     */
    public function index()
    {
        $warehouses = \App\Models\Warehouse\Warehouse::with('floors.locations.sections')->get();

        return view('warehouses.views.warehouse.transfers.index', [
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Buscar producto por código de barras o ID
     */
    public function searchProduct(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:1',
        ]);

        $search = $request->input('search');

        // Intentar búsqueda por barcode
        $product = Product::where('barcode', $search)
            ->orWhere('reference', $search)
            ->orWhere('title', 'like', "%$search%")
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
            ], 404);
        }

        // Obtener slots del producto agrupados por sección
        $slots = WarehouseInventorySlot::where('product_id', $product->id)
            ->occupied()
            ->with('section.location.floor.warehouse')
            ->get();

        if ($slots->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'El producto no tiene stock en ninguna sección',
            ]);
        }

        // Agrupar por sección
        $locations = [];
        foreach ($slots as $slot) {
            $section = $slot->section;
            $location = $section->location;
            $warehouse = $location->warehouse;

            $key = $location->id;

            if (!isset($locations[$key])) {
                $locations[$key] = [
                    'location_id' => $location->id,
                    'location_code' => $location->code,
                    'warehouse_id' => $warehouse->id,
                    'warehouse_name' => $warehouse->name,
                    'sections' => [],
                ];
            }

            $locations[$key]['sections'][] = [
                'section_id' => $section->id,
                'section_code' => $section->code,
                'section_level' => $section->level,
                'section_face' => $section->face,
                'quantity' => $slot->quantity,
                'uid' => $slot->uid,
            ];
        }

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'uid' => $product->uid,
                'title' => $product->title,
                'reference' => $product->reference,
                'barcode' => $product->barcode,
            ],
            'locations' => array_values($locations),
        ]);
    }

    /**
     * Obtener secciones disponibles para transferencia
     */
    public function getAvailableSections(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:warehouse_locations,id',
            'exclude_section_id' => 'nullable|integer',
        ]);

        $locationId = $request->input('location_id');
        $excludeSectionId = $request->input('exclude_section_id');

        $sections = WarehouseLocationSection::where('location_id', $locationId)
            ->where('available', true);

        if ($excludeSectionId) {
            $sections = $sections->where('id', '!=', $excludeSectionId);
        }

        $sections = $sections->with('slots')
            ->get()
            ->map(function ($section) {
                return [
                    'id' => $section->id,
                    'code' => $section->code,
                    'level' => $section->level,
                    'face' => $section->face,
                    'total_quantity' => $section->getTotalQuantity(),
                    'max_quantity' => $section->max_quantity,
                    'available_slots' => $section->getAvailableSlots(),
                ];
            });

        return response()->json([
            'success' => true,
            'sections' => $sections,
        ]);
    }

    /**
     * Realizar transferencia de producto
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_section_id' => 'required|exists:warehouse_location_sections,id',
            'to_section_id' => 'required|exists:warehouse_location_sections,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $productId = $request->input('product_id');
        $fromSectionId = $request->input('from_section_id');
        $toSectionId = $request->input('to_section_id');
        $quantity = $request->input('quantity');

        // Validaciones
        if ($fromSectionId === $toSectionId) {
            return response()->json([
                'success' => false,
                'message' => 'Las secciones origen y destino no pueden ser iguales',
            ], 422);
        }

        // Obtener secciones
        $fromSection = WarehouseLocationSection::findOrFail($fromSectionId);
        $toSection = WarehouseLocationSection::findOrFail($toSectionId);

        // Validar que pertenezcan a la misma ubicación
        if ($fromSection->location_id !== $toSection->location_id) {
            return response()->json([
                'success' => false,
                'message' => 'Las secciones deben estar en la misma estantería',
            ], 422);
        }

        // Obtener slot origen
        $fromSlot = WarehouseInventorySlot::where('section_id', $fromSectionId)
            ->where('product_id', $productId)
            ->first();

        if (!$fromSlot) {
            return response()->json([
                'success' => false,
                'message' => 'El producto no existe en la sección origen',
            ], 404);
        }

        // Validar cantidad
        if ($fromSlot->quantity < $quantity) {
            return response()->json([
                'success' => false,
                'message' => "No hay suficiente cantidad. Disponible: {$fromSlot->quantity}",
            ], 422);
        }

        try {
            // Validar capacidad en destino
            $toSection = WarehouseLocationSection::find($toSectionId);
            if ($toSection->max_quantity) {
                $currentQuantity = $toSection->getTotalQuantity();
                if (($currentQuantity + $quantity) > $toSection->max_quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => "La sección destino no tiene capacidad. Máximo: {$toSection->max_quantity}, Actual: {$currentQuantity}",
                    ], 422);
                }
            }

            // Realizar transferencia
            $fromSlot->moveTo(
                $toSection,
                $quantity,
                'Transferencia de sección',
                auth()->id()
            );

            // Log de auditoría
            Log::channel('inventory')->info('Transferencia de producto realizada', [
                'product_id' => $productId,
                'from_section_id' => $fromSectionId,
                'to_section_id' => $toSectionId,
                'quantity' => $quantity,
                'user_id' => auth()->id(),
                'timestamp' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Transferencia exitosa: {$quantity} unidades movidas",
                'transfer_info' => [
                    'from_section' => $fromSection->code,
                    'to_section' => $toSection->code,
                    'quantity' => $quantity,
                    'timestamp' => now(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error en transferencia de producto', [
                'product_id' => $productId,
                'from_section_id' => $fromSectionId,
                'to_section_id' => $toSectionId,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la transferencia',
            ], 500);
        }
    }

    /**
     * Obtener historial de transferencias
     */
    public function history(Request $request)
    {
        $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $daysAgo = $request->input('days', 30);
        $productId = $request->input('product_id');

        $movements = \App\Models\Warehouse\WarehouseInventoryMovement::where('movement_type', 'move')
            ->where('recorded_at', '>=', now()->subDays($daysAgo));

        if ($productId) {
            $movements = $movements->where('product_id', $productId);
        }

        $movements = $movements->with('product', 'user')
            ->orderBy('recorded_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'movements' => $movements,
        ]);
    }
}

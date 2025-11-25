<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseFloor;
use App\Models\Warehouse\WarehouseLocation;
use App\Models\Warehouse\WarehouseLocationStyle;
use App\Models\Warehouse\WarehouseInventorySlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseMapController extends Controller
{
    /**
     * Display the warehouse interactive map
     */
    public function map($warehouse_uid)
    {
        $warehouse = Warehouse::uid($warehouse_uid);
        $floors = WarehouseFloor::where('warehouse_id', $warehouse->id)->available()->ordered()->with('locations')->get();
        $standStyles = WarehouseLocationStyle::available()->with('locations')->get();

        return view('managers.views.warehouse.map.index', [
            'warehouse' => $warehouse,
            'warehouse_uid' => $warehouse_uid,
            'floors' => $floors,
            'standStyles' => $standStyles,
        ]);
    }

    /**
     * API endpoint: Get layout specification from database
     * Returns JSON compatible with the SVG drawing logic
     */
    public function getLayoutSpec(Request $request): JsonResponse
    {
        $floorId = $request->query('floor_id');

        // Load all stands with their relationships
        $query = WarehouseLocation::with(['floor', 'style', 'slots.product']);

        if ($floorId) {
            $query->where('floor_id', $floorId);
        }

        $stands = $query->orderBy('position_x', 'asc')
            ->orderBy('position_y', 'asc')
            ->get();

        // Transform stands to layout spec format
        $layoutSpec = $this->transformStandsToLayoutSpec($stands);

        return response()->json([
            'success' => true,
            'layoutSpec' => $layoutSpec,
            'metadata' => [
                'totalStands' => count($stands),
                'totalFloors' => WarehouseFloor::count(),
            ],
        ]);
    }

    /**
     * Transform database stands to layout specification format
     *
     * @param \Illuminate\Database\Eloquent\Collection $stands
     * @return array
     */
    private function transformStandsToLayoutSpec($stands): array
    {
        $layoutSpec = [];

        foreach ($stands as $stand) {
            // Group inventory slots by face and create location data
            $itemLocations = $this->buildItemLocations($stand);

            $layoutItem = [
                'id' => $stand->code,
                'uid' => $stand->uid,  // Agregar UID para poder identificar la ubicación
                'floors' => [$stand->floor_id],
                'kind' => 'row', // Simplified - can be enhanced
                'anchor' => 'top-right',
                'start' => [
                    'offsetRight_m' => (float)$stand->position_x ?? 0,
                    'offsetTop_m' => (float)$stand->position_y ?? 0,
                ],
                'shelf' => [
                    'w_m' => (float)($stand->style?->width ?? 1.0),
                    'h_m' => (float)($stand->style?->height ?? 1.0),
                ],
                'count' => 1,
                'direction' => 'left',
                'gaps' => [
                    'between_m' => 0,
                ],
                'label' => [
                    'pattern' => 'P{floor}-' . $stand->code,
                ],
                'nameTemplate' => $stand->code,
                'color' => $this->getStandColorClass($stand),
                'style_type' => $stand->style?->type ?? 'row',
                'style_faces' => $stand->style?->faces ?? ['front'],
                'available' => $stand->available,
                'occupancy_percentage' => round($stand->getOccupancyPercentage(), 2),
                'exportEdges' => false,
                'itemLocationsByIndex' => [
                    1 => $itemLocations,
                ],
            ];

            $layoutSpec[] = $layoutItem;
        }

        return $layoutSpec;
    }

    /**
     * Build item locations (inventory slots grouped by face)
     *
     * @param WarehouseLocation $stand
     * @return array
     */
    private function buildItemLocations($stand): array
    {
        $locations = [];

        // Get all slots for this location through sections
        $slots = WarehouseInventorySlot::whereHas('section', function ($query) use ($stand) {
            $query->where('location_id', $stand->id);
        })
        ->with(['product', 'section'])
        ->orderBy('id', 'asc')
        ->get();

        // Group by face (from section)
        $slotsByFace = $slots->groupBy(function ($slot) {
            return $slot->section?->face ?? 'front';
        });

        foreach (['left', 'right', 'front', 'back'] as $face) {
            $faceSlots = $slotsByFace->get($face, collect());

            if ($faceSlots->isNotEmpty()) {
                $locations[$face] = $faceSlots->map(function ($slot) {
                    $section = $slot->section;
                    return [
                        'code' => $section?->barcode ?? sprintf('SLOT-%d-%d', $slot->id, $section?->id ?? 0),
                        'color' => $this->getSlotColorByOccupancy($slot),
                    ];
                })->values()->all();
            }
        }

        return $locations;
    }

    /**
     * Determine shelf color class based on stand status/occupancy
     *
     * @param WarehouseLocation $stand
     * @return string
     */
    private function getStandColorClass($stand): string
    {
        if (!$stand->available) {
            return 'shelf--gris';
        }

        $occupancyPct = $stand->getOccupancyPercentage();

        if ($occupancyPct < 25) {
            return 'shelf--azul';
        } elseif ($occupancyPct < 50) {
            return 'shelf--verde';
        } elseif ($occupancyPct < 75) {
            return 'shelf--ambar';
        } else {
            return 'shelf--rojo';
        }
    }

    /**
     * Determine slot color based on occupancy status
     *
     * @param WarehouseInventorySlot $slot
     * @return string
     */
    private function getSlotColorByOccupancy($slot): string
    {
        if (!$slot->is_occupied) {
            return 'shelf--gris';
        }

        if ($slot->weight_max && $slot->weight_current) {
            $weightPct = ($slot->weight_current / $slot->weight_max) * 100;
            if ($weightPct >= 90) {
                return 'shelf--rojo';
            } elseif ($weightPct >= 70) {
                return 'shelf--ambar';
            }
        }

        if ($slot->max_quantity && $slot->quantity) {
            $qtyPct = ($slot->quantity / $slot->max_quantity) * 100;
            if ($qtyPct >= 90) {
                return 'shelf--rojo';
            } elseif ($qtyPct >= 70) {
                return 'shelf--ambar';
            }
        }

        return 'shelf--verde';
    }

    /**
     * Get warehouse dimensions and configuration
     */
    public function getWarehouseConfig(): JsonResponse
    {
        return response()->json([
            'warehouse' => [
                'width_m' => 42.23,
                'height_m' => 30.26,
            ],
            'scale' => 30,
            'floors' => WarehouseFloor::available()
                ->ordered()
                ->select('id', 'code', 'name')
                ->get()
                ->map(fn($floor) => [
                    'id' => $floor->id,
                    'code' => $floor->code,
                    'name' => $floor->name,
                    'number' => $floor->id,
                ])
                ->values(),
        ]);
    }

    /**
     * Get detailed slot information for modal
     */
    public function getSlotDetails($uid): JsonResponse
    {
        $slot = WarehouseInventorySlot::where('uid', $uid)
            ->with(['location.floor', 'location.style', 'product'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'slot' => [
                'uid' => $slot->uid,
                'barcode' => $slot->barcode,
                'address' => $slot->getAddress(),
                'is_occupied' => $slot->is_occupied,
                'product' => $slot->product ? [
                    'id' => $slot->product->id,
                    'title' => $slot->product->title,
                    'barcode' => $slot->product->barcode,
                ] : null,
                'quantity' => [
                    'current' => $slot->quantity,
                    'max' => $slot->max_quantity,
                    'available' => $slot->getAvailableQuantity(),
                    'percentage' => $slot->getQuantityPercentage(),
                ],
                'weight' => [
                    'current' => round($slot->weight_current, 2),
                    'max' => $slot->weight_max ? round($slot->weight_max, 2) : null,
                    'available' => round($slot->getAvailableWeight(), 2),
                    'percentage' => $slot->getWeightPercentage(),
                ],
                'last_movement' => $slot->last_movement?->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseInventoryMovement;
use App\Models\Warehouse\WarehouseInventorySlot;
use Illuminate\Http\Request;

class WarehouseDashboardController extends Controller
{
    /**
     * Vista principal del dashboard de almacén
     */
    public function dashboard(Request $request)
    {
        $warehouseId = $request->warehouse_id ?? null;

        // Obtener almacenes
        $warehouses = Warehouse::available()->get();
        $selectedWarehouse = null;

        if ($warehouseId) {
            $selectedWarehouse = Warehouse::find($warehouseId);
        } else if ($warehouses->count() > 0) {
            $selectedWarehouse = $warehouses->first();
        }

        $statistics = $this->getWarehouseStatistics($selectedWarehouse);
        $recentMovements = $this->getRecentMovements($selectedWarehouse);
        $alerts = $this->getAlerts($selectedWarehouse);

        return view('managers.views.warehouse.dashboard')->with([
            'warehouses' => $warehouses,
            'selectedWarehouse' => $selectedWarehouse,
            'statistics' => $statistics,
            'recentMovements' => $recentMovements,
            'alerts' => $alerts,
        ]);
    }

    /**
     * Vista de resumen con filtros
     */
    public function resume(Request $request)
    {
        $filters = [
            'warehouse' => $request->warehouse ?? null,
            'floor' => $request->floor ?? null,
            'search' => $request->search ?? null,
            'occupancy_status' => $request->occupancy_status ?? null,
        ];

        $warehouses = Warehouse::available()->pluck('title', 'id');
        $warehouses->prepend('Todos', '0');

        return view('managers.views.warehouse.resume.index')->with([
            'warehouses' => $warehouses,
            'filters' => $filters,
        ]);
    }

    /**
     * Generar datos de resumen (AJAX)
     */
    public function generate(Request $request)
    {
        $filters = [
            'warehouse' => $request->warehouse ?? null,
            'floor' => $request->floor ?? null,
            'search' => $request->search ?? null,
            'occupancy_status' => $request->occupancy_status ?? null,
        ];

        $query = WarehouseInventorySlot::with(['location', 'product']);

        // Filtro por almacén
        if ($filters['warehouse'] && $filters['warehouse'] != '0') {
            $query->whereHas('location', function ($q) use ($filters) {
                $q->where('warehouse_id', $filters['warehouse']);
            });
        }

        // Filtro por piso
        if ($filters['floor']) {
            $query->whereHas('location', function ($q) use ($filters) {
                $q->where('floor_id', $filters['floor']);
            });
        }

        // Filtro por búsqueda
        if ($filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('barcode', 'like', '%' . $filters['search'] . '%')
                    ->orWhereHas('product', function ($subQ) use ($filters) {
                        $subQ->where('title', 'like', '%' . $filters['search'] . '%')
                            ->orWhere('reference', 'like', '%' . $filters['search'] . '%');
                    });
            });
        }

        // Filtro por estado de ocupancia
        if ($filters['occupancy_status']) {
            if ($filters['occupancy_status'] === 'occupied') {
                $query->where('is_occupied', true);
            } elseif ($filters['occupancy_status'] === 'available') {
                $query->where('is_occupied', false);
            }
        }

        $slots = $query->paginate(100);

        $summary = [
            'total_slots' => $query->count(),
            'occupied_slots' => $query->where('is_occupied', true)->count(),
            'available_slots' => $query->where('is_occupied', false)->count(),
            'occupancy_percentage' => round(($query->where('is_occupied', true)->count() / max($query->count(), 1)) * 100, 2),
        ];

        return view('managers.views.warehouse.resume.resume')->with([
            'slots' => $slots,
            'summary' => $summary,
            'filters' => $filters,
        ]);
    }

    /**
     * API: Obtener estadísticas de almacén
     */
    public function getStatistics(Request $request)
    {
        $warehouseId = $request->warehouse_id ?? null;

        $warehouse = null;
        if ($warehouseId) {
            $warehouse = Warehouse::find($warehouseId);
        }

        $statistics = $this->getWarehouseStatistics($warehouse);

        return response()->json($statistics);
    }

    /**
     * API: Obtener almacenes para filtro
     */
    public function getWarehouses()
    {
        $warehouses = Warehouse::available()
            ->get()
            ->map(function ($warehouse) {
                return [
                    'id' => $warehouse->id,
                    'uid' => $warehouse->uid,
                    'title' => $warehouse->title,
                ];
            });

        return response()->json($warehouses);
    }

    /**
     * API: Obtener pisos para un almacén
     */
    public function getFloors(Request $request)
    {
        $warehouseId = $request->warehouse_id;

        $floors = \App\Models\Warehouse\WarehouseFloor::where('warehouse_id', $warehouseId)
            ->available()
            ->get()
            ->map(function ($floor) {
                return [
                    'id' => $floor->id,
                    'uid' => $floor->uid,
                    'name' => $floor->name,
                ];
            });

        return response()->json($floors);
    }

    /**
     * Calcular estadísticas de un almacén
     */
    protected function getWarehouseStatistics($warehouse = null)
    {
        if (!$warehouse) {
            return [
                'total_warehouses' => Warehouse::count(),
                'total_locations' => 0,
                'total_slots' => 0,
                'occupied_slots' => 0,
                'available_slots' => 0,
                'occupancy_percentage' => 0,
                'total_weight' => 0,
                'avg_occupancy_per_location' => 0,
            ];
        }

        $locations = $warehouse->locations()->with('slots')->get();

        $totalSlots = $locations->sum(function ($location) {
            return $location->slots()->count();
        });

        $occupiedSlots = $locations->sum(function ($location) {
            return $location->slots()->where('is_occupied', true)->count();
        });

        $totalWeight = $locations->sum(function ($location) {
            return $location->slots()->sum('weight_current');
        });

        return [
            'warehouse_id' => $warehouse->id,
            'warehouse_uid' => $warehouse->uid,
            'title' => $warehouse->title,
            'total_locations' => $locations->count(),
            'total_slots' => $totalSlots,
            'occupied_slots' => $occupiedSlots,
            'available_slots' => $totalSlots - $occupiedSlots,
            'occupancy_percentage' => $totalSlots > 0 ? round(($occupiedSlots / $totalSlots) * 100, 2) : 0,
            'total_weight' => round($totalWeight, 2),
            'avg_occupancy_per_location' => $locations->count() > 0
                ? round(($occupiedSlots / $locations->count()) / max($totalSlots / $locations->count(), 1) * 100, 2)
                : 0,
        ];
    }

    /**
     * Obtener movimientos recientes
     */
    protected function getRecentMovements($warehouse = null, $limit = 10)
    {
        $query = WarehouseInventoryMovement::with(['slot', 'product', 'user'])
            ->latest('recorded_at');

        if ($warehouse) {
            $query->where('warehouse_id', $warehouse->id);
        }

        return $query->limit($limit)->get()->map(function ($movement) {
            return [
                'id' => $movement->id,
                'uid' => $movement->uid,
                'type' => $movement->getTypeLabel(),
                'slot_address' => $movement->slot ? $movement->slot->getAddress() : 'N/A',
                'product' => $movement->product ? $movement->product->title : 'N/A',
                'quantity_delta' => $movement->quantity_delta,
                'weight_delta' => $movement->weight_delta,
                'user' => $movement->user ? $movement->user->name : 'Sistema',
                'recorded_at' => $movement->recorded_at->format('Y-m-d H:i:s'),
            ];
        });
    }

    /**
     * Obtener alertas (slots con capacidad cercana al límite)
     */
    protected function getAlerts($warehouse = null, $limit = 15)
    {
        $query = WarehouseInventorySlot::with(['location', 'product']);

        if ($warehouse) {
            $query->whereHas('location', function ($q) use ($warehouse) {
                $q->where('warehouse_id', $warehouse->id);
            });
        }

        // Slots con ocupancia superior al 80%
        $alerts = $query->get()->filter(function ($slot) {
            return $slot->getQuantityPercentage() > 80 || $slot->getWeightPercentage() > 80;
        })->map(function ($slot) {
            return [
                'type' => 'capacity_warning',
                'level' => $slot->getQuantityPercentage() > 95 ? 'danger' : 'warning',
                'message' => 'Slot ' . $slot->getAddress() . ' al ' . round($slot->getQuantityPercentage(), 1) . '% de capacidad',
                'slot' => $slot,
            ];
        });

        return $alerts->take($limit)->values();
    }
}

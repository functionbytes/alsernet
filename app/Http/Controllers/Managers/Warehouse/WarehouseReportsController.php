<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseInventoryMovement;
use App\Models\Warehouse\WarehouseInventorySlot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseReportsController extends Controller
{
    /**
     * Mostrar formulario para generar reportes
     */
    public function report()
    {
        $warehouses = Warehouse::available()->pluck('title', 'id');
        $warehouses->prepend('Todos', '0');

        return view('managers.views.warehouse.reports.index')->with([
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Generar reporte de inventario
     */
    public function generateInventory(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'format' => 'required|in:excel,pdf,csv',
        ]);

        $query = WarehouseInventorySlot::with(['location', 'product', 'lastInventarie']);

        if ($validated['warehouse_id'] ?? null) {
            $query->whereHas('location', function ($q) use ($validated) {
                $q->where('warehouse_id', $validated['warehouse_id']);
            });
        }

        if ($validated['date_from'] ?? null) {
            $query->where('created_at', '>=', $validated['date_from']);
        }

        if ($validated['date_to'] ?? null) {
            $query->where('created_at', '<=', $validated['date_to']);
        }

        $slots = $query->get();

        $data = $slots->map(function ($slot) {
            return [
                'warehouse' => $slot->location->warehouse->title,
                'floor' => $slot->location->floor->name ?? 'N/A',
                'location_code' => $slot->location->code,
                'location_address' => $slot->location->getFullName(),
                'slot_address' => $slot->getAddress(),
                'product' => $slot->product->title ?? 'N/A',
                'reference' => $slot->product->reference ?? 'N/A',
                'barcode' => $slot->barcode,
                'quantity' => $slot->quantity,
                'max_quantity' => $slot->max_quantity,
                'weight_current' => $slot->weight_current,
                'weight_max' => $slot->weight_max,
                'occupancy' => $slot->quantity . '/' . $slot->max_quantity,
                'is_occupied' => $slot->is_occupied ? 'Ocupado' : 'Disponible',
                'last_movement' => $slot->last_movement ? $slot->last_movement->format('Y-m-d H:i:s') : 'N/A',
            ];
        });

        if ($validated['format'] === 'excel') {
            return $this->exportToExcel($data, 'Inventario_Almacén_' . now()->format('Y-m-d'));
        } elseif ($validated['format'] === 'csv') {
            return $this->exportToCsv($data, 'Inventario_Almacén_' . now()->format('Y-m-d'));
        }

        return response()->json(['error' => 'Formato no soportado'], 400);
    }

    /**
     * Generar reporte de movimientos
     */
    public function generateMovements(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|integer',
            'movement_type' => 'nullable|string',
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'format' => 'required|in:excel,pdf,csv',
        ]);

        $query = WarehouseInventoryMovement::with(['slot', 'product', 'warehouse', 'user'])
            ->whereBetween('recorded_at', [
                Carbon::parse($validated['date_from'])->startOfDay(),
                Carbon::parse($validated['date_to'])->endOfDay(),
            ]);

        if ($validated['warehouse_id'] ?? null) {
            $query->where('warehouse_id', $validated['warehouse_id']);
        }

        if ($validated['movement_type'] ?? null) {
            $query->where('movement_type', $validated['movement_type']);
        }

        $movements = $query->latest('recorded_at')->get();

        $data = $movements->map(function ($movement) {
            return [
                'warehouse' => $movement->warehouse->title,
                'slot_address' => $movement->slot ? $movement->slot->getAddress() : 'N/A',
                'product' => $movement->product->title ?? 'N/A',
                'reference' => $movement->product->reference ?? 'N/A',
                'movement_type' => $movement->getTypeLabel(),
                'quantity_from' => $movement->from_quantity,
                'quantity_to' => $movement->to_quantity,
                'quantity_delta' => $movement->quantity_delta,
                'weight_from' => $movement->from_weight,
                'weight_to' => $movement->to_weight,
                'weight_delta' => $movement->weight_delta,
                'reason' => $movement->reason,
                'user' => $movement->user->name ?? 'Sistema',
                'recorded_at' => $movement->recorded_at->format('Y-m-d H:i:s'),
            ];
        });

        if ($validated['format'] === 'excel') {
            return $this->exportToExcel($data, 'Movimientos_Inventario_' . now()->format('Y-m-d'));
        } elseif ($validated['format'] === 'csv') {
            return $this->exportToCsv($data, 'Movimientos_Inventario_' . now()->format('Y-m-d'));
        }

        return response()->json(['error' => 'Formato no soportado'], 400);
    }

    /**
     * Generar reporte de ocupancia
     */
    public function generateOccupancy(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|integer',
            'format' => 'required|in:excel,pdf,csv',
        ]);

        $query = Warehouse::query();

        if ($validated['warehouse_id'] ?? null) {
            $query->where('id', $validated['warehouse_id']);
        }

        $warehouses = $query->get();

        $data = $warehouses->map(function ($warehouse) {
            $totalSlots = $warehouse->locations()->with('slots')->get()
                ->sum(function ($location) {
                    return $location->slots()->count();
                });

            $occupiedSlots = $warehouse->locations()->with('slots')->get()
                ->sum(function ($location) {
                    return $location->slots()->where('is_occupied', true)->count();
                });

            $occupancyPercentage = $totalSlots > 0 ? round(($occupiedSlots / $totalSlots) * 100, 2) : 0;

            return [
                'warehouse' => $warehouse->title,
                'total_locations' => $warehouse->locations()->count(),
                'total_slots' => $totalSlots,
                'occupied_slots' => $occupiedSlots,
                'available_slots' => $totalSlots - $occupiedSlots,
                'occupancy_percentage' => $occupancyPercentage . '%',
            ];
        });

        if ($validated['format'] === 'excel') {
            return $this->exportToExcel($data, 'Ocupancia_Almacén_' . now()->format('Y-m-d'));
        } elseif ($validated['format'] === 'csv') {
            return $this->exportToCsv($data, 'Ocupancia_Almacén_' . now()->format('Y-m-d'));
        }

        return response()->json(['error' => 'Formato no soportado'], 400);
    }

    /**
     * Generar reporte de utilización de capacidad
     */
    public function generateCapacity(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|integer',
            'format' => 'required|in:excel,pdf,csv',
        ]);

        $query = WarehouseInventorySlot::with(['location', 'product']);

        if ($validated['warehouse_id'] ?? null) {
            $query->whereHas('location', function ($q) use ($validated) {
                $q->where('warehouse_id', $validated['warehouse_id']);
            });
        }

        $slots = $query->get();

        $data = $slots->map(function ($slot) {
            $quantityUsage = $slot->max_quantity > 0 ? round(($slot->quantity / $slot->max_quantity) * 100, 2) : 0;
            $weightUsage = $slot->weight_max > 0 ? round(($slot->weight_current / $slot->weight_max) * 100, 2) : 0;

            return [
                'warehouse' => $slot->location->warehouse->title,
                'location_code' => $slot->location->code,
                'slot_address' => $slot->getAddress(),
                'product' => $slot->product->title ?? 'N/A',
                'quantity' => $slot->quantity,
                'max_quantity' => $slot->max_quantity,
                'quantity_usage' => $quantityUsage . '%',
                'weight_current' => $slot->weight_current,
                'weight_max' => $slot->weight_max,
                'weight_usage' => $weightUsage . '%',
                'is_near_capacity' => $slot->isNearQuantityCapacity() ? 'Sí' : 'No',
                'is_over_capacity' => $slot->isOverQuantity() ? 'Excedido' : 'Dentro',
            ];
        });

        if ($validated['format'] === 'excel') {
            return $this->exportToExcel($data, 'Capacidad_Almacén_' . now()->format('Y-m-d'));
        } elseif ($validated['format'] === 'csv') {
            return $this->exportToCsv($data, 'Capacidad_Almacén_' . now()->format('Y-m-d'));
        }

        return response()->json(['error' => 'Formato no soportado'], 400);
    }

    /**
     * Exportar a Excel
     */
    protected function exportToExcel($data, $filename)
    {
        // Implementar con Maatwebsite\Excel si está disponible
        // Por ahora retornamos respuesta JSON
        return response()->json([
            'status' => 'success',
            'message' => 'Reporte generado',
            'filename' => $filename . '.xlsx',
            'data' => $data,
        ]);
    }

    /**
     * Exportar a CSV
     */
    protected function exportToCsv($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ];

        $columns = collect($data)->first() ? array_keys($data[0]) : [];
        $csv = fopen('php://output', 'w');
        fputcsv($csv, $columns);

        foreach ($data as $row) {
            fputcsv($csv, array_values($row));
        }

        fclose($csv);

        return response()->stream(function () {
            // Stream already handled
        }, 200, $headers);
    }
}

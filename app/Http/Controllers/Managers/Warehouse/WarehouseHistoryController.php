<?php

namespace App\Http\Controllers\Managers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\WarehouseInventoryMovement;
use App\Models\Warehouse\WarehouseInventorySlot;
use App\Models\Warehouse\WarehouseLocationCondition;
use Illuminate\Http\Request;

class WarehouseHistoryController extends Controller
{
    /**
     * Listar histórico de movimientos de inventario
     */
    public function index(Request $request)
    {
        $searchKey = $request->search ?? null;
        $movements = WarehouseInventoryMovement::with(['slot', 'product', 'warehouse', 'user'])->latest();

        if ($searchKey) {
            // Búsqueda flexible por varios campos
            $movements = $movements->where(function ($query) use ($searchKey) {
                // Búsqueda en productos
                $query->whereHas('product', function ($q) use ($searchKey) {
                    $q->where('reference', 'like', '%' . $searchKey . '%')
                        ->orWhere('barcode', 'like', '%' . $searchKey . '%')
                        ->orWhere('title', 'like', '%' . $searchKey . '%');
                })
                // Búsqueda en ubicaciones/slots
                ->orWhereHas('slot', function ($q) use ($searchKey) {
                    $q->where('barcode', 'like', '%' . $searchKey . '%')
                        ->orWhere('uid', 'like', '%' . $searchKey . '%');
                })
                // Búsqueda en razón del movimiento
                ->orWhere('reason', 'like', '%' . $searchKey . '%');
            });
        }

        $movements = $movements->paginate(paginationNumber());

        return view('managers.views.warehouse.history.index')->with([
            'movements' => $movements,
            'searchKey' => $searchKey,
        ]);
    }

    /**
     * Ver detalles de un movimiento específico
     */
    public function view($uid)
    {
        $movement = WarehouseInventoryMovement::where('uid', $uid)
            ->with(['slot', 'product', 'warehouse', 'user'])
            ->firstOrFail();

        return view('managers.views.warehouse.history.view')->with([
            'movement' => $movement,
        ]);
    }

    /**
     * Formulario para editar datos de un movimiento (solo para correcciones manuales)
     */
    public function edit($uid)
    {
        $movement = WarehouseInventoryMovement::where('uid', $uid)
            ->with(['slot', 'product', 'warehouse'])
            ->firstOrFail();

        $conditions = WarehouseLocationCondition::available()->get();
        $conditions = $conditions->pluck('title', 'id');

        return view('managers.views.warehouse.history.edit')->with([
            'movement' => $movement,
            'conditions' => $conditions,
        ]);
    }

    /**
     * Actualizar movimiento (correcciones manuales)
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'uid' => 'required|string',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $movement = WarehouseInventoryMovement::where('uid', $validated['uid'])->firstOrFail();

        $oldData = $movement->only(['reason']);

        $movement->update([
            'reason' => $validated['reason'] ?? $movement->reason,
        ]);

        // Registrar el cambio en activity log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($movement)
            ->event('updated')
            ->withProperties(['old' => $oldData, 'attributes' => $movement->getChanges()])
            ->log('Movimiento de inventario corregido');

        return response()->json([
            'status' => true,
            'uid' => $movement->uid,
            'message' => 'Movimiento actualizado correctamente',
        ]);
    }

    /**
     * API: Obtener histórico de un slot específico
     */
    public function getSlotHistory($slotUid)
    {
        $slot = WarehouseInventorySlot::uid($slotUid)->firstOrFail();

        $movements = $slot->movements()
            ->with(['product', 'warehouse', 'user'])
            ->latest('recorded_at')
            ->paginate(50);

        return response()->json([
            'slot' => [
                'uid' => $slot->uid,
                'address' => $slot->getAddress(),
            ],
            'movements' => $movements->map(function ($movement) {
                return [
                    'id' => $movement->id,
                    'uid' => $movement->uid,
                    'type' => $movement->movement_type,
                    'type_label' => $movement->getTypeLabel(),
                    'product' => $movement->product ? [
                        'id' => $movement->product->id,
                        'title' => $movement->product->title,
                        'reference' => $movement->product->reference,
                    ] : null,
                    'quantity_delta' => $movement->quantity_delta,
                    'weight_delta' => $movement->weight_delta,
                    'reason' => $movement->reason,
                    'user' => $movement->user ? [
                        'id' => $movement->user->id,
                        'name' => $movement->user->name,
                    ] : null,
                    'recorded_at' => $movement->recorded_at,
                ];
            }),
        ]);
    }

    /**
     * API: Obtener histórico de un almacén completo
     */
    public function getWarehouseHistory($warehouseUid)
    {
        $warehouse = \App\Models\Warehouse\Warehouse::uid($warehouseUid)->firstOrFail();

        $movements = WarehouseInventoryMovement::where('warehouse_id', $warehouse->id)
            ->with(['slot', 'product', 'user'])
            ->latest('recorded_at')
            ->paginate(100);

        return response()->json([
            'warehouse' => [
                'id' => $warehouse->id,
                'uid' => $warehouse->uid,
                'title' => $warehouse->title,
            ],
            'movements' => $movements->map(function ($movement) {
                return [
                    'id' => $movement->id,
                    'uid' => $movement->uid,
                    'type' => $movement->movement_type,
                    'type_label' => $movement->getTypeLabel(),
                    'slot_address' => $movement->slot ? $movement->slot->getAddress() : null,
                    'product' => $movement->product ? [
                        'title' => $movement->product->title,
                        'reference' => $movement->product->reference,
                    ] : null,
                    'quantity_delta' => $movement->quantity_delta,
                    'weight_delta' => $movement->weight_delta,
                    'reason' => $movement->reason,
                    'user_name' => $movement->user ? $movement->user->name : null,
                    'recorded_at' => $movement->recorded_at,
                ];
            }),
        ]);
    }

    /**
     * API: Filtrar movimientos por rango de fechas
     */
    public function filterByDateRange(Request $request)
    {
        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date',
            'movement_type' => 'nullable|string',
            'warehouse_id' => 'nullable|integer',
        ]);

        $query = WarehouseInventoryMovement::whereBetween('recorded_at', [
            $validated['from_date'],
            $validated['to_date']
        ])->with(['slot', 'product', 'warehouse', 'user']);

        if ($validated['movement_type'] ?? null) {
            $query->where('movement_type', $validated['movement_type']);
        }

        if ($validated['warehouse_id'] ?? null) {
            $query->where('warehouse_id', $validated['warehouse_id']);
        }

        $movements = $query->latest('recorded_at')->paginate(100);

        return response()->json($movements);
    }

    /**
     * API: Obtener estadísticas de movimientos
     */
    public function getStatistics(Request $request)
    {
        $warehouseId = $request->warehouse_id ?? null;

        $query = WarehouseInventoryMovement::query();

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $statistics = [
            'total_movements' => $query->count(),
            'by_type' => $query->groupBy('movement_type')->select('movement_type')->selectRaw('count(*) as count')->get(),
            'by_user' => $query->groupBy('user_id')->with('user')->selectRaw('user_id, count(*) as count')->get(),
            'recent_24h' => $query->where('recorded_at', '>=', now()->subDay())->count(),
            'total_quantity_moved' => $query->sum('quantity_delta'),
            'total_weight_moved' => $query->sum('weight_delta'),
        ];

        return response()->json($statistics);
    }
}

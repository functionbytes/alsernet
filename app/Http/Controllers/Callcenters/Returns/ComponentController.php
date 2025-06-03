<?php

namespace App\Http\Controllers\Callcenters\Returns;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderComponent;
use App\Models\ProductComponent;
use App\Models\ComponentShipment;
use App\Services\ComponentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComponentController extends Controller
{
    protected $componentService;

    public function __construct(ComponentService $componentService)
    {
        $this->componentService = $componentService;
    }

    /**
     * Mostrar componentes de una orden
     */
    public function orderComponents(Order $order)
    {
        $this->authorize('view', $order);

        $order->load([
            'orderComponents.component',
            'orderComponents.substituteComponent',
            'orderComponents.shipmentItems.shipment'
        ]);

        $componentSummary = [
            'total_components' => $order->orderComponents->count(),
            'reserved_components' => $order->orderComponents->where('status', OrderComponent::STATUS_RESERVED)->count(),
            'shipped_components' => $order->orderComponents->where('status', OrderComponent::STATUS_SHIPPED)->count(),
            'missing_components' => $order->orderComponents->where('status', OrderComponent::STATUS_MISSING)->count(),
            'total_deductions' => $order->total_component_deductions,
        ];

        return view('components.order-components', compact('order', 'componentSummary'));
    }

    /**
     * Mostrar detalles de un componente de orden
     */
    public function showOrderComponent(OrderComponent $orderComponent)
    {
        $this->authorize('view', $orderComponent->order);

        $orderComponent->load([
            'component',
            'substituteComponent',
            'order',
            'orderItem.product',
            'shipmentItems.shipment'
        ]);

        $alternatives = [];
        if ($orderComponent->status === OrderComponent::STATUS_MISSING) {
            $alternatives = $orderComponent->findAlternatives();
        }

        $missingStrategies = [];
        if ($orderComponent->quantity_missing > 0) {
            $missingStrategies = $this->componentService->handleMissingComponent($orderComponent);
        }

        return view('components.show-order-component', compact(
            'orderComponent',
            'alternatives',
            'missingStrategies'
        ));
    }

    /**
     * Aplicar estrategia para componente faltante
     */
    public function applyMissingStrategy(Request $request, OrderComponent $orderComponent)
    {
        $this->authorize('update', $orderComponent->order);

        $request->validate([
            'strategy' => 'required|in:substitution,apply_deduction,wait_restock,cancel_item',
            'substitute_component_id' => 'required_if:strategy,substitution|exists:product_components,id',
            'quantity' => 'required_if:strategy,substitution|integer|min:1',
            'expected_date' => 'required_if:strategy,wait_restock|date|after:today',
        ]);

        $data = $request->only(['substitute_component_id', 'quantity', 'expected_date']);

        $success = $this->componentService->applyMissingComponentStrategy(
            $orderComponent,
            $request->strategy,
            $data
        );

        if ($success) {
            $messages = [
                'substitution' => 'Sustitución aplicada exitosamente',
                'apply_deduction' => 'Deducción aplicada, se enviará sin el componente',
                'wait_restock' => 'Componente marcado en espera de reposición',
                'cancel_item' => 'Componente cancelado del pedido',
            ];

            return redirect()->back()->with('success', $messages[$request->strategy]);
        } else {
            return redirect()->back()->withErrors(['strategy' => 'No se pudo aplicar la estrategia seleccionada']);
        }
    }

    /**
     * Procesar envío parcial
     */
    public function processPartialShipment(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $request->validate([
            'components' => 'required|array|min:1',
            'components.*.order_component_id' => 'required|exists:order_components,id',
            'components.*.quantity' => 'required|integer|min:1',
            'shipping_method' => 'required|in:standard,express,overnight',
            'priority' => 'required|in:normal,high,urgent',
        ]);

        try {
            $shipment = $this->componentService->createComponentShipment(
                $order,
                $request->components,
                [
                    'shipping_method' => $request->shipping_method,
                    'priority' => $request->priority,
                    'requires_signature' => $request->boolean('requires_signature'),
                ]
            );

            return redirect()->route('shipments.show', $shipment)
                ->with('success', 'Envío parcial creado exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['shipment' => $e->getMessage()]);
        }
    }

    /**
     * Mostrar inventario de componentes
     */
    public function inventory(Request $request)
    {
        $query = ProductComponent::active()->with(['product', 'supplier']);

        // Filtros
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('low_stock')) {
            $query->lowStock();
        }

        if ($request->filled('needs_reorder')) {
            $query->needsReorder();
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $components = $query->paginate(20);

        // Estadísticas rápidas
        $stats = [
            'total_components' => ProductComponent::active()->count(),
            'low_stock_count' => ProductComponent::active()->lowStock()->count(),
            'reorder_count' => ProductComponent::active()->needsReorder()->count(),
            'total_value' => ProductComponent::active()->sum(DB::raw('current_stock * unit_cost')),
        ];

        return view('components.inventory', compact('components', 'stats'));
    }

    /**
     * Mostrar detalles de componente
     */
    public function show(ProductComponent $component)
    {
        $component->load(['product', 'supplier', 'stockMovements' => function ($query) {
            $query->latest()->limit(20);
        }]);

        $stats = [
            'total_reserved' => $component->orderComponents()->sum('quantity_reserved'),
            'total_shipped' => $component->orderComponents()->sum('quantity_shipped'),
            'pending_orders' => $component->orderComponents()
                ->whereIn('status', [OrderComponent::STATUS_PENDING, OrderComponent::STATUS_PARTIAL])
                ->count(),
        ];

        return view('components.show', compact('component', 'stats'));
    }

    /**
     * Actualizar stock de componente
     */
    public function updateStock(Request $request, ProductComponent $component)
    {
        $this->authorize('admin');

        $request->validate([
            'movement_type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer',
            'reason' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
        ]);

        $quantity = $request->movement_type === 'out' ? -abs($request->quantity) : abs($request->quantity);

        try {
            $component->updateStock(
                $quantity,
                $request->movement_type,
                $request->reference,
                $request->reason
            );

            return redirect()->back()->with('success', 'Stock actualizado exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['stock' => 'Error actualizando stock: ' . $e->getMessage()]);
        }
    }

    /**
     * Generar reporte de inventario
     */
    public function inventoryReport()
    {
        $report = $this->componentService->generateInventoryReport();

        return view('components.inventory-report', compact('report'));
    }

    /**
     * Exportar reporte de inventario
     */
    public function exportInventory(Request $request)
    {
        $format = $request->get('format', 'csv');
        $report = $this->componentService->generateInventoryReport();

        // Preparar datos para exportación
        $exportData = [];
        foreach ($report['components_by_category'] as $category => $data) {
            $components = ProductComponent::where('category', $category)->active()->get();

            foreach ($components as $component) {
                $exportData[] = [
                    'Código' => $component->code,
                    'SKU' => $component->sku,
                    'Nombre' => $component->name,
                    'Categoría' => $component->category,
                    'Tipo' => $component->type,
                    'Stock Actual' => $component->current_stock,
                    'Stock Reservado' => $component->reserved_stock,
                    'Stock Disponible' => $component->available_stock,
                    'Stock Mínimo' => $component->minimum_stock,
                    'Punto de Reorden' => $component->reorder_point,
                    'Costo Unitario' => $component->unit_cost,
                    'Valor Total' => $component->current_stock * $component->unit_cost,
                    'Proveedor' => $component->supplier?->name,
                    'Ubicación' => $component->location,
                ];
            }
        }

        if ($format === 'csv') {
            return $this->exportToCsv($exportData, 'inventario-componentes.csv');
        } else {
            return $this->exportToExcel($exportData, 'inventario-componentes.xlsx');
        }
    }

    /**
     * Optimizar asignaciones de stock
     */
    public function optimizeAllocations()
    {
        $this->authorize('admin');

        $results = $this->componentService->optimizeStockAllocations();

        return response()->json([
            'success' => true,
            'message' => "Optimización completada. {$results['components_reserved']} componentes reservados.",
            'results' => $results,
        ]);
    }

    /**
     * Buscar componentes (AJAX)
     */
    public function search(Request $request)
    {
        $query = $request->get('q');

        $components = ProductComponent::active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'code', 'name', 'current_stock', 'available_stock']);

        return response()->json($components);
    }

    /**
     * Obtener alternativas para un componente
     */
    public function getAlternatives(ProductComponent $component)
    {
        $alternatives = $component->getAvailableSubstitutes();

        return response()->json([
            'alternatives' => $alternatives->map(function ($substitution) {
                return [
                    'id' => $substitution->substituteComponent->id,
                    'code' => $substitution->substituteComponent->code,
                    'name' => $substitution->substituteComponent->name,
                    'available_stock' => $substitution->substituteComponent->available_stock,
                    'compatibility_level' => $substitution->compatibility_level,
                    'cost_difference' => $substitution->cost_difference,
                    'performance_impact' => $substitution->performance_impact,
                ];
            }),
        ]);
    }

    /**
     * Sincronizar inventario físico
     */
    public function syncPhysicalStock(Request $request)
    {
        $this->authorize('admin');

        $request->validate([
            'stock_counts' => 'required|array',
            'stock_counts.*.component_code' => 'required|string',
            'stock_counts.*.physical_count' => 'required|integer|min:0',
        ]);

        $results = $this->componentService->syncPhysicalStock($request->stock_counts);

        if (empty($results['errors'])) {
            return response()->json([
                'success' => true,
                'message' => "Sincronización completada. {$results['components_updated']} componentes actualizados.",
                'results' => $results,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Sincronización completada con errores.',
                'results' => $results,
            ], 422);
        }
    }

    /**
     * Exportar a CSV
     */
    protected function exportToCsv($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // Escribir headers
            if (!empty($data)) {
                fputcsv($file, array_keys($data[0]));
            }

            // Escribir datos
            foreach ($data as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

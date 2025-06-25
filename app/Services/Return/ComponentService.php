<?php

namespace App\Services\Return;

use App\Models\Order;
use App\Models\OrderComponent;
use App\Models\ProductComponent;
use App\Models\Return\ComponentShipment;
use App\Models\Return\ComponentShipmentItem;
use App\Models\Return\ComponentSubstitution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComponentService
{
    /**
     * Procesar componentes para una orden
     */
    public function processOrderComponents(Order $order): array
    {
        $results = [
            'components_processed' => 0,
            'components_reserved' => 0,
            'components_missing' => 0,
            'substitutions_applied' => 0,
            'total_deductions' => 0,
            'can_ship_complete' => true,
            'missing_components' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($order->items as $orderItem) {
                $product = $orderItem->product;
                $components = $product->components()->active()->get();

                foreach ($components as $component) {
                    $quantityRequired = $component->quantity_per_product * $orderItem->quantity;

                    // Crear registro de componente de orden
                    $orderComponent = OrderComponent::create([
                        'order_id' => $order->id,
                        'order_item_id' => $orderItem->id,
                        'component_id' => $component->id,
                        'quantity_required' => $quantityRequired,
                        'unit_cost' => $component->unit_cost,
                        'total_cost' => $component->unit_cost * $quantityRequired,
                        'is_essential' => $component->type === ProductComponent::TYPE_ESSENTIAL,
                        'can_substitute' => $component->compatible_alternatives ? true : false,
                    ]);

                    $results['components_processed']++;

                    // Intentar reservar stock
                    if ($orderComponent->reserveStock()) {
                        $results['components_reserved']++;
                    } else {
                        $results['components_missing']++;
                        $results['can_ship_complete'] = false;

                        // Intentar sustitución automática
                        if ($this->tryAutoSubstitution($orderComponent)) {
                            $results['substitutions_applied']++;
                            $results['components_reserved']++;
                        } else {
                            // Calcular deducción si corresponde
                            $deduction = $orderComponent->calculateDeduction();
                            $results['total_deductions'] += $deduction;

                            $results['missing_components'][] = [
                                'component_id' => $component->id,
                                'component_code' => $component->code,
                                'component_name' => $component->name,
                                'quantity_required' => $quantityRequired,
                                'quantity_missing' => $orderComponent->quantity_missing,
                                'is_essential' => $orderComponent->is_essential,
                                'deduction_applied' => $deduction,
                                'estimated_availability' => $orderComponent->getEstimatedAvailabilityDate(),
                            ];
                        }
                    }
                }
            }

            // Actualizar estado de la orden
            $order->update([
                'has_missing_components' => $results['components_missing'] > 0,
                'component_status' => $this->determineComponentStatus($results),
                'total_component_deductions' => $results['total_deductions'],
                'expected_completion_date' => $this->calculateExpectedCompletionDate($order),
                'component_summary' => $results,
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error procesando componentes de orden', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $results;
    }

    /**
     * Intentar sustitución automática
     */
    protected function tryAutoSubstitution(OrderComponent $orderComponent): bool
    {
        if (!$orderComponent->can_substitute) {
            return false;
        }

        $alternatives = $orderComponent->findAlternatives();

        foreach ($alternatives as $alternative) {
            $substitute = $alternative['component'];
            $availableQuantity = $alternative['available_quantity'];
            $pendingQuantity = $orderComponent->getPendingQuantity();

            // Solo aplicar si puede cubrir completamente o si la diferencia de costo es mínima
            if ($availableQuantity >= $pendingQuantity || $alternative['cost_difference'] <= 0) {
                $quantityToSubstitute = min($availableQuantity, $pendingQuantity);

                if ($orderComponent->applySubstitution($substitute, $quantityToSubstitute)) {
                    Log::info('Sustitución automática aplicada', [
                        'order_id' => $orderComponent->order_id,
                        'original_component' => $orderComponent->component->code,
                        'substitute_component' => $substitute->code,
                        'quantity' => $quantityToSubstitute,
                    ]);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determinar estado de componentes de la orden
     */
    protected function determineComponentStatus(array $results): string
    {
        if ($results['components_missing'] === 0) {
            return 'complete';
        } elseif ($results['components_reserved'] > 0) {
            return 'partial';
        } else {
            return 'missing';
        }
    }

    /**
     * Calcular fecha estimada de completación
     */
    protected function calculateExpectedCompletionDate(Order $order): ?string
    {
        $missingComponents = $order->orderComponents()->missing()->get();

        if ($missingComponents->isEmpty()) {
            return now()->addDays(1)->toDateString(); // Disponible mañana
        }

        $maxLeadTime = $missingComponents->max(function ($orderComponent) {
            return $orderComponent->component->getEstimatedLeadTime();
        });

        return now()->addDays($maxLeadTime)->toDateString();
    }

    /**
     * Crear envío de componentes
     */
    public function createComponentShipment(Order $order, array $componentItems, array $shippingData = []): ComponentShipment
    {
        DB::beginTransaction();

        try {
            // Crear envío
            $shipment = ComponentShipment::create(array_merge([
                'order_id' => $order->id,
                'shipment_number' => ComponentShipment::generateShipmentNumber(),
                'shipment_type' => $this->determineShipmentType($order, $componentItems),
                'status' => ComponentShipment::STATUS_PREPARING,
                'shipping_address' => $order->shipping_address,
                'created_by' => auth()->id(),
            ], $shippingData));

            // Agregar items al envío
            foreach ($componentItems as $item) {
                $orderComponent = OrderComponent::find($item['order_component_id']);
                $component = $orderComponent->component;

                ComponentShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'order_component_id' => $orderComponent->id,
                    'component_id' => $component->id,
                    'quantity_shipped' => $item['quantity'],
                    'unit_cost' => $component->unit_cost,
                    'total_cost' => $component->unit_cost * $item['quantity'],
                    'weight' => $component->weight,
                    'serial_numbers' => $item['serial_numbers'] ?? null,
                    'batch_number' => $item['batch_number'] ?? null,
                    'condition' => $item['condition'] ?? 'new',
                    'package_reference' => $item['package_reference'] ?? null,
                ]);
            }

            // Crear paquetes automáticamente
            $shipment->createPackages();

            // Calcular costo de envío
            $shippingCost = $shipment->calculateShippingCost();
            $shipment->update(['shipping_cost' => $shippingCost]);

            // Actualizar contador de envíos de la orden
            $order->increment('total_shipments');

            DB::commit();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        return $shipment;
    }

    /**
     * Determinar tipo de envío
     */
    protected function determineShipmentType(Order $order, array $componentItems): string
    {
        $totalRequired = $order->orderComponents()->sum('quantity_required');
        $totalShipping = array_sum(array_column($componentItems, 'quantity'));
        $totalAlreadyShipped = $order->orderComponents()->sum('quantity_shipped');

        if ($totalShipping + $totalAlreadyShipped >= $totalRequired) {
            return ComponentShipment::TYPE_COMPLETE;
        } else {
            return ComponentShipment::TYPE_PARTIAL;
        }
    }

    /**
     * Procesar envío parcial
     */
    public function processPartialShipment(Order $order): array
    {
        $availableComponents = $order->orderComponents()
            ->where('status', OrderComponent::STATUS_ALLOCATED)
            ->where('quantity_allocated', '>', 0)
            ->with('component')
            ->get();

        if ($availableComponents->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No hay componentes disponibles para envío',
            ];
        }

        $shipmentItems = [];
        foreach ($availableComponents as $orderComponent) {
            $shipmentItems[] = [
                'order_component_id' => $orderComponent->id,
                'quantity' => $orderComponent->quantity_allocated,
                'condition' => 'new',
            ];
        }

        try {
            $shipment = $this->createComponentShipment($order, $shipmentItems, [
                'shipment_type' => ComponentShipment::TYPE_PARTIAL,
                'priority' => 'normal',
            ]);

            return [
                'success' => true,
                'shipment' => $shipment,
                'components_shipped' => count($shipmentItems),
                'message' => 'Envío parcial creado exitosamente',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creando envío parcial: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Manejar componente faltante
     */
    public function handleMissingComponent(OrderComponent $orderComponent, array $options = []): array
    {
        $strategies = [];

        // Estrategia 1: Buscar sustitutos
        if ($orderComponent->can_substitute) {
            $alternatives = $orderComponent->findAlternatives();
            if (!empty($alternatives)) {
                $strategies[] = [
                    'type' => 'substitution',
                    'description' => 'Usar componente sustituto',
                    'alternatives' => $alternatives,
                    'recommended' => true,
                ];
            }
        }

        // Estrategia 2: Esperar reposición
        $expectedDate = $orderComponent->getEstimatedAvailabilityDate();
        $strategies[] = [
            'type' => 'wait_restock',
            'description' => 'Esperar reposición del componente',
            'expected_date' => $expectedDate,
            'lead_time_days' => $orderComponent->component->lead_time_days,
            'recommended' => false,
        ];

        // Estrategia 3: Aplicar deducción
        if ($orderComponent->component->deduction_percentage > 0 || $orderComponent->component->fixed_deduction_amount > 0) {
            $deduction = $orderComponent->calculateDeduction();
            $strategies[] = [
                'type' => 'apply_deduction',
                'description' => 'Enviar sin el componente con deducción',
                'deduction_amount' => $deduction,
                'affects_functionality' => $orderComponent->component->affects_functionality,
                'recommended' => !$orderComponent->is_essential,
            ];
        }

        // Estrategia 4: Cancelar item (solo para componentes no esenciales)
        if (!$orderComponent->is_essential) {
            $strategies[] = [
                'type' => 'cancel_item',
                'description' => 'Cancelar el item del pedido',
                'refund_amount' => $orderComponent->total_cost,
                'recommended' => false,
            ];
        }

        return [
            'component' => $orderComponent->component->only(['id', 'code', 'name', 'type']),
            'missing_quantity' => $orderComponent->quantity_missing,
            'is_essential' => $orderComponent->is_essential,
            'strategies' => $strategies,
        ];
    }

    /**
     * Aplicar estrategia para componente faltante
     */
    public function applyMissingComponentStrategy(OrderComponent $orderComponent, string $strategy, array $data = []): bool
    {
        switch ($strategy) {
            case 'substitution':
                $substituteId = $data['substitute_component_id'];
                $quantity = $data['quantity'];
                $substitute = ProductComponent::find($substituteId);

                return $orderComponent->applySubstitution($substitute, $quantity);

            case 'apply_deduction':
                $deduction = $orderComponent->calculateDeduction();

                // Actualizar total de la orden
                $orderComponent->order->increment('total_component_deductions', $deduction);

                return true;

            case 'wait_restock':
                $expectedDate = $data['expected_date'] ?? $orderComponent->getEstimatedAvailabilityDate();

                $orderComponent->update([
                    'expected_date' => $expectedDate,
                    'status' => OrderComponent::STATUS_PENDING,
                ]);

                return true;

            case 'cancel_item':
                if ($orderComponent->is_essential) {
                    return false; // No se puede cancelar componente esencial
                }

                // Liberar stock reservado
                if ($orderComponent->quantity_reserved > 0) {
                    $orderComponent->component->releaseStock(
                        $orderComponent->quantity_reserved,
                        $orderComponent->order_id
                    );
                }

                $orderComponent->update([
                    'status' => 'cancelled',
                    'quantity_reserved' => 0,
                    'quantity_allocated' => 0,
                ]);

                return true;

            default:
                return false;
        }
    }

    /**
     * Generar reporte de inventario de componentes
     */
    public function generateInventoryReport(): array
    {
        $components = ProductComponent::active()
            ->with(['product', 'supplier'])
            ->get();

        $report = [
            'total_components' => $components->count(),
            'low_stock_components' => 0,
            'reorder_required' => 0,
            'total_inventory_value' => 0,
            'components_by_category' => [],
            'low_stock_items' => [],
            'reorder_items' => [],
        ];

        foreach ($components as $component) {
            // Contadores generales
            if ($component->current_stock <= $component->minimum_stock) {
                $report['low_stock_components']++;
                $report['low_stock_items'][] = $component->getStatusSummary();
            }

            if ($component->current_stock <= $component->reorder_point) {
                $report['reorder_required']++;
                $report['reorder_items'][] = array_merge(
                    $component->getStatusSummary(),
                    [
                        'supplier_name' => $component->supplier?->name,
                        'suggested_order_quantity' => max(
                            $component->minimum_stock * 2,
                            $component->reorder_point + 50
                        ),
                    ]
                );
            }

            // Valor del inventario
            $report['total_inventory_value'] += $component->current_stock * $component->unit_cost;

            // Agrupar por categoría
            $category = $component->category ?: 'uncategorized';
            if (!isset($report['components_by_category'][$category])) {
                $report['components_by_category'][$category] = [
                    'count' => 0,
                    'total_value' => 0,
                    'low_stock_count' => 0,
                ];
            }

            $report['components_by_category'][$category]['count']++;
            $report['components_by_category'][$category]['total_value'] +=
                $component->current_stock * $component->unit_cost;

            if ($component->current_stock <= $component->minimum_stock) {
                $report['components_by_category'][$category]['low_stock_count']++;
            }
        }

        return $report;
    }

    /**
     * Optimizar asignaciones de stock
     */
    public function optimizeStockAllocations(): array
    {
        $pendingOrders = Order::whereIn('component_status', ['partial', 'missing'])
            ->with(['orderComponents' => function ($query) {
                $query->whereIn('status', [OrderComponent::STATUS_PENDING, OrderComponent::STATUS_PARTIAL]);
            }])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at')
            ->get();

        $optimizations = [];
        $componentsReserved = 0;

        foreach ($pendingOrders as $order) {
            foreach ($order->orderComponents as $orderComponent) {
                if ($orderComponent->status !== OrderComponent::STATUS_PENDING) {
                    continue;
                }

                $component = $orderComponent->component;
                $pendingQuantity = $orderComponent->getPendingQuantity();

                if ($component->available_stock >= $pendingQuantity) {
                    // Reservar stock disponible
                    if ($orderComponent->reserveStock()) {
                        $optimizations[] = [
                            'order_id' => $order->id,
                            'component_code' => $component->code,
                            'action' => 'reserved',
                            'quantity' => $pendingQuantity,
                        ];
                        $componentsReserved++;
                    }
                } elseif ($component->available_stock > 0 && $order->allows_partial_shipment) {
                    // Reservar parcialmente si se permiten envíos parciales
                    $availableQuantity = $component->available_stock;
                    if ($component->reserveStock($availableQuantity, $order->id)) {
                        $orderComponent->update([
                            'quantity_reserved' => $orderComponent->quantity_reserved + $availableQuantity,
                            'quantity_missing' => $pendingQuantity - $availableQuantity,
                            'status' => OrderComponent::STATUS_PARTIAL,
                        ]);

                        $optimizations[] = [
                            'order_id' => $order->id,
                            'component_code' => $component->code,
                            'action' => 'partial_reserved',
                            'quantity' => $availableQuantity,
                            'missing' => $pendingQuantity - $availableQuantity,
                        ];
                        $componentsReserved++;
                    }
                }
            }
        }

        return [
            'orders_processed' => $pendingOrders->count(),
            'components_reserved' => $componentsReserved,
            'optimizations' => $optimizations,
        ];
    }

    /**
     * Sincronizar stock físico
     */
    public function syncPhysicalStock(array $stockCounts): array
    {
        $results = [
            'components_updated' => 0,
            'adjustments_made' => [],
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($stockCounts as $stockData) {
                $component = ProductComponent::where('code', $stockData['component_code'])
                    ->orWhere('sku', $stockData['component_code'])
                    ->first();

                if (!$component) {
                    $results['errors'][] = [
                        'code' => $stockData['component_code'],
                        'error' => 'Componente no encontrado',
                    ];
                    continue;
                }

                $physicalCount = $stockData['physical_count'];
                $systemCount = $component->current_stock;
                $difference = $physicalCount - $systemCount;

                if ($difference !== 0) {
                    $component->updateStock(
                        $difference,
                        'adjustment',
                        'physical_inventory',
                        "Ajuste por inventario físico. Conteo: {$physicalCount}, Sistema: {$systemCount}"
                    );

                    $results['adjustments_made'][] = [
                        'component_code' => $component->code,
                        'component_name' => $component->name,
                        'system_count' => $systemCount,
                        'physical_count' => $physicalCount,
                        'adjustment' => $difference,
                    ];

                    $results['components_updated']++;
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollback();
            $results['errors'][] = ['error' => 'Error general: ' . $e->getMessage()];
        }

        return $results;
    }
}

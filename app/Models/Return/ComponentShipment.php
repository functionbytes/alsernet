<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComponentShipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'shipment_number',
        'shipment_type',
        'status',
        'total_weight',
        'shipping_cost',
        'carrier',
        'tracking_number',
        'shipping_address',
        'shipped_date',
        'estimated_delivery_date',
        'actual_delivery_date',
        'shipping_notes',
        'packages_info',
        'requires_signature',
        'is_fragile',
        'priority',
        'insurance_value',
        'shipping_method',
        'created_by',
        'delivery_confirmation',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'packages_info' => 'array',
        'delivery_confirmation' => 'array',
        'total_weight' => 'decimal:3',
        'shipping_cost' => 'decimal:2',
        'insurance_value' => 'decimal:2',
        'requires_signature' => 'boolean',
        'is_fragile' => 'boolean',
        'shipped_date' => 'date',
        'estimated_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
    ];

    /**
     * Estados de envío
     */
    const STATUS_PREPARING = 'preparing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_RETURNED = 'returned';

    /**
     * Tipos de envío
     */
    const TYPE_PARTIAL = 'partial';
    const TYPE_COMPLETE = 'complete';
    const TYPE_BACKORDER = 'backorder';

    /**
     * Relación con orden
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Items del envío
     */
    public function items()
    {
        return $this->hasMany(ComponentShipmentItem::class, 'shipment_id');
    }

    /**
     * Usuario que creó el envío
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope para envíos activos
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_DELIVERED, self::STATUS_RETURNED]);
    }

    /**
     * Scope para envíos en tránsito
     */
    public function scopeInTransit($query)
    {
        return $query->whereIn('status', [self::STATUS_SHIPPED, self::STATUS_IN_TRANSIT]);
    }

    /**
     * Generar número de envío único
     */
    public static function generateShipmentNumber(): string
    {
        do {
            $number = 'SHP-' . now()->format('Y') . '-' . strtoupper(uniqid());
        } while (self::where('shipment_number', $number)->exists());

        return $number;
    }

    /**
     * Calcular peso total del envío
     */
    public function calculateTotalWeight(): float
    {
        return $this->items->sum(function ($item) {
            return $item->weight * $item->quantity_shipped;
        });
    }

    /**
     * Marcar como enviado
     */
    public function markAsShipped(array $shippingData = []): bool
    {
        $updateData = array_merge([
            'status' => self::STATUS_SHIPPED,
            'shipped_date' => now()->toDateString(),
            'total_weight' => $this->calculateTotalWeight(),
        ], $shippingData);

        $this->update($updateData);

        // Actualizar items del envío
        foreach ($this->items as $item) {
            $item->orderComponent->markAsShipped($item->quantity_shipped, [
                'shipment_id' => $this->id,
                'tracking_number' => $this->tracking_number,
            ]);
        }

        return true;
    }

    /**
     * Marcar como entregado
     */
    public function markAsDelivered(array $deliveryData = []): bool
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'actual_delivery_date' => now()->toDateString(),
            'delivery_confirmation' => $deliveryData,
        ]);

        return true;
    }

    /**
     * Obtener información de tracking
     */
    public function getTrackingInfo(): array
    {
        return [
            'shipment_number' => $this->shipment_number,
            'tracking_number' => $this->tracking_number,
            'carrier' => $this->carrier,
            'status' => $this->status,
            'shipped_date' => $this->shipped_date?->format('d/m/Y'),
            'estimated_delivery' => $this->estimated_delivery_date?->format('d/m/Y'),
            'actual_delivery' => $this->actual_delivery_date?->format('d/m/Y'),
            'packages_info' => $this->packages_info,
        ];
    }

    /**
     * Verificar si está atrasado
     */
    public function isOverdue(): bool
    {
        if (!$this->estimated_delivery_date || $this->status === self::STATUS_DELIVERED) {
            return false;
        }

        return $this->estimated_delivery_date < now()->toDateString();
    }

    /**
     * Obtener resumen de items
     */
    public function getItemsSummary(): array
    {
        return $this->items->map(function ($item) {
            return [
                'component_code' => $item->component->code,
                'component_name' => $item->component->name,
                'quantity' => $item->quantity_shipped,
                'condition' => $item->condition,
                'serial_numbers' => $item->serial_numbers,
                'package_reference' => $item->package_reference,
            ];
        })->toArray();
    }

    /**
     * Crear paquetes automáticamente
     */
    public function createPackages(): array
    {
        $packages = [];
        $currentPackage = [];
        $currentWeight = 0;
        $maxWeightPerPackage = 20; // kg

        foreach ($this->items as $item) {
            $itemWeight = $item->weight * $item->quantity_shipped;

            if ($currentWeight + $itemWeight > $maxWeightPerPackage && !empty($currentPackage)) {
                // Crear paquete actual y empezar uno nuevo
                $packages[] = [
                    'package_number' => count($packages) + 1,
                    'weight' => $currentWeight,
                    'items' => $currentPackage,
                    'is_fragile' => collect($currentPackage)->contains('is_fragile', true),
                ];

                $currentPackage = [];
                $currentWeight = 0;
            }

            $currentPackage[] = [
                'component_id' => $item->component_id,
                'component_code' => $item->component->code,
                'quantity' => $item->quantity_shipped,
                'weight' => $itemWeight,
                'is_fragile' => $item->component->metadata['is_fragile'] ?? false,
            ];

            $currentWeight += $itemWeight;
        }

        // Agregar último paquete
        if (!empty($currentPackage)) {
            $packages[] = [
                'package_number' => count($packages) + 1,
                'weight' => $currentWeight,
                'items' => $currentPackage,
                'is_fragile' => collect($currentPackage)->contains('is_fragile', true),
            ];
        }

        $this->update(['packages_info' => $packages]);

        return $packages;
    }

    /**
     * Calcular costo de envío
     */
    public function calculateShippingCost(): float
    {
        $baseRate = 15.00; // Tarifa base
        $weightRate = 2.00; // Por kg
        $priorityMultiplier = $this->priority === 'urgent' ? 2.0 : ($this->priority === 'high' ? 1.5 : 1.0);

        $cost = $baseRate + ($this->total_weight * $weightRate);
        $cost *= $priorityMultiplier;

        // Costo adicional por firma requerida
        if ($this->requires_signature) {
            $cost += 5.00;
        }

        // Costo adicional por seguro
        if ($this->insurance_value > 0) {
            $cost += ($this->insurance_value * 0.01); // 1% del valor asegurado
        }

        return round($cost, 2);
    }
}

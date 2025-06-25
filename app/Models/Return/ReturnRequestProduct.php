<?php

namespace App\Models\Return;

use App\Models\Return\Order\ReturnOrderProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRequestProduct extends Model
{
    protected $table = 'return_request_products';
    protected $primaryKey = 'id';

    protected $fillable = [
        'request_id',
        'product_id',
        'product_code',
        'product_name',
        'quantity',
        'unit_price',
        'total_price',
        'reason_id',
        'return_condition',
        'notes',
        'is_approved',
        'approved_quantity',
        'refund_amount',
        'replacement_requested'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'approved_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'is_approved' => 'boolean',
        'replacement_requested' => 'boolean'
    ];

    // Relaciones
    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\ReturnRequest', 'return_id', 'id');
    }

    public function orderProduct(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\Order\ReturnOrderProduct', 'product_id', 'id');
    }

    public function returnReason(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\ReturnReason', 'reason_id', 'id');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeByCondition($query, $condition)
    {
        return $query->where('return_condition', $condition);
    }

    public function scopeForReplacement($query)
    {
        return $query->where('replacement_requested', true);
    }

    // Métodos auxiliares
    public function getStatusName(): string
    {
        if ($this->is_approved === null) {
            return 'Pendiente de revisión';
        }

        return $this->is_approved ? 'Aprobado' : 'Rechazado';
    }

    public function getStatusColor(): string
    {
        if ($this->is_approved === null) {
            return 'warning';
        }

        return $this->is_approved ? 'success' : 'danger';
    }

    public function calculateRefundAmount(): float
    {
        $quantity = $this->approved_quantity ?? $this->quantity;
        return $quantity * $this->unit_price;
    }

    public function approve(float $approvedQuantity = null, float $refundAmount = null): bool
    {
        return $this->update([
            'is_approved' => true,
            'approved_quantity' => $approvedQuantity ?? $this->quantity,
            'refund_amount' => $refundAmount ?? $this->calculateRefundAmount()
        ]);
    }

    public function reject(string $reason = null): bool
    {
        return $this->update([
            'is_approved' => false,
            'approved_quantity' => 0,
            'refund_amount' => 0,
            'notes' => $reason ? "Rechazado: {$reason}" : $this->notes
        ]);
    }

    /**
     * Crear productos de devolución desde selección del usuario
     */
    public static function createFromSelection(int $returnRequestId, array $selectedProducts): void
    {
        foreach ($selectedProducts as $selection) {
            $orderProduct = ReturnOrderProduct::find($selection['order_product_id']);

            if (!$orderProduct || !$orderProduct->canBeReturned()) {
                continue;
            }

            // Verificar que la cantidad solicitada no exceda la disponible
            $requestedQuantity = min(
                floatval($selection['quantity']),
                $orderProduct->available_for_return
            );

            if ($requestedQuantity <= 0) {
                continue;
            }

            self::create([
                'request_id' => $returnRequestId,
                'product_id' => $orderProduct->id,
                'product_code' => $orderProduct->product_code,
                'product_name' => $orderProduct->product_name,
                'quantity' => $requestedQuantity,
                'unit_price' => $orderProduct->unit_price,
                'total_price' => $requestedQuantity * $orderProduct->unit_price,
                'return_reason_id' => $selection['return_reason_id'] ?? null,
                'return_condition' => $selection['condition'] ?? 'good',
                'notes' => $selection['notes'] ?? null,
                'replacement_requested' => $selection['replacement_requested'] ?? false
            ]);
        }
    }

    /**
     * Obtener resumen para mostrar
     */
    public function getDisplayInfo(): array
    {
        return [
            'id' => $this->id,
            'product_code' => $this->product_code,
            'product_name' => $this->product_name,
            'quantity' => $this->quantity,
            'approved_quantity' => $this->approved_quantity,
            'unit_price' => number_format($this->unit_price, 2) . ' €',
            'total_price' => number_format($this->total_price, 2) . ' €',
            'refund_amount' => number_format($this->refund_amount ?? 0, 2) . ' €',
            'return_condition' => $this->return_condition,
            'status' => $this->getStatusName(),
            'status_color' => $this->getStatusColor(),
            'replacement_requested' => $this->replacement_requested,
            'notes' => $this->notes,
            'return_reason' => $this->returnReason?->getTranslation()?->name ?? 'No especificado'
        ];
    }

    /**
     * Validar que se puede devolver esta cantidad
     */
    public function validateQuantity(): bool
    {
        if (!$this->orderProduct) {
            return false;
        }

        // Cantidad disponible considerando otras devoluciones activas
        $availableQuantity = $this->orderProduct->available_for_return;

        // Si es una actualización, sumar la cantidad actual de este item
        if ($this->exists) {
            $availableQuantity += $this->getOriginal('quantity');
        }

        return $this->quantity <= $availableQuantity;
    }
}

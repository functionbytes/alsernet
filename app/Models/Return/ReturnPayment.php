<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnPayment extends Model
{
    protected $table = 'return_payments';
    protected $primaryKey = 'id_return_payment';

    protected $fillable = [
        'id_return_request', 'amount', 'payment_method', 'transaction_id',
        'payment_status', 'processed_at', 'notes', 'id_employee'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\ReturnRequest', 'id_return_request', 'id_return_request');
    }

    public function scopeByReturn($query, $returnId)
    {
        return $query->where('id_return_request', $returnId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('payment_status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('payment_status', self::STATUS_FAILED);
    }

    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_at');
    }

    // Constantes para estados de pago
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    // Constantes para métodos de pago
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CREDIT_CARD = 'credit_card';
    const METHOD_PAYPAL = 'paypal';
    const METHOD_WALLET = 'wallet';
    const METHOD_CASH = 'cash';
    const METHOD_OTHER = 'other';

    /**
     * Verificar si el pago está pendiente
     */
    public function isPending(): bool
    {
        return $this->payment_status === self::STATUS_PENDING;
    }

    /**
     * Verificar si el pago está completado
     */
    public function isCompleted(): bool
    {
        return $this->payment_status === self::STATUS_COMPLETED;
    }

    /**
     * Verificar si el pago ha fallado
     */
    public function isFailed(): bool
    {
        return $this->payment_status === self::STATUS_FAILED;
    }

    /**
     * Verificar si el pago está siendo procesado
     */
    public function isProcessing(): bool
    {
        return $this->payment_status === self::STATUS_PROCESSING;
    }

    /**
     * Marcar el pago como completado
     */
    public function markAsCompleted($transactionId = null, $notes = null): bool
    {
        $data = [
            'payment_status' => self::STATUS_COMPLETED,
            'processed_at' => now()
        ];

        if ($transactionId) {
            $data['transaction_id'] = $transactionId;
        }

        if ($notes) {
            $data['notes'] = $notes;
        }

        return $this->update($data);
    }

    /**
     * Marcar el pago como fallido
     */
    public function markAsFailed($notes = null): bool
    {
        $data = [
            'payment_status' => self::STATUS_FAILED,
            'processed_at' => now()
        ];

        if ($notes) {
            $data['notes'] = $notes;
        }

        return $this->update($data);
    }

    /**
     * Obtener el nombre legible del estado de pago
     */
    public function getStatusName(): string
    {
        $statuses = [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_PROCESSING => 'Procesando',
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_FAILED => 'Fallido',
            self::STATUS_CANCELLED => 'Cancelado',
            self::STATUS_REFUNDED => 'Reembolsado'
        ];

        return $statuses[$this->payment_status] ?? 'Desconocido';
    }

    /**
     * Obtener el nombre legible del método de pago
     */
    public function getPaymentMethodName(): string
    {
        $methods = [
            self::METHOD_BANK_TRANSFER => 'Transferencia Bancaria',
            self::METHOD_CREDIT_CARD => 'Tarjeta de Crédito',
            self::METHOD_PAYPAL => 'PayPal',
            self::METHOD_WALLET => 'Monedero Digital',
            self::METHOD_CASH => 'Efectivo',
            self::METHOD_OTHER => 'Otro'
        ];

        return $methods[$this->payment_method] ?? 'Desconocido';
    }

    /**
     * Obtener el color CSS del estado
     */
    public function getStatusColor(): string
    {
        $colors = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_PROCESSING => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            self::STATUS_REFUNDED => 'primary'
        ];

        return $colors[$this->payment_status] ?? 'secondary';
    }

    /**
     * Obtener el monto formateado
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 2, ',', '.') . ' €';
    }

    /**
     * Calcular el total de pagos para una devolución
     */
    public static function getTotalForReturn($returnId): float
    {
        return static::where('id_return_request', $returnId)
            ->where('payment_status', self::STATUS_COMPLETED)
            ->sum('amount');
    }

    /**
     * Verificar si una devolución está completamente reembolsada
     */
    public static function isReturnFullyRefunded($returnId, $expectedAmount): bool
    {
        $totalRefunded = static::getTotalForReturn($returnId);
        return $totalRefunded >= $expectedAmount;
    }
}

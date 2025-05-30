<?php

namespace App\Events\Return;

use App\Models\Return\ReturnRequest;
use App\Models\Return\ReturnPayment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReturnPaymentProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $return;
    public $payment;
    public $processedBy;
    public $metadata;

    public function __construct(
        ReturnRequest $return,
        ReturnPayment $payment,
        int $processedBy = 1,
        array $metadata = []
    ) {
        $this->return = $return;
        $this->payment = $payment;
        $this->processedBy = $processedBy;
        $this->metadata = $metadata;
    }

    /**
     * Obtener datos del evento para logs
     */
    public function getEventData(): array
    {
        return [
            'event' => 'return_payment_processed',
            'return_id' => $this->return->id_return_request,
            'payment_id' => $this->payment->id_return_payment,
            'order_id' => $this->return->id_order,
            'customer_email' => $this->return->email,
            'amount' => $this->payment->amount,
            'payment_method' => $this->payment->payment_method,
            'payment_status' => $this->payment->payment_status,
            'transaction_id' => $this->payment->transaction_id,
            'processed_by' => $this->processedBy,
            'metadata' => $this->metadata,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Verificar si el pago fue exitoso
     */
    public function isSuccessful(): bool
    {
        return $this->payment->payment_status === 'completed';
    }

    /**
     * Verificar si el pago falló
     */
    public function isFailed(): bool
    {
        return $this->payment->payment_status === 'failed';
    }

    /**
     * Verificar si debe enviar notificación al cliente
     */
    public function shouldNotifyCustomer(): bool
    {
        return $this->isSuccessful() || $this->isFailed();
    }

    /**
     * Verificar si debe actualizar estado de la devolución
     */
    public function shouldUpdateReturnStatus(): bool
    {
        return $this->isSuccessful() && !$this->return->is_refunded;
    }

    /**
     * Obtener el monto total reembolsado para esta devolución
     */
    public function getTotalRefundedAmount(): float
    {
        return $this->return->payments()
            ->where('payment_status', 'completed')
            ->sum('amount');
    }

    /**
     * Verificar si la devolución está completamente reembolsada
     */
    public function isFullyRefunded(): bool
    {
        // Esto requeriría conocer el monto original del pedido
        // Se implementaría según la lógica de negocio específica
        return $this->getTotalRefundedAmount() > 0;
    }
}

<?php

namespace App\Events\Return;

use App\Models\Return\ReturnRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReturnCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $return;
    public $completionType;
    public $completedBy;
    public $totalAmount;
    public $metadata;

    public function __construct(
        ReturnRequest $return,
        string $completionType = 'refund',
        int $completedBy = 1,
        float $totalAmount = 0.0,
        array $metadata = []
    ) {
        $this->return = $return;
        $this->completionType = $completionType; // refund, replacement, repair
        $this->completedBy = $completedBy;
        $this->totalAmount = $totalAmount;
        $this->metadata = $metadata;
    }

    /**
     * Obtener datos del evento para logs
     */
    public function getEventData(): array
    {
        return [
            'event' => 'return_completed',
            'return_id' => $this->return->id_return_request,
            'order_id' => $this->return->id_order,
            'customer_email' => $this->return->email,
            'completion_type' => $this->completionType,
            'total_amount' => $this->totalAmount,
            'processing_time_days' => $this->getProcessingTimeDays(),
            'completed_by' => $this->completedBy,
            'metadata' => $this->metadata,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Calcular días de procesamiento
     */
    public function getProcessingTimeDays(): int
    {
        return $this->return->created_at->diffInDays(now());
    }

    /**
     * Verificar si es un reembolso
     */
    public function isRefund(): bool
    {
        return $this->completionType === 'refund';
    }

    /**
     * Verificar si es un reemplazo
     */
    public function isReplacement(): bool
    {
        return $this->completionType === 'replacement';
    }

    /**
     * Verificar si es una reparación
     */
    public function isRepair(): bool
    {
        return $this->completionType === 'repair';
    }

    /**
     * Verificar si debe actualizar inventario
     */
    public function shouldUpdateInventory(): bool
    {
        return $this->isReplacement() || $this->isRepair();
    }

    /**
     * Verificar si debe procesar reembolso automático
     */
    public function shouldProcessRefund(): bool
    {
        return $this->isRefund() && $this->totalAmount > 0;
    }

    /**
     * Obtener métricas de satisfacción
     */
    public function getSatisfactionMetrics(): array
    {
        $processingTime = $this->getProcessingTimeDays();

        return [
            'processing_time_days' => $processingTime,
            'is_within_sla' => $processingTime <= 7, // SLA de 7 días
            'completion_type' => $this->completionType,
            'customer_waited_long' => $processingTime > 14
        ];
    }
}

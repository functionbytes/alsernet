<?php

namespace App\Events\Return;

use App\Models\Return\ReturnRequest;
use App\Models\Return\ReturnStatus;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReturnStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $return;
    public $previousStatus;
    public $newStatus;
    public $changedBy;
    public $description;
    public $metadata;

    public function __construct(
        ReturnRequest $return,
        ReturnStatus $previousStatus,
        ReturnStatus $newStatus,
        int $changedBy = 1,
        string $description = '',
        array $metadata = []
    ) {
        $this->return = $return;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
        $this->changedBy = $changedBy;
        $this->description = $description;
        $this->metadata = $metadata;
    }

    /**
     * Obtener datos del evento para logs
     */
    public function getEventData(): array
    {
        return [
            'event' => 'return_status_changed',
            'return_id' => $this->return->id_return_request,
            'order_id' => $this->return->id_order,
            'customer_email' => $this->return->email,
            'previous_status' => [
                'id' => $this->previousStatus->id_return_status,
                'name' => $this->previousStatus->getTranslation()->name ?? 'Desconocido',
                'state' => $this->previousStatus->state->name
            ],
            'new_status' => [
                'id' => $this->newStatus->id_return_status,
                'name' => $this->newStatus->getTranslation()->name ?? 'Desconocido',
                'state' => $this->newStatus->state->name
            ],
            'changed_by' => $this->changedBy,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Verificar si debe enviar email al cliente
     */
    public function shouldNotifyCustomer(): bool
    {
        return $this->newStatus->send_email &&
            $this->newStatus->shown_to_customer &&
            config('returns.notifications.notify_customer_on_status_change', true);
    }

    /**
     * Verificar si debe actualizar estado de reembolso
     */
    public function shouldUpdateRefundStatus(): bool
    {
        return $this->newStatus->is_refunded && !$this->return->is_refunded;
    }

    /**
     * Verificar si el estado cambió a completado
     */
    public function isCompleted(): bool
    {
        return $this->newStatus->state->name === 'Close' &&
            $this->newStatus->color === '#28a745'; // Verde = completado exitoso
    }

    /**
     * Verificar si el estado cambió a rechazado
     */
    public function isRejected(): bool
    {
        return $this->newStatus->state->name === 'Close' &&
            $this->newStatus->color === '#dc3545'; // Rojo = rechazado
    }

    /**
     * Obtener el tipo de transición
     */
    public function getTransitionType(): string
    {
        $fromState = $this->previousStatus->id_return_state;
        $toState = $this->newStatus->id_return_state;

        if ($toState > $fromState) {
            return 'progress'; // Avanza en el flujo
        } elseif ($toState < $fromState) {
            return 'regression'; // Retrocede en el flujo
        } else {
            return 'lateral'; // Cambio dentro del mismo estado
        }
    }
}

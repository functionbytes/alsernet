<?php

namespace App\Events\Return;

use App\Models\Return\ReturnRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReturnCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $return;
    public $createdBy;
    public $ipAddress;
    public $userAgent;

    public function __construct(ReturnRequest $return, string $createdBy = 'web', ?string $ipAddress = null, ?string $userAgent = null)
    {
        $this->return = $return;
        $this->createdBy = $createdBy;
        $this->ipAddress = $ipAddress ?? request()->ip();
        $this->userAgent = $userAgent ?? request()->userAgent();
    }

    /**
     * Obtener datos del evento para logs
     */
    public function getEventData(): array
    {
        return [
            'event' => 'return_created',
            'return_id' => $this->return->id_return_request,
            'order_id' => $this->return->id_order,
            'customer_email' => $this->return->email,
            'return_type' => $this->return->id_return_type,
            'return_reason' => $this->return->id_return_reason,
            'logistics_mode' => $this->return->logistics_mode,
            'created_by' => $this->createdBy,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Verificar si debe enviar notificaciones
     */
    public function shouldSendNotifications(): bool
    {
        return config('returns.notifications.notify_admin_on_new_return', true) &&
            config('returns.send_confirmation_email', true);
    }

    /**
     * Verificar si debe generar PDF
     */
    public function shouldGeneratePDF(): bool
    {
        return !empty($this->return->email) &&
            in_array($this->createdBy, ['admin', 'callcenter', 'web']);
    }
}

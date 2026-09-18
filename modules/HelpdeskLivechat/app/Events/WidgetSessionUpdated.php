<?php

namespace Modules\HelpdeskLivechat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskLivechat\Models\WidgetSession;

class WidgetSessionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly WidgetSession $session,
        public readonly int $conversationId
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('helpdesk.conversation.'.$this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return 'widget.session.updated';
    }

    public function broadcastWith(): array
    {
        $device = $this->session->device ?? [];

        return [
            'current_url' => $this->session->current_url,
            'browser' => $device['browser'] ?? null,
            'os' => $device['os'] ?? null,
            // Producto que el visitante está viendo — el agente lo ve en vivo en
            // el panel de la conversación (covisualización estilo Oct8ne).
            'current_product' => $this->session->current_product,
            'last_activity_at' => $this->session->last_activity_at?->toIso8601String(),
        ];
    }
}

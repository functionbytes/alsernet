<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\SlaBreached;
use Modules\Helpdesk\Services\SlackNotificationService;

class NotifySlackOnSlaBreached implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        private readonly SlackNotificationService $slack
    ) {}

    public function handle(SlaBreached $event): void
    {
        $conv = $event->conversation;

        $this->slack->send('sla_breach', [
            'Conversacion ID' => $conv->id,
            'Canal' => $conv->channel,
            'Asignado a' => $conv->assignee?->name ?? 'Sin asignar',
            'Creada' => $conv->created_at?->format('d/m/Y H:i'),
        ]);
    }

    public function failed(SlaBreached $event, \Throwable $exception): void
    {
        Log::error('NotifySlackOnSlaBreached failed', [
            'conversation_id' => $event->conversation->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

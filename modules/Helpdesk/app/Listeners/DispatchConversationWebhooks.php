<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Jobs\DispatchWebhookJob;
use Modules\Helpdesk\Models\Webhook;

class DispatchConversationWebhooks implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'webhooks';

    public int $tries = 1;

    /**
     * Handle any conversation event and dispatch to matching webhooks.
     *
     * @param  object  $event  ConversationCreated|ConversationUpdated
     */
    public function handle(object $event): void
    {
        $conversation = $event->conversation;
        $eventName = $this->resolveEventName($event);

        if (! $eventName) {
            return;
        }

        $payload = [
            'id' => $conversation->id,
            'subject' => $conversation->subject,
            'priority' => $conversation->priority,
            'channel' => $conversation->channel,
            'status' => $conversation->status?->slug ?? $conversation->status?->name,
            'assignee_id' => $conversation->assignee_id,
            'customer_id' => $conversation->customer_id,
            'is_archived' => $conversation->is_archived,
            'created_at' => $conversation->created_at?->toIso8601String(),
            'updated_at' => $conversation->updated_at?->toIso8601String(),
            'closed_at' => $conversation->closed_at?->toIso8601String(),
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
        ];

        Webhook::query()
            ->where('is_active', true)
            ->get()
            ->each(function (Webhook $webhook) use ($eventName, $payload) {
                if ($webhook->shouldFireFor($eventName)) {
                    DispatchWebhookJob::dispatch($webhook->id, $eventName, $payload);
                }
            });
    }

    public function failed(object $event, \Throwable $exception): void
    {
        Log::error('DispatchConversationWebhooks listener failed', [
            'event' => get_class($event),
            'error' => $exception->getMessage(),
        ]);
    }

    private function resolveEventName(object $event): ?string
    {
        $class = class_basename($event);

        return match ($class) {
            'ConversationCreated' => 'conversation.created',
            'ConversationUpdated' => 'conversation.updated',
            default => null,
        };
    }
}

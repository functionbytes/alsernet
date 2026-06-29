<?php

namespace Modules\Reviews\Listeners;

use Modules\Reviews\Events\ReplyPublished;
use Modules\Reviews\Services\OutboundWebhookService;

class DispatchWebhookOnReplyPublished
{
    public function __construct(private readonly OutboundWebhookService $webhookService) {}

    public function handle(ReplyPublished $event): void
    {
        $this->webhookService->dispatch('reply.published', [
            'event' => 'reply.published',
            'reply_id' => $event->reply->id,
            'review_id' => $event->reply->review_id,
            'body' => $event->reply->body,
            'published_at' => now()->toIso8601String(),
        ]);
    }
}

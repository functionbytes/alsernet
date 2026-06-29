<?php

namespace Modules\HelpdeskSocial\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Events\IntentClassified;

class AutoTagOnIntentClassified implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-events';

    public int $tries = 3;

    public function handle(IntentClassified $event): void
    {
        $comment = $event->comment;
        $intent = $event->intent;

        $tags = match ($intent->intent) {
            'complaint' => ['reclamo', 'atencion-urgente'],
            'purchase_interest' => ['venta', 'lead'],
            'spam' => ['spam-auto'],
            default => [$intent->intent],
        };

        $meta = $comment->metadata ?? [];
        $meta['auto_tags'] = array_merge($meta['auto_tags'] ?? [], $tags);
        $comment->update(['metadata' => $meta]);

        Log::info('AutoTagOnIntentClassified: tags applied', [
            'comment_id' => $comment->id,
            'intent' => $intent->intent,
            'tags' => $tags,
        ]);
    }

    public function failed(IntentClassified $event, \Throwable $exception): void
    {
        Log::error('AutoTagOnIntentClassified failed', [
            'comment_id' => $event->comment->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}

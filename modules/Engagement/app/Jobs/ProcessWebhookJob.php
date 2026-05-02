<?php

namespace Modules\Engagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Engagement\Models\WebhookLog;
use Modules\Engagement\Services\PlatformWebhookHandler;
use Throwable;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // we manage retries via WebhookLog state

    public int $timeout = 60;

    public function __construct(
        public readonly int $webhookLogId,
    ) {
        $this->onQueue('helpdesklivechat');
    }

    public function handle(PlatformWebhookHandler $handler): void
    {
        $log = WebhookLog::query()->with('integration')->find($this->webhookLogId);

        if (! $log || ! $log->integration) {
            return;
        }

        $log->increment('attempts');

        try {
            $handler->handle($log->integration, $log->topic, $log->payload);

            $log->update([
                'status' => WebhookLog::STATUS_PROCESSED,
                'processed_at' => now(),
                'last_error' => null,
            ]);
        } catch (Throwable $e) {
            $isDead = $log->attempts >= WebhookLog::MAX_ATTEMPTS;

            $log->update([
                'status' => $isDead ? WebhookLog::STATUS_DEAD : WebhookLog::STATUS_FAILED,
                'last_error' => mb_substr($e->getMessage(), 0, 500),
            ]);

            Log::warning('Webhook processing failed', [
                'log_id' => $log->id,
                'integration_id' => $log->platform_integration_id,
                'topic' => $log->topic,
                'attempt' => $log->attempts,
                'is_dead' => $isDead,
                'error' => $e->getMessage(),
            ]);

            if (! $isDead) {
                $delaySeconds = (int) pow(2, $log->attempts) * 10; // 20, 40, 80, 160s
                static::dispatch($log->id)->delay(now()->addSeconds($delaySeconds));
            }
        }
    }
}

<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Services\WhatsAppBusinessService;

/**
 * Sends the WhatsApp read receipt for an inbound message.
 *
 * Runs OUT OF BAND so the webhook flow can broadcast the message to the agent
 * immediately — the Graph API call has no bearing on that broadcast and was
 * previously adding its full network latency (and retries on failure) to
 * every inbound message before the job could finish.
 */
class MarkWhatsAppMessageReadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 30;

    public int $backoff = 10;

    public function __construct(
        private readonly string $messageId,
    ) {
        $this->onQueue('helpdesk-webhooks');
    }

    public function handle(WhatsAppBusinessService $whatsapp): void
    {
        $whatsapp->markAsRead($this->messageId);
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('MarkWhatsAppMessageReadJob failed', [
            'message_id' => $this->messageId,
            'error' => $exception->getMessage(),
        ]);
    }
}

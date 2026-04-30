<?php

namespace Modules\Chat\Jobs\Webhooks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Models\Conversations\ConversationMessage;

class ProcessWhatsAppStatusUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     *
     * @param  array  $webhookData  Full webhook payload
     * @param  array  $status  Status update data from webhook
     */
    public function __construct(
        public array $webhookData,
        public array $status
    ) {
        $this->onQueue('webhooks');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $messageId = $this->status['id'] ?? null;
        $statusType = $this->status['status'] ?? null;
        $recipientId = $this->status['recipient_id'] ?? null;

        if (! $messageId || ! $statusType) {
            Log::warning('WhatsApp status update missing required fields', [
                'status' => $this->status,
            ]);

            return;
        }

        Log::info('Processing WhatsApp status update', [
            'message_id' => $messageId,
            'status' => $statusType,
            'recipient_id' => $recipientId,
        ]);

        // Find message by source_id (WhatsApp message ID)
        $message = ConversationMessage::where('source_id', $messageId)->first();

        if (! $message) {
            Log::warning('WhatsApp message not found for status update', [
                'message_id' => $messageId,
                'status' => $statusType,
            ]);

            return;
        }

        // Map WhatsApp status to internal status
        $updates = ['status' => $statusType];

        match ($statusType) {
            'sent' => $updates['sent_at'] = now(),
            'delivered' => $updates['delivered_at'] = now(),
            'read' => $updates['read_at'] = now(),
            'failed' => [
                $updates['failed_at'] = now(),
                $updates['status'] = 'failed',
                $updates['error_message'] = $this->status['errors'][0]['title'] ?? 'Message failed',
            ],
            default => null,
        };

        // Update message without triggering events
        $message->updateQuietly($updates);

        Log::info('WhatsApp message status updated', [
            'message_id' => $message->id,
            'source_id' => $messageId,
            'status' => $statusType,
            'conversation_id' => $message->conversation_id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(static::class.' failed', [
            'message_id' => $this->status['id'] ?? null,
            'status' => $this->status['status'] ?? null,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

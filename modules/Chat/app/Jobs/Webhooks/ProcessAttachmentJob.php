<?php

namespace Modules\Chat\Jobs\Webhooks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Models\Conversations\ConversationMessage;

class ProcessAttachmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public array $backoff = [10, 30];

    /**
     * Create a new job instance.
     *
     * @param  ConversationMessage  $message  The message to attach media to
     * @param  array  $attachment  The attachment data from webhook
     */
    public function __construct(
        public ConversationMessage $message,
        public array $attachment
    ) {
        $this->onQueue('attachments');
    }

    /**
     * Execute the job.
     *
     * Downloads and stores a single attachment asynchronously.
     * Runs in parallel with other attachment jobs for maximum throughput.
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            $url = $this->extractUrl($this->attachment);
            $type = $this->attachment['type'] ?? 'file';

            if (! $url) {
                Log::warning('Attachment URL not found', [
                    'message_id' => $this->message->id,
                    'attachment' => $this->attachment,
                ]);

                return;
            }

            // Download file with timeout
            $response = Http::timeout(30)->get($url);

            if ($response->failed()) {
                Log::warning('Failed to download attachment', [
                    'message_id' => $this->message->id,
                    'url' => $url,
                    'type' => $type,
                    'status' => $response->status(),
                ]);

                return;
            }

            // Generate filename
            $filename = basename(parse_url($url, PHP_URL_PATH)) ?: $type.'.'.time().'-'.uniqid();

            // Store with Spatie Media Library
            $this->message->addMediaFromString($response->body())
                ->usingFileName($filename)
                ->toMediaCollection('attachments');

            $endTime = microtime(true);
            $processingTime = ($endTime - $startTime) * 1000;

            Log::info('Attachment processed', [
                'message_id' => $this->message->id,
                'type' => $type,
                'filename' => $filename,
                'size_bytes' => strlen($response->body()),
                'processing_time_ms' => round($processingTime, 2),
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing attachment', [
                'message_id' => $this->message->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Extract URL from attachment data (supports Facebook/Instagram formats).
     */
    private function extractUrl(array $attachment): ?string
    {
        // Facebook format
        if (isset($attachment['payload']['url'])) {
            return $attachment['payload']['url'];
        }

        // Instagram format
        if (isset($attachment['media']['image'])) {
            return $attachment['media']['image'];
        }

        if (isset($attachment['media']['video'])) {
            return $attachment['media']['video'];
        }

        return null;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(static::class.' failed', [
            'message_id' => $this->message->id,
            'attachment_type' => $this->attachment['type'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }
}

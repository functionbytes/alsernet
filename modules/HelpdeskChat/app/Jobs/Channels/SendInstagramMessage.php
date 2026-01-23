<?php

namespace Modules\HelpdeskChat\Jobs\Channels;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChat\Models\Channels\Instagram;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;
use Modules\HelpdeskChat\Services\Channels\Instagram\ApiClient;

class SendInstagramMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ConversationMessage $message,
        public Instagram $instagram,
        public string $recipientId
    ) {
        $this->onQueue('messages');
    }

    /**
     * Execute the job.
     */
    public function handle(ApiClient $apiClient): void
    {
        try {
            // Check if token needs refresh
            if ($this->instagram->needsTokenRefresh()) {
                $apiClient->refreshAccessToken($this->instagram);
            }

            // Instagram Messaging API requires the Facebook Page ID, not Instagram Account ID
            $facebookPage = $this->instagram->facebookPage;
            if (! $facebookPage) {
                throw new \Exception('Instagram account must be linked to a Facebook Page');
            }

            $response = $apiClient->sendMessage(
                $this->instagram->page_access_token,
                $facebookPage->page_id, // Use Facebook Page ID instead of Instagram ID
                $this->recipientId,
                $this->message->content
            );

            $this->message->update([
                'source_id' => $response['message_id'] ?? null,
                'status' => 'sent',
            ]);

            Log::info('Instagram message sent successfully', [
                'message_id' => $this->message->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Instagram message', [
                'message_id' => $this->message->id,
                'error' => $e->getMessage(),
            ]);

            $this->message->update(['status' => 'failed']);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->message->update(['status' => 'failed']);
    }
}

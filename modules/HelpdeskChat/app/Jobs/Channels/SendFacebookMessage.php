<?php

namespace Modules\HelpdeskChat\Jobs\Channels;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChat\Models\Channels\Facebook;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;
use Modules\HelpdeskChat\Services\Channels\Facebook\ApiClient;

class SendFacebookMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ConversationMessage $message,
        public Facebook $facebookPage,
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
            $response = $apiClient->sendMessage(
                $this->facebookPage->page_access_token,
                $this->recipientId,
                $this->message->content
            );

            $this->message->update([
                'source_id' => $response['message_id'] ?? null,
                'status' => 'sent',
            ]);

            Log::info('Facebook message sent successfully', [
                'message_id' => $this->message->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Facebook message', [
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

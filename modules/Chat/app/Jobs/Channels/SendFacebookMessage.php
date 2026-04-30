<?php

namespace Modules\Chat\Jobs\Channels;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Models\Channels\Facebook;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Services\Channels\Facebook\ApiClient;

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
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(ApiClient $apiClient): void
    {
        try {
            $pageToken = $this->facebookPage->page_access_token;
            $messageId = null;

            // Send text message if content exists
            if (! empty($this->message->content)) {
                $response = $apiClient->sendMessage(
                    $pageToken,
                    $this->recipientId,
                    $this->message->content
                );
                $messageId = $response['message_id'] ?? null;
            }

            // Send attachments if present
            if ($this->message->hasAttachments()) {
                $this->message->getMedia('attachments')->each(function ($media) use ($apiClient, $pageToken) {
                    $type = $this->getAttachmentType($media->mime_type);
                    $url = $this->getPublicAttachmentUrl($media);

                    try {
                        $apiClient->sendAttachment(
                            $pageToken,
                            $this->recipientId,
                            $type,
                            $url
                        );

                        Log::info('Facebook attachment sent', [
                            'message_id' => $this->message->id,
                            'attachment_id' => $media->id,
                            'file_name' => $media->file_name,
                            'url' => $url,
                            'mime_type' => $media->mime_type,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send Facebook attachment', [
                            'message_id' => $this->message->id,
                            'attachment_id' => $media->id,
                            'file_name' => $media->file_name,
                            'url' => $url,
                            'error' => $e->getMessage(),
                        ]);
                    }
                });
            }

            $this->message->updateQuietly([
                'source_id' => $messageId,
                'status' => 'sent',
            ]);

            Log::info('Facebook message sent successfully', [
                'message_id' => $this->message->id,
                'has_attachments' => $this->message->hasAttachments(),
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

    /**
     * Get public URL for attachment, replacing local domain with public webhook domain.
     */
    private function getPublicAttachmentUrl($media): string
    {
        $localUrl = $media->getFullUrl();
        $webhookUrl = env('WEBHOOK_URL', config('app.url'));
        $appUrl = config('app.url');

        // Replace local app.url with public webhook URL if they differ
        if ($appUrl !== $webhookUrl) {
            return str_replace($appUrl, $webhookUrl, $localUrl);
        }

        return $localUrl;
    }

    /**
     * Map MIME type to Facebook attachment type.
     */
    private function getAttachmentType(string $mimeType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'video/') => 'video',
            str_starts_with($mimeType, 'audio/') => 'audio',
            default => 'file',
        };
    }

    public function failed(\Throwable $exception): void
    {
        $this->message->updateQuietly(['status' => 'failed']);

        Log::error(static::class.' failed', [
            'message_id' => $this->message->id,
            'facebook_page_id' => $this->facebookPage->id,
            'recipient_id' => $this->recipientId,
            'error' => $exception->getMessage(),
        ]);
    }
}

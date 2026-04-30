<?php

namespace Modules\Chat\Jobs\Channels;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Events\MessageSent;
use Modules\Chat\Models\Channels\Instagram;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Services\Channels\Instagram\ApiClient;

class SendInstagramMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    public function __construct(
        public ConversationMessage $message,
        public Instagram $instagram,
        public string $recipientId
    ) {
        $this->onQueue('default');
    }

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

            $messageId = null;

            // Send text message if content exists
            if (! empty($this->message->content) &&
                strpos($this->message->content, 'ATTACHMENT') === false) {
                $response = $apiClient->sendMessage(
                    $this->instagram->page_access_token,
                    $facebookPage->page_id,
                    $this->recipientId,
                    $this->message->content
                );
                $messageId = $response['message_id'] ?? null;
            }

            // Send attachments if present
            if ($this->message->hasAttachments()) {
                $this->message->getMedia('attachments')->each(function ($media) use ($apiClient, $facebookPage) {
                    $type = $this->getAttachmentType($media->mime_type);
                    $url = $this->getPublicAttachmentUrl($media);

                    try {
                        $apiClient->sendAttachment(
                            $this->instagram->page_access_token,
                            $facebookPage->page_id,
                            $this->recipientId,
                            $type,
                            $url
                        );

                        Log::info('Instagram attachment sent', [
                            'message_id' => $this->message->id,
                            'attachment_id' => $media->id,
                            'file_name' => $media->file_name,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send Instagram attachment', [
                            'message_id' => $this->message->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                });
            }

            $this->message->updateQuietly([
                'source_id' => $messageId,
                'status' => 'sent',
            ]);

            // Broadcast message sent event for real-time UI update
            MessageSent::dispatch($this->message);

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

    private function getPublicAttachmentUrl($media): string
    {
        $localUrl = $media->getFullUrl();
        $webhookUrl = env('WEBHOOK_URL', config('app.url'));
        $appUrl = config('app.url');

        if ($appUrl !== $webhookUrl) {
            return str_replace($appUrl, $webhookUrl, $localUrl);
        }

        return $localUrl;
    }

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
            'instagram_id' => $this->instagram->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

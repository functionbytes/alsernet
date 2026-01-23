<?php

namespace Modules\HelpdeskChat\Jobs\Channels;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChat\Models\Channels\Whatsapp;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;
use Modules\HelpdeskChat\Services\Channels\Whatsapp\Providers\CloudApiService;
use Modules\HelpdeskChat\Services\Channels\Whatsapp\Providers\Dialog360Service;
use Modules\HelpdeskChat\Services\Channels\Whatsapp\Providers\EvolutionApiService;

class SendWhatsappMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(
        public ConversationMessage $message,
        public Whatsapp $whatsapp,
        public string $recipientPhone
    ) {
        $this->onQueue('messages');
    }

    public function handle(Dialog360Service $dialog360, CloudApiService $cloudApi, EvolutionApiService $evolutionApi): void
    {
        try {
            // Check if message has attachments
            $hasAttachments = $this->message->hasAttachments();

            if ($hasAttachments && $this->whatsapp->isEvolutionApi()) {
                // Send media message
                $media = $this->message->getMedia('attachments')->first();
                $mediaUrl = $media->getFullUrl();
                $caption = $this->message->content;

                $response = $evolutionApi->sendMediaMessage(
                    $this->whatsapp->getApiUrl(),
                    $this->whatsapp->getApiCredential(),
                    $this->whatsapp->getInstanceName(),
                    $this->recipientPhone,
                    $mediaUrl,
                    $this->message->content_type,
                    $caption
                );
                $sourceId = $response['key']['id'] ?? null;
            } elseif ($this->whatsapp->is360Dialog()) {
                $response = $dialog360->sendTextMessage(
                    $this->whatsapp->getApiCredential(),
                    $this->recipientPhone,
                    $this->message->content
                );
                $sourceId = $response['messages'][0]['id'] ?? null;
            } elseif ($this->whatsapp->isEvolutionApi()) {
                $response = $evolutionApi->sendTextMessage(
                    $this->whatsapp->getApiUrl(),
                    $this->whatsapp->getApiCredential(),
                    $this->whatsapp->getInstanceName(),
                    $this->recipientPhone,
                    $this->message->content
                );
                $sourceId = $response['key']['id'] ?? null;
            } else {
                $response = $cloudApi->sendTextMessage(
                    $this->whatsapp->getApiCredential(),
                    $this->whatsapp->getPhoneNumberId(),
                    $this->recipientPhone,
                    $this->message->content
                );
                $sourceId = $response['messages'][0]['id'] ?? null;
            }

            $this->message->update([
                'source_id' => $sourceId,
                'status' => 'sent',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp message', [
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

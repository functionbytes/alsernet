<?php

namespace Modules\HelpdeskChat\Services\Channels\Whatsapp\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudApiService
{
    protected ?string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('channels.whatsapp.providers.whatsapp_cloud.api_url', 'https://graph.facebook.com/v17.0');
    }

    /**
     * Send a text message.
     */
    public function sendTextMessage(string $accessToken, string $phoneNumberId, string $to, string $message): array
    {
        $response = Http::withToken($accessToken)
            ->post("{$this->apiUrl}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $message,
                ],
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to send WhatsApp Cloud message: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Send a template message.
     */
    public function sendTemplateMessage(string $accessToken, string $phoneNumberId, string $to, string $templateName, string $languageCode = 'en', array $parameters = []): array
    {
        $response = Http::withToken($accessToken)
            ->post("{$this->apiUrl}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $languageCode,
                    ],
                    'components' => $this->buildTemplateComponents($parameters),
                ],
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to send WhatsApp Cloud template: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Get message templates.
     */
    public function getTemplates(string $accessToken, string $businessAccountId): array
    {
        $response = Http::withToken($accessToken)
            ->get("{$this->apiUrl}/{$businessAccountId}/message_templates");

        if ($response->failed()) {
            Log::error('Failed to get WhatsApp Cloud templates', [
                'response' => $response->body(),
            ]);

            return [];
        }

        return $response->json('data', []);
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(string $accessToken, string $phoneNumberId, string $messageId): bool
    {
        $response = Http::withToken($accessToken)
            ->post("{$this->apiUrl}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'status' => 'read',
                'message_id' => $messageId,
            ]);

        return $response->successful();
    }

    /**
     * Verify webhook signature.
     */
    public function verifySignature(string $payload, string $signature, string $appSecret): bool
    {
        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, $appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Build template components from parameters.
     */
    protected function buildTemplateComponents(array $parameters): array
    {
        if (empty($parameters)) {
            return [];
        }

        return [
            [
                'type' => 'body',
                'parameters' => array_map(function ($param) {
                    return [
                        'type' => 'text',
                        'text' => $param,
                    ];
                }, $parameters),
            ],
        ];
    }

    /**
     * Check if message is within 24-hour window.
     */
    public function isWithinMessageWindow(\DateTimeInterface $lastMessageTime): bool
    {
        $now = now();
        $hoursSinceLastMessage = $now->diffInHours($lastMessageTime);

        return $hoursSinceLastMessage < 24;
    }

    /**
     * Get phone number information.
     */
    public function getPhoneNumberInfo(string $accessToken, string $phoneNumberId): array
    {
        $response = Http::withToken($accessToken)
            ->get("{$this->apiUrl}/{$phoneNumberId}");

        if ($response->failed()) {
            Log::error('Failed to get phone number info', [
                'response' => $response->body(),
            ]);

            return [];
        }

        return $response->json();
    }
}

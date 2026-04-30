<?php

namespace Modules\Chat\Services\Channels\Whatsapp\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Dialog360Service
{
    protected ?string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('channels.whatsapp.providers.360dialog.api_url', 'https://waba.360dialog.io/v1');
    }

    /**
     * Send a text message.
     */
    public function sendTextMessage(string $apiKey, string $to, string $message): array
    {
        $response = Http::withHeaders([
            'D360-API-KEY' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl.'/messages', [
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $message,
            ],
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to send 360Dialog message: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Send a template message.
     */
    public function sendTemplateMessage(string $apiKey, string $to, string $templateName, array $parameters = []): array
    {
        $response = Http::withHeaders([
            'D360-API-KEY' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl.'/messages', [
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => 'en',
                ],
                'components' => $this->buildTemplateComponents($parameters),
            ],
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to send 360Dialog template: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Get message templates.
     */
    public function getTemplates(string $apiKey): array
    {
        $response = Http::withHeaders([
            'D360-API-KEY' => $apiKey,
        ])->get($this->apiUrl.'/configs/templates');

        if ($response->failed()) {
            Log::error('Failed to get 360Dialog templates', [
                'response' => $response->body(),
            ]);

            return [];
        }

        return $response->json('waba_templates', []);
    }

    /**
     * Verify webhook signature.
     */
    public function verifySignature(string $payload, string $signature, string $apiKey): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $apiKey);

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
}

<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Exceptions\WhatsAppHsmException;
use Modules\Helpdesk\Models\Conversation;

class WhatsAppHsmService
{
    public function send(Conversation $conversation, string $templateName, array $variables): array
    {
        $accessToken = config('helpdesk.whatsapp.access_token');
        $phoneNumberId = config('helpdesk.whatsapp.phone_number_id');
        $apiVersion = config('helpdesk.whatsapp.api_version', 'v18.0');

        if (! filled($accessToken) || ! filled($phoneNumberId)) {
            Log::warning('WhatsApp HSM not configured — returning mock response', [
                'conversation_id' => $conversation->id,
                'template' => $templateName,
            ]);

            return ['mocked' => true, 'id' => 'wa-mock-'.uniqid()];
        }

        $phone = $conversation->customer->whatsapp_phone
            ?? $conversation->customer->phone
            ?? null;

        if (! filled($phone)) {
            throw new WhatsAppHsmException('Customer has no WhatsApp phone number.', [
                'conversation_id' => $conversation->id,
            ]);
        }

        $components = [];
        if (! empty($variables)) {
            $parameters = array_map(
                fn (string $value) => ['type' => 'text', 'text' => $value],
                array_values($variables)
            );

            $components[] = [
                'type' => 'body',
                'parameters' => $parameters,
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => 'es'],
                'components' => $components,
            ],
        ];

        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

        $response = Http::withToken($accessToken)
            ->timeout(15)
            ->post($url, $payload);

        if ($response->failed()) {
            $error = $response->json('error.message', 'Unknown error');
            $code = $response->json('error.code', $response->status());

            throw new WhatsAppHsmException("WhatsApp API error ({$code}): {$error}", [
                'conversation_id' => $conversation->id,
                'template' => $templateName,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        }

        return [
            'id' => $response->json('messages.0.id'),
            'status' => 'sent',
        ];
    }
}

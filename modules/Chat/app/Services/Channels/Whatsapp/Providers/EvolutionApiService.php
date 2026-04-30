<?php

namespace Modules\Chat\Services\Channels\Whatsapp\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionApiService
{
    /**
     * Create a new Evolution API instance.
     */
    public function createInstance(string $apiUrl, string $apiKey, string $instanceName, string $webhookUrl): array
    {
        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])->post("{$apiUrl}/instance/create", [
            'instanceName' => $instanceName,
            'integration' => 'WHATSAPP_BAILEYS',
            'qrcode' => true,
            'webhookUrl' => $webhookUrl,
            'webhookByEvents' => false,
            'webhookEvents' => [
                'QRCODE_UPDATED',
                'MESSAGES_UPSERT',
                'MESSAGES_UPDATE',
                'SEND_MESSAGE',
                'CONNECTION_UPDATE',
            ],
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to create Evolution API instance: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Get instance connection state.
     */
    public function getConnectionState(string $apiUrl, string $apiKey, string $instanceName): array
    {
        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])->get("{$apiUrl}/instance/connectionState/{$instanceName}");

        if ($response->failed()) {
            Log::error('Failed to get Evolution API connection state', [
                'response' => $response->body(),
            ]);

            return ['state' => 'unknown'];
        }

        return $response->json();
    }

    /**
     * Connect to WhatsApp (get QR code).
     */
    public function connect(string $apiUrl, string $apiKey, string $instanceName): array
    {
        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])->get("{$apiUrl}/instance/connect/{$instanceName}");

        if ($response->failed()) {
            throw new \Exception('Failed to connect Evolution API instance: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Send a text message.
     */
    public function sendTextMessage(string $apiUrl, string $apiKey, string $instanceName, string $to, string $message): array
    {
        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])->post("{$apiUrl}/message/sendText/{$instanceName}", [
            'number' => $this->formatPhoneNumber($to),
            'text' => $message,
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to send Evolution API message: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Send media message (image, video, audio, document).
     */
    public function sendMediaMessage(string $apiUrl, string $apiKey, string $instanceName, string $to, string $mediaUrl, string $mediaType = 'image', ?string $caption = null): array
    {
        $payload = [
            'number' => $this->formatPhoneNumber($to),
            'mediaUrl' => $mediaUrl,
        ];

        if ($caption) {
            $payload['caption'] = $caption;
        }

        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])->post("{$apiUrl}/message/sendMedia/{$instanceName}", $payload);

        if ($response->failed()) {
            throw new \Exception('Failed to send Evolution API media: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Send location message.
     */
    public function sendLocationMessage(string $apiUrl, string $apiKey, string $instanceName, string $to, float $latitude, float $longitude, ?string $name = null, ?string $address = null): array
    {
        $payload = [
            'number' => $this->formatPhoneNumber($to),
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];

        if ($name) {
            $payload['name'] = $name;
        }

        if ($address) {
            $payload['address'] = $address;
        }

        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])->post("{$apiUrl}/message/sendLocation/{$instanceName}", $payload);

        if ($response->failed()) {
            throw new \Exception('Failed to send Evolution API location: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Logout from WhatsApp.
     */
    public function logout(string $apiUrl, string $apiKey, string $instanceName): bool
    {
        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])->delete("{$apiUrl}/instance/logout/{$instanceName}");

        return $response->successful();
    }

    /**
     * Restart instance.
     */
    public function restart(string $apiUrl, string $apiKey, string $instanceName): array
    {
        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])->put("{$apiUrl}/instance/restart/{$instanceName}");

        if ($response->failed()) {
            throw new \Exception('Failed to restart instance: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Delete instance.
     */
    public function deleteInstance(string $apiUrl, string $apiKey, string $instanceName): bool
    {
        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])->delete("{$apiUrl}/instance/delete/{$instanceName}");

        return $response->successful();
    }

    /**
     * Fetch contacts from WhatsApp.
     */
    public function fetchContacts(string $apiUrl, string $apiKey, string $instanceName): array
    {
        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])->post("{$apiUrl}/chat/findContacts/{$instanceName}");

        if ($response->failed()) {
            throw new \Exception('Failed to fetch contacts: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Fetch chats from WhatsApp.
     */
    public function fetchChats(string $apiUrl, string $apiKey, string $instanceName): array
    {
        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])->post("{$apiUrl}/chat/findChats/{$instanceName}");

        if ($response->failed()) {
            throw new \Exception('Failed to fetch chats: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Set instance webhook configuration.
     */
    public function setWebhook(string $apiUrl, string $apiKey, string $instanceName, string $webhookUrl, array $events = []): array
    {
        $defaultEvents = [
            'QRCODE_UPDATED',
            'MESSAGES_UPSERT',
            'MESSAGES_UPDATE',
            'SEND_MESSAGE',
            'CONNECTION_UPDATE',
            'CONTACTS_UPSERT',
            'CHATS_UPSERT',
        ];

        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])->post("{$apiUrl}/webhook/set/{$instanceName}", [
            'webhook' => [
                'enabled' => true,
                'url' => $webhookUrl,
                'events' => empty($events) ? $defaultEvents : $events,
                'webhookByEvents' => false,
            ],
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to set Evolution API webhook: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Format phone number for Evolution API (remove + and spaces).
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Evolution API expects phone numbers without + or spaces
        // Example: 5511999999999 instead of +55 11 99999-9999
        return preg_replace('/[^0-9]/', '', $phoneNumber);
    }

    /**
     * Check if instance is within message window (not applicable for Baileys).
     * Evolution API with Baileys doesn't have the 24-hour restriction.
     */
    public function isWithinMessageWindow(): bool
    {
        // Baileys doesn't have 24-hour window restrictions like official API
        return true;
    }

    /**
     * Get instance settings.
     */
    public function getSettings(string $apiUrl, string $apiKey, string $instanceName): array
    {
        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])->get("{$apiUrl}/settings/find/{$instanceName}");

        if ($response->failed()) {
            Log::error('Failed to get Evolution API settings', [
                'response' => $response->body(),
            ]);

            return [];
        }

        return $response->json();
    }

    /**
     * Update instance settings.
     */
    public function updateSettings(string $apiUrl, string $apiKey, string $instanceName, array $settings): array
    {
        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])->post("{$apiUrl}/settings/set/{$instanceName}", $settings);

        if ($response->failed()) {
            throw new \Exception('Failed to update Evolution API settings: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Get Chatwoot integration settings.
     */
    public function getChatwootSettings(string $apiUrl, string $apiKey, string $instanceName): array
    {
        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])->get("{$apiUrl}/chatwoot/find/{$instanceName}");

        if ($response->failed()) {
            Log::error('Failed to get Chatwoot settings', [
                'response' => $response->body(),
            ]);

            return [];
        }

        return $response->json();
    }

    /**
     * Update Chatwoot integration settings.
     */
    public function updateChatwootSettings(string $apiUrl, string $apiKey, string $instanceName, array $settings): array
    {
        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])->post("{$apiUrl}/chatwoot/set/{$instanceName}", $settings);

        if ($response->failed()) {
            throw new \Exception('Failed to update Chatwoot settings: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Download media from Evolution API.
     *
     * @param  string  $messageKey  The message key containing media info
     * @return string|null Binary content of the media file or null if failed
     */
    public function downloadMedia(string $apiUrl, string $apiKey, string $instanceName, array $messageKey): ?string
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $apiKey,
            ])->post("{$apiUrl}/chat/getBase64FromMediaMessage/{$instanceName}", [
                'message' => [
                    'key' => $messageKey,
                ],
                'convertToMp4' => false,
            ]);

            if ($response->failed()) {
                Log::error('Failed to download media from Evolution API', [
                    'response' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            // Evolution API returns base64 encoded media
            if (isset($data['base64'])) {
                // Extract base64 data (remove data:mime/type;base64, prefix if present)
                $base64 = $data['base64'];
                if (str_contains($base64, ',')) {
                    $base64 = explode(',', $base64)[1];
                }

                return base64_decode($base64);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error downloading media from Evolution API', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

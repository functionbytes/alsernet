<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected $fcmServerKey;
    protected $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->fcmServerKey = config('services.fcm.server_key');
    }

    /**
     * Enviar notificación push a un token específico
     */
    public function sendToToken(string $token, array $data, string $deviceType = 'android'): bool
    {
        try {
            $payload = $this->buildPayload($token, $data, $deviceType);

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                $result = $response->json();
                return $result['success'] > 0;
            }

            Log::error('Error enviando push notification: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('Excepción enviando push notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar notificación a múltiples tokens
     */
    public function sendToTokens(array $tokens, array $data): array
    {
        $results = [];

        foreach ($tokens as $token) {
            $results[$token] = $this->sendToToken($token, $data);
        }

        return $results;
    }

    /**
     * Enviar notificación a un topic
     */
    public function sendToTopic(string $topic, array $data): bool
    {
        try {
            $payload = [
                'to' => '/topics/' . $topic,
                'notification' => [
                    'title' => $data['title'] ?? 'Notificación',
                    'body' => $data['body'] ?? '',
                    'icon' => $data['icon'] ?? 'default',
                    'sound' => 'default',
                ],
                'data' => $data['data'] ?? [],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Error enviando push notification a topic: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Construir payload según el tipo de dispositivo
     */
    protected function buildPayload(string $token, array $data, string $deviceType): array
    {
        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $data['title'] ?? 'Notificación',
                'body' => $data['body'] ?? '',
                'icon' => $data['icon'] ?? 'default',
                'sound' => 'default',
            ],
            'data' => $data['data'] ?? [],
        ];

        // Configuraciones específicas por dispositivo
        if ($deviceType === 'ios') {
            $payload['apns'] = [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'badge' => $data['badge'] ?? 1,
                        'sound' => 'default',
                    ],
                ],
            ];
        }

        return $payload;
    }

    /**
     * Suscribir token a un topic
     */
    public function subscribeToTopic(string $token, string $topic): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post('https://iid.googleapis.com/iid/v1/' . $token . '/rel/topics/' . $topic);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Error suscribiendo a topic: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Desuscribir token de un topic
     */
    public function unsubscribeFromTopic(string $token, string $topic): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->delete('https://iid.googleapis.com/iid/v1/' . $token . '/rel/topics/' . $topic);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Error desuscribiendo de topic: ' . $e->getMessage());
            return false;
        }
    }
}

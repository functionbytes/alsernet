<?php

declare(strict_types=1);

namespace Modules\Engagement\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Modules\Engagement\Models\MobileDevice;

class PushNotificationService
{
    private ?Client $fcmClient = null;

    private ?Client $apnClient = null;

    public function __construct()
    {
        $fcmKey = config('engagement.services.fcm.server_key', '');
        if ($fcmKey) {
            $this->fcmClient = new Client([
                'base_uri' => 'https://fcm.googleapis.com/fcm/',
                'headers' => [
                    'Authorization' => 'key='.$fcmKey,
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 10,
            ]);
        }
    }

    /**
     * Send a push notification to a single device.
     *
     * @param  array{title: string, body: string, data?: array}  $payload
     *
     * @throws \RuntimeException
     */
    public function sendToDevice(MobileDevice $device, array $payload): void
    {
        if (! $device->push_enabled) {
            return;
        }

        match ($device->platform) {
            'android' => $this->sendFcm($device->device_token, $payload),
            'ios' => $this->sendApn($device->device_token, $payload),
            default => throw new \RuntimeException("Plataforma '{$device->platform}' no soportada."),
        };

        $device->update(['last_seen_at' => now()]);
    }

    /**
     * Broadcast to all active devices of an inbox.
     *
     * @param  array{title: string, body: string, data?: array}  $payload
     */
    public function broadcastToInbox(int $inboxId, array $payload): int
    {
        $devices = MobileDevice::query()
            ->forInbox($inboxId)
            ->active()
            ->get();

        $sent = 0;
        foreach ($devices as $device) {
            try {
                $this->sendToDevice($device, $payload);
                $sent++;
            } catch (\Throwable) {
                // Log failure silently; production should use queued jobs
            }
        }

        return $sent;
    }

    public function healthCheck(): array
    {
        return [
            'fcm' => $this->fcmClient !== null,
            'apn' => $this->apnClient !== null,
        ];
    }

    /**
     * @throws \RuntimeException
     */
    private function sendFcm(string $token, array $payload): void
    {
        if (! $this->fcmClient) {
            throw new \RuntimeException('FCM no está configurado. Configure FCM_SERVER_KEY.');
        }

        try {
            $this->fcmClient->post('send', [
                'json' => [
                    'to' => $token,
                    'notification' => [
                        'title' => $payload['title'],
                        'body' => $payload['body'],
                    ],
                    'data' => $payload['data'] ?? (object) [],
                    'priority' => 'high',
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Error FCM: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function sendApn(string $token, array $payload): void
    {
        // APN requires certificate-based auth; stubbed for now
        throw new \RuntimeException('APN requiere configuración de certificado. Configure APN_CERT_PATH.');
    }
}

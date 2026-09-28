<?php

namespace Modules\HelpdeskSocial\Jobs;

use App\Models\PushSubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class SendWebPushNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    /**
     * @param  array<string, mixed>  $subscription
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly array $subscription,
        public readonly array $payload,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        if (! class_exists(WebPush::class)) {
            Log::info('Web push skipped: minishlink/web-push is not installed.', [
                'endpoint' => $this->subscription['endpoint'] ?? null,
                'payload' => $this->payload,
            ]);

            return;
        }

        $publicKey = config('services.webpush.public_key');
        $privateKey = config('services.webpush.private_key');

        if (blank($publicKey) || blank($privateKey)) {
            Log::info('Web push skipped: VAPID keys not configured.', [
                'endpoint' => $this->subscription['endpoint'] ?? null,
            ]);

            return;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('services.webpush.subject', config('app.url')),
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        // Columnas reales de push_subscriptions (WebPushService::getSubscriptionsForUsers
        // castea la fila a array): public_key/auth_token, no p256dh/auth.
        try {
            $report = $webPush->sendOneNotification(
                Subscription::create([
                    'endpoint' => $this->subscription['endpoint'],
                    'keys' => [
                        'p256dh' => $this->subscription['public_key'],
                        'auth' => $this->subscription['auth_token'],
                    ],
                    'contentEncoding' => $this->subscription['content_encoding'] ?? 'aes128gcm',
                ]),
                json_encode($this->payload)
            );
        } catch (\Throwable $e) {
            // La librería lanza (no devuelve un MessageSentReport) cuando la
            // suscripción está corrupta/mal formada — p256dh no es un punto
            // EC válido, endpoint truncado, etc. No es un fallo de red
            // reintentable: la suscripción nunca va a poder cifrarse, así
            // que se borra en vez de agotar los 3 reintentos del job en cada
            // evento futuro.
            if (isset($this->subscription['id'])) {
                PushSubscription::where('id', $this->subscription['id'])->delete();
            }

            Log::warning('Web push delivery threw (subscription discarded)', [
                'endpoint' => $this->subscription['endpoint'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if (! $report->isSuccess()) {
            // 404/410: el navegador dio de baja la suscripción (desinstaló,
            // borró datos del sitio, ...) — nunca va a volver a aceptar push,
            // así que se borra en vez de reintentarla en cada evento futuro.
            if ($report->isSubscriptionExpired() && isset($this->subscription['id'])) {
                PushSubscription::where('id', $this->subscription['id'])->delete();
            }

            Log::warning('Web push delivery failed', [
                'endpoint' => $this->subscription['endpoint'] ?? null,
                'reason' => $report->getReason(),
                'expired' => $report->isSubscriptionExpired(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendWebPushNotificationJob failed', [
            'endpoint' => $this->subscription['endpoint'] ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}

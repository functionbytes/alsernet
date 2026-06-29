<?php

namespace Modules\HelpdeskSocial\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\WebPush;

class SendWebPushNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

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

        // TODO: Implement actual dispatch once VAPID keys are configured in config/services.php.
        // Example implementation:
        // $auth = [
        //     'VAPID' => [
        //         'subject' => config('app.url'),
        //         'publicKey' => config('services.webpush.public_key'),
        //         'privateKey' => config('services.webpush.private_key'),
        //     ],
        // ];
        // $webPush = new \Minishlink\WebPush\WebPush($auth);
        // $webPush->queueNotification(
        //     new \Minishlink\WebPush\Subscription(
        //         $this->subscription['endpoint'],
        //         $this->subscription['p256dh'],
        //         $this->subscription['auth'],
        //     ),
        //     json_encode($this->payload)
        // );
        // $webPush->flush();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendWebPushNotificationJob failed', [
            'endpoint' => $this->subscription['endpoint'] ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}

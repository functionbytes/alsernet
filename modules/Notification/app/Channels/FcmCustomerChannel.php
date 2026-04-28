<?php

namespace Modules\Notification\Channels;

use Illuminate\Notifications\Notification;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\CustomerPushToken;
use Modules\Notification\Services\PushNotificationService;

class FcmCustomerChannel
{
    public function __construct(
        private readonly PushNotificationService $pushService
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof Customer) {
            return;
        }

        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $payload = $notification->toFcm($notifiable);

        if (empty($payload)) {
            return;
        }

        $tokens = CustomerPushToken::query()
            ->where('customer_id', $notifiable->id)
            ->where('is_active', true)
            ->get();

        foreach ($tokens as $token) {
            $this->pushService->sendToToken(
                $token->token,
                $payload,
                $token->platform
            );

            $token->forceFill(['last_used_at' => now()])->save();
        }
    }
}

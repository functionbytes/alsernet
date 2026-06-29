<?php

namespace Modules\Notification\Channels;

use Illuminate\Notifications\Notification;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\CustomerPushToken;
use Modules\Notification\Enums\PushResult;
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
            $result = $this->pushService->sendToToken(
                $token->token,
                $payload,
                $token->platform
            );

            if ($result === PushResult::InvalidToken) {
                $token->forceFill(['is_active' => false])->save();

                continue;
            }

            if ($result === PushResult::Success) {
                $token->forceFill(['last_used_at' => now()])->save();
            }
        }
    }
}

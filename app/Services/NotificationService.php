<?php

namespace App\Services;

use App\Models\User;
use App\Models\NotificationSetting;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $pushService;
    protected $smsService;

    public function __construct(
        PushNotificationService $pushService,
        SmsService $smsService
    ) {
        $this->pushService = $pushService;
        $this->smsService = $smsService;
    }

    /**
     * Enviar notificación a un usuario específico
     */
    public function sendToUser(User $user, Notification $notification, array $channels = ['database']): array
    {
        $results = [];

        foreach ($channels as $channel) {
            try {
                if ($user->canReceiveNotification($channel, get_class($notification))) {
                    switch ($channel) {
                        case 'push':
                            $results[$channel] = $this->sendPushNotification($user, $notification);
                            break;
                        case 'sms':
                            $results[$channel] = $this->sendSmsNotification($user, $notification);
                            break;
                        case 'database':
                        case 'mail':
                            $user->notify($notification);
                            $results[$channel] = true;
                            break;
                    }
                } else {
                    $results[$channel] = 'disabled';
                }
            } catch (\Exception $e) {
                Log::error("Error enviando notificación {$channel} a usuario {$user->id}: " . $e->getMessage());
                $results[$channel] = false;
            }
        }

        return $results;
    }

    /**
     * Enviar notificación a múltiples usuarios
     */
    public function sendToUsers(array $users, Notification $notification, array $channels = ['database']): array
    {
        $results = [];

        foreach ($users as $user) {
            $results[$user->id] = $this->sendToUser($user, $notification, $channels);
        }

        return $results;
    }

    /**
     * Enviar notificación push
     */
    protected function sendPushNotification(User $user, Notification $notification): bool
    {
        $tokens = $user->getActivePushTokens();

        if ($tokens->isEmpty()) {
            return false;
        }

        $data = $notification->toPush($user);

        foreach ($tokens as $token) {
            $this->pushService->sendToToken($token->token, $data, $token->device_type);
        }

        return true;
    }

    /**
     * Enviar notificación SMS
     */
    protected function sendSmsNotification(User $user, Notification $notification): bool
    {
        if (!$user->phone) {
            return false;
        }

        $message = $notification->toSms($user);
        return $this->smsService->send($user->phone, $message);
    }

    /**
     * Programar notificación para envío posterior
     */
    public function scheduleNotification(User $user, Notification $notification, \Carbon\Carbon $sendAt, array $channels = ['database']): void
    {
        // Implementar usando queues
        dispatch(function () use ($user, $notification, $channels) {
            $this->sendToUser($user, $notification, $channels);
        })->delay($sendAt);
    }
}

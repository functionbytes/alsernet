<?php

namespace Modules\Notification\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Modules\Notification\Enums\PushResult;

class NotificationService
{
    /**
     * Canales gestionados por el sistema de notificaciones nativo de Laravel.
     * Se despachan en una sola llamada para evitar envíos duplicados.
     */
    private const STANDARD_CHANNELS = ['database', 'mail', 'broadcast'];

    public function __construct(
        protected PushNotificationService $pushService,
        protected SmsService $smsService
    ) {}

    /**
     * @param  string[]  $channels
     * @return array<string, bool|string>
     */
    public function sendToUser(User $user, Notification $notification, array $channels = ['database']): array
    {
        $results = [];
        $standardChannels = [];

        foreach ($channels as $channel) {
            if (! $user->canReceiveNotification($channel, get_class($notification))) {
                $results[$channel] = 'disabled';

                continue;
            }

            if (in_array($channel, self::STANDARD_CHANNELS, true)) {
                $standardChannels[] = $channel;

                continue;
            }

            $results[$channel] = $this->sendCustomChannel($user, $notification, $channel);
        }

        if ($standardChannels !== []) {
            $delivered = $this->sendStandardChannels($user, $notification, $standardChannels);

            foreach ($standardChannels as $channel) {
                $results[$channel] = $delivered;
            }
        }

        return $results;
    }

    /**
     * Despachar los canales nativos (database, mail, broadcast) en una sola llamada,
     * forzando el set de canales habilitados sin reevaluar el via() de la notificación.
     *
     * @param  string[]  $channels
     */
    private function sendStandardChannels(User $user, Notification $notification, array $channels): bool
    {
        try {
            NotificationFacade::sendNow($user, $notification, $channels);

            return true;
        } catch (\Exception $e) {
            Log::error('Error enviando notificación ['.implode(',', $channels)."] a usuario {$user->id}: ".$e->getMessage());

            return false;
        }
    }

    private function sendCustomChannel(User $user, Notification $notification, string $channel): bool
    {
        try {
            return match ($channel) {
                'push' => $this->sendPushNotification($user, $notification),
                'sms' => $this->sendSmsNotification($user, $notification),
                default => false,
            };
        } catch (\Exception $e) {
            Log::error("Error enviando notificación {$channel} a usuario {$user->id}: ".$e->getMessage());

            return false;
        }
    }

    public function sendToUsers(array $users, Notification $notification, array $channels = ['database']): array
    {
        $results = [];

        foreach ($users as $user) {
            $results[$user->id] = $this->sendToUser($user, $notification, $channels);
        }

        return $results;
    }

    protected function sendPushNotification(User $user, Notification $notification): bool
    {
        $tokens = $user->getActivePushTokens();

        if ($tokens->isEmpty()) {
            return false;
        }

        $data = $notification->toPush($user);
        $delivered = false;

        foreach ($tokens as $token) {
            $result = $this->pushService->sendToToken($token->token, $data, $token->device_type);

            if ($result === PushResult::InvalidToken) {
                $token->deactivate();

                continue;
            }

            if ($result === PushResult::Success) {
                $token->activate();
                $delivered = true;
            }
        }

        return $delivered;
    }

    protected function sendSmsNotification(User $user, Notification $notification): bool
    {
        if (! $user->phone) {
            return false;
        }

        $message = $notification->toSms($user);

        return $this->smsService->send($user->phone, $message);
    }
}

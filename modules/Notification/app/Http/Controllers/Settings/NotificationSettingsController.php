<?php

namespace Modules\Notification\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class NotificationSettingsController extends Controller
{
    public function index(): View
    {
        $config = [
            'cleanup' => [
                'enabled' => config('notification.cleanup.enabled', true),
                'days' => config('notification.cleanup.days', 30),
            ],
            'push_notifications' => [
                'enabled' => config('notification.push_notifications.enabled', true),
                'max_retries' => config('notification.push_notifications.max_retries', 3),
            ],
            'channels' => [
                'database' => config('notification.channels.database', true),
                'mail' => config('notification.channels.mail', true),
                'push' => config('notification.channels.push', false),
            ],
            'retention_days' => config('notification.retention_days', 30),
        ];

        return view('notification::theme.backups.index', compact('config'));
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cleanup_enabled' => 'required|boolean',
            'cleanup_days' => 'required|integer|min:1|max:365',
            'push_enabled' => 'required|boolean',
            'push_max_retries' => 'required|integer|min:1|max:10',
            'channel_database' => 'required|boolean',
            'channel_mail' => 'required|boolean',
            'channel_push' => 'required|boolean',
            'retention_days' => 'required|integer|min:1|max:365',
        ], [
            'cleanup_days.min' => 'Los días de limpieza deben ser al menos 1',
            'cleanup_days.max' => 'Los días de limpieza no pueden exceder 365',
            'push_max_retries.min' => 'Los reintentos deben ser al menos 1',
            'push_max_retries.max' => 'Los reintentos no pueden exceder 10',
            'retention_days.min' => 'Los días de retención deben ser al menos 1',
            'retention_days.max' => 'Los días de retención no pueden exceder 365',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        // Update environment file
        $this->updateEnvFile([
            'NOTIFICATION_CLEANUP_ENABLED' => $request->cleanup_enabled ? 'true' : 'false',
            'NOTIFICATION_CLEANUP_DAYS' => $request->cleanup_days,
            'PUSH_NOTIFICATIONS_ENABLED' => $request->push_enabled ? 'true' : 'false',
            'PUSH_NOTIFICATION_MAX_RETRIES' => $request->push_max_retries,
            'NOTIFICATION_CHANNEL_DATABASE' => $request->channel_database ? 'true' : 'false',
            'NOTIFICATION_CHANNEL_MAIL' => $request->channel_mail ? 'true' : 'false',
            'NOTIFICATION_CHANNEL_PUSH' => $request->channel_push ? 'true' : 'false',
            'NOTIFICATION_RETENTION_DAYS' => $request->retention_days,
        ]);

        return $this->success('Configuración actualizada correctamente');
    }
}

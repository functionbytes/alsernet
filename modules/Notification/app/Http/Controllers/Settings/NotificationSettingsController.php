<?php

namespace Modules\Notification\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\Notification\Http\Requests\UpdateSettingsRequest;

class NotificationSettingsController extends Controller
{
    public function index(): View
    {
        Gate::authorize('notification.settings.view');

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

        return view('notification::managers.settings.index', compact('config'));
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $this->persistSettings($request);

        Artisan::call('config:clear');

        return $this->success('Configuración actualizada correctamente');
    }

    /**
     * Persist notification settings using the Core Setting model.
     */
    private function persistSettings(UpdateSettingsRequest $request): void
    {
        $map = [
            'notification.cleanup.enabled' => $request->boolean('cleanup_enabled'),
            'notification.cleanup.days' => (int) $request->input('cleanup_days'),
            'notification.push_notifications.enabled' => $request->boolean('push_enabled'),
            'notification.push_notifications.max_retries' => (int) $request->input('push_max_retries'),
            'notification.channels.database' => $request->boolean('channel_database'),
            'notification.channels.mail' => $request->boolean('channel_mail'),
            'notification.channels.push' => $request->boolean('channel_push'),
            'notification.retention_days' => (int) $request->input('retention_days'),
        ];

        foreach ($map as $key => $value) {
            Setting::set($key, $value);
        }
    }
}

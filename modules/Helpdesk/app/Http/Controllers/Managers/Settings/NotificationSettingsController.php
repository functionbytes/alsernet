<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Setting;

class NotificationSettingsController extends Controller
{
    private const GROUP = 'notifications';

    private const DEFAULTS = [
        'notify_new_conversation' => true,
        'notify_conversation_assigned' => true,
        'notify_conversation_resolved' => true,
        'notify_new_message' => true,
        'notify_overdue_sla' => true,
        'email_notifications_enabled' => true,
        'browser_notifications_enabled' => false,
        'notification_sound_enabled' => true,
        'daily_digest_enabled' => false,
        'daily_digest_time' => '08:00',
    ];

    private const BOOL_KEYS = [
        'notify_new_conversation',
        'notify_conversation_assigned',
        'notify_conversation_resolved',
        'notify_new_message',
        'notify_overdue_sla',
        'email_notifications_enabled',
        'browser_notifications_enabled',
        'notification_sound_enabled',
        'daily_digest_enabled',
    ];

    public function __construct()
    {
        $this->middleware('can:helpdesk.settings.view')->only('index');
        $this->middleware('can:helpdesk.settings.update')->only('update');
    }

    public function index(): View
    {
        $settings = array_merge(self::DEFAULTS, Setting::allAsFlatArray(self::GROUP));

        return view('helpdesk::settings.notifications.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notify_new_conversation' => ['nullable', 'boolean'],
            'notify_conversation_assigned' => ['nullable', 'boolean'],
            'notify_conversation_resolved' => ['nullable', 'boolean'],
            'notify_new_message' => ['nullable', 'boolean'],
            'notify_overdue_sla' => ['nullable', 'boolean'],
            'email_notifications_enabled' => ['nullable', 'boolean'],
            'browser_notifications_enabled' => ['nullable', 'boolean'],
            'notification_sound_enabled' => ['nullable', 'boolean'],
            'daily_digest_enabled' => ['nullable', 'boolean'],
            'daily_digest_time' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
        ]);

        foreach (self::BOOL_KEYS as $key) {
            $validated[$key] = $request->has($key);
        }

        foreach ($validated as $key => $value) {
            Setting::set(self::GROUP.'.'.$key, $value, self::GROUP);
        }

        return back()->with('success', 'Configuración de notificaciones actualizada correctamente.');
    }
}

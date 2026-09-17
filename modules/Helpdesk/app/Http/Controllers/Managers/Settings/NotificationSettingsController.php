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
            'notify_new_conversation' => ['sometimes', 'boolean'],
            'notify_conversation_assigned' => ['sometimes', 'boolean'],
            'notify_conversation_resolved' => ['sometimes', 'boolean'],
            'notify_new_message' => ['sometimes', 'boolean'],
            'notify_overdue_sla' => ['sometimes', 'boolean'],
            'email_notifications_enabled' => ['sometimes', 'boolean'],
            'browser_notifications_enabled' => ['sometimes', 'boolean'],
            'notification_sound_enabled' => ['sometimes', 'boolean'],
            'daily_digest_enabled' => ['sometimes', 'boolean'],
            'daily_digest_time' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
        ]);

        // boolean(), no has(): la vista usa <select> Activado/Desactivado
        // (convención del proyecto, no checkboxes) — el campo SIEMPRE viene
        // presente en el POST, así que has() daría true sin importar la
        // opción elegida. boolean() interpreta correctamente "1"/"0".
        foreach (self::BOOL_KEYS as $key) {
            $validated[$key] = $request->boolean($key);
        }

        Setting::setMany($validated, self::GROUP, 'settings.notifications.updated');

        return back()->with('success', 'Configuración de notificaciones actualizada correctamente.');
    }
}

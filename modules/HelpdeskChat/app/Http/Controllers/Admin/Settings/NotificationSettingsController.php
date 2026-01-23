<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskChat\Http\Controllers\Controller;

class NotificationSettingsController extends Controller
{
    /**
     * Show notification settings form.
     */
    public function index(Request $request): View
    {
        $settings = $request->user()->getNotificationSettings();

        return view('helpdeskchat::admin.settings.notifications', compact('settings'));
    }

    /**
     * Update notification settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email.conversation_assigned' => 'boolean',
            'email.new_message' => 'boolean',
            'email.conversation_mention' => 'boolean',
            'browser.conversation_assigned' => 'boolean',
            'browser.new_message' => 'boolean',
            'browser.conversation_mention' => 'boolean',
            'sound' => 'boolean',
        ]);

        // Convert missing checkboxes to false
        $settings = [
            'email' => [
                'conversation_assigned' => $request->boolean('email.conversation_assigned'),
                'new_message' => $request->boolean('email.new_message'),
                'conversation_mention' => $request->boolean('email.conversation_mention'),
            ],
            'browser' => [
                'conversation_assigned' => $request->boolean('browser.conversation_assigned'),
                'new_message' => $request->boolean('browser.new_message'),
                'conversation_mention' => $request->boolean('browser.conversation_mention'),
            ],
            'sound' => $request->boolean('sound'),
        ];

        $request->user()->update([
            'notification_settings' => $settings,
        ]);

        return back()->with('success', 'Notification settings updated successfully');
    }
}

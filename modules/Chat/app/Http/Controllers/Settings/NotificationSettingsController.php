<?php

namespace Modules\Chat\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Services\Settings\HelpdeskSettingsRepository;
use Modules\Chat\Services\Settings\NotificationSettingsService;

class NotificationSettingsController extends Controller
{
    public function __construct(
        private readonly HelpdeskSettingsRepository $settings,
        private readonly NotificationSettingsService $notificationService,
    ) {}

    public function index(): View
    {
        $settings = $this->settings->get('chat.notifications', [
            'email' => [
                'conversation_assigned' => true,
                'new_message' => true,
                'conversation_mention' => true,
            ],
            'browser' => [
                'conversation_assigned' => true,
                'new_message' => true,
                'conversation_mention' => true,
            ],
            'sound' => true,
        ]);

        return view('Chat::settings.notifications', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'nullable|array',
            'email.conversation_assigned' => 'nullable',
            'email.new_message' => 'nullable',
            'email.conversation_mention' => 'nullable',
            'browser' => 'nullable|array',
            'browser.conversation_assigned' => 'nullable',
            'browser.new_message' => 'nullable',
            'browser.conversation_mention' => 'nullable',
            'sound' => 'nullable',
        ]);

        $this->settings->save('chat.notifications', $this->notificationService->normalize($validated));

        return back()->with('success', 'Configuración de notificaciones actualizada correctamente');
    }
}

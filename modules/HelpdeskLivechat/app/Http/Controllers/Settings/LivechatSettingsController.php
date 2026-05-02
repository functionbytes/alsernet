<?php

namespace Modules\HelpdeskLivechat\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskLivechat\Http\Requests\UpdateLivechatSettingsRequest;

class LivechatSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.livechat.settings.view')->only('index');
        $this->middleware('can:helpdesk.livechat.settings.update')->only('update');
    }

    public function index(): View
    {
        $dbSettings = Setting::allAsArray('livechat');
        $settings = $this->getSettings('helpdesk.livechat', [
            // Widget - Home Screen
            'show_avatars' => true,
            'show_help_center' => true,
            'hide_suggested_articles' => false,
            'show_tickets_section' => true,
            'enable_send_message' => true,
            'enable_create_ticket' => true,
            'enable_search_help' => true,

            // Widget - Chat Screen
            'welcome_message' => 'Hola! ¿Cómo podemos ayudarte?',
            'input_placeholder' => 'Escribe tu mensaje...',
            'offline_message' => 'Nuestros agentes no están disponibles en este momento, pero puedes enviar mensajes. Te notificaremos aquí y en tu correo cuando obtengas una respuesta.',
            'queue_message' => 'Uno de nuestros agentes estará contigo en breve. Eres el número :number en la cola.',

            // Widget - Launcher
            'position' => 'bottom-right',
            'side_spacing' => 16,
            'bottom_spacing' => 16,
            'hide_launcher' => false,

            // Widget - Style
            'primary_color' => '#b10100',
            'secondary_color' => '#ffffff',
            'header_title' => 'Chat de Soporte',
            'show_dark_mode_preview' => true,

            // Widget - Additional Options
            'show_timestamps' => true,
            'typing_indicator' => true,
            'sound_notifications' => true,
            'enable_email_transcripts' => true,

            // Timeouts
            'enable_auto_transfer' => false,
            'auto_transfer_minutes' => 5,
            'enable_auto_inactive' => false,
            'auto_inactive_minutes' => 10,
            'enable_auto_close' => false,
            'auto_close_minutes' => 15,

            // Security
            'trusted_domains' => '',
            'enforce_identity_verification' => false,
            'secret_key' => Setting::get('livechat.secret_key') ?? tap(Str::random(40), fn ($key) => Setting::set('livechat.secret_key', $key, 'livechat')),

            // Forms
            'pre_chat_form_enabled' => false,
            'pre_chat_info' => '',
            'post_chat_form_enabled' => false,
            'post_chat_info' => '',

            // Chat page
            'chat_page_title' => '',
            'chat_page_subtitle' => '',
        ]);

        $positions = [
            'bottom-right' => 'Abajo Derecha',
            'bottom-left' => 'Abajo Izquierda',
            'top-right' => 'Arriba Derecha',
            'top-left' => 'Arriba Izquierda',
        ];

        $settings = array_merge($settings, $dbSettings);

        return view('helpdesklivechat::settings.livechat.index', [
            'backups' => $settings,
            'positions' => $positions,
        ]);
    }

    public function update(UpdateLivechatSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (isset($validated['no_agents_message'])) {
            $validated['offline_message'] = $validated['no_agents_message'];
            unset($validated['no_agents_message']);
        }

        $this->saveSettings('helpdesk.livechat', $validated);

        foreach ($validated as $key => $value) {
            Setting::set('livechat.'.$key, $value, 'livechat');
        }

        return back()->with('success', 'Configuración de LiveChat actualizada correctamente');
    }

    protected function getSettings(string $key, array $defaults = []): array
    {
        $stored = \Cache::get($key, []);

        return array_merge($defaults, $stored);
    }

    protected function saveSettings(string $key, array $values): void
    {
        \Cache::put($key, $values, now()->addDays(365));
    }
}

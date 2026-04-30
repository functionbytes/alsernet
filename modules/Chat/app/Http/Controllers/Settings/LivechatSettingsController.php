<?php

namespace Modules\Chat\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Services\Settings\HelpdeskSettingsRepository;

class LivechatSettingsController extends Controller
{
    public function __construct(
        private readonly HelpdeskSettingsRepository $settings,
    ) {}

    public function index(): View
    {
        $settings = $this->settings->get('chat.livechat', [
            'show_avatars' => true,
            'show_help_center' => true,
            'hide_suggested_articles' => false,
            'show_tickets_section' => true,
            'enable_send_message' => true,
            'enable_create_ticket' => true,
            'enable_search_help' => true,
            'welcome_message' => 'Hola! ¿Cómo podemos ayudarte?',
            'input_placeholder' => 'Escribe tu mensaje...',
            'offline_message' => 'Nuestros agentes no están disponibles en este momento, pero puedes enviar mensajes. Te notificaremos aquí y en tu correo cuando obtengas una respuesta.',
            'queue_message' => 'Uno de nuestros agentes estará contigo en breve. Eres el número :number en la cola.',
            'position' => 'bottom-right',
            'side_spacing' => 16,
            'bottom_spacing' => 16,
            'hide_launcher' => false,
            'primary_color' => '#081A28',
            'secondary_color' => '#ffffff',
            'header_title' => 'Chat de Soporte',
            'show_dark_mode_preview' => true,
            'show_timestamps' => true,
            'typing_indicator' => true,
            'sound_notifications' => true,
            'enable_email_transcripts' => true,
            'enable_auto_transfer' => false,
            'auto_transfer_minutes' => 5,
            'enable_auto_inactive' => false,
            'auto_inactive_minutes' => 10,
            'enable_auto_close' => false,
            'auto_close_minutes' => 15,
            'trusted_domains' => '',
            'enforce_identity_verification' => false,
            'secret_key' => \Str::random(40),
        ]);

        $positions = [
            'bottom-right' => 'Abajo Derecha',
            'bottom-left' => 'Abajo Izquierda',
            'top-right' => 'Arriba Derecha',
            'top-left' => 'Arriba Izquierda',
        ];

        return view('Chat::settings.livechat', compact('settings', 'positions'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'show_avatars' => 'boolean',
            'show_help_center' => 'boolean',
            'hide_suggested_articles' => 'boolean',
            'show_tickets_section' => 'boolean',
            'enable_send_message' => 'boolean',
            'enable_create_ticket' => 'boolean',
            'enable_search_help' => 'boolean',
            'welcome_message' => 'required|string|max:200',
            'input_placeholder' => 'nullable|string|max:100',
            'no_agents_message' => 'nullable|string|max:500',
            'queue_message' => 'nullable|string|max:500',
            'position' => 'required|in:bottom-right,bottom-left,top-right,top-left',
            'side_spacing' => 'nullable|integer|min:0|max:100',
            'bottom_spacing' => 'nullable|integer|min:0|max:100',
            'hide_launcher' => 'boolean',
            'primary_color' => 'required|regex:/^#[0-9a-f]{6}$/i',
            'secondary_color' => 'required|regex:/^#[0-9a-f]{6}$/i',
            'header_title' => 'required|string|max:100',
            'show_dark_mode_preview' => 'boolean',
            'show_timestamps' => 'boolean',
            'typing_indicator' => 'boolean',
            'sound_notifications' => 'boolean',
            'enable_email_transcripts' => 'boolean',
            'enable_auto_transfer' => 'boolean',
            'auto_transfer_minutes' => 'nullable|integer|min:1|max:60',
            'enable_auto_inactive' => 'boolean',
            'auto_inactive_minutes' => 'nullable|integer|min:1|max:120',
            'enable_auto_close' => 'boolean',
            'auto_close_minutes' => 'nullable|integer|min:1|max:240',
            'trusted_domains' => 'nullable|string',
            'enforce_identity_verification' => 'boolean',
        ]);

        if (isset($validated['no_agents_message'])) {
            $validated['offline_message'] = $validated['no_agents_message'];
            unset($validated['no_agents_message']);
        }

        $this->settings->save('chat.livechat', $validated);

        return back()->with('success', 'Configuración de LiveChat actualizada correctamente');
    }
}

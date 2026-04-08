<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Setting;

class SettingsController extends Controller
{
    /**
     * Tickets Settings
     */
    public function ticketsIndex()
    {
        $dbSettings = Setting::allAsArray('tickets');
        $settings = $this->getSettings('helpdesk.tickets', [
            // ID y Caracteres
            'customer_ticketid' => 'SPT',
            'ticket_character' => 100,

            // Restricciones de creación
            'restrict_to_create_ticket' => false,
            'maximum_allow_tickets' => 5,
            'maximum_allow_hours' => 24,

            // Restricciones de respuesta
            'restrict_to_reply_ticket' => false,
            'maximum_allow_replies' => 10,
            'reply_allow_in_hours' => 1,

            // Tiempo de respuesta automática
            'auto_responsetime_ticket' => false,
            'auto_responsetime_ticket_time' => 48,

            // Cierre automático
            'auto_close_ticket' => true,
            'auto_close_ticket_time' => 30,

            // Reapertura
            'user_reopen_issue' => true,
            'user_reopen_time' => 7,

            // Infracciones
            'auto_overdue_ticket' => false,
            'auto_overdue_ticket_time' => 5,

            // Edición de respuestas
            'restrict_reply_edit' => false,
            'reply_edit_with_in_time' => 15,

            // Tickets vencidos
            'auto_overdue_customer' => false,

            // Eliminación automática
            'trashed_ticket_autodelete' => true,
            'trashed_ticket_delete_time' => 30,

            // Notificaciones
            'auto_notification_delete_enable' => true,
            'auto_notification_delete_days' => 15,

            // Privacidad
            'customer_panel_employee_protect' => false,
            'employee_protect_name' => 'Equipo de Soporte',

            // Opciones generales
            'guest_ticket' => true,
            'note_create_mails' => false,
            'restict_to_delete_ticket' => false,
            'user_file_upload_enable' => true,
            'guest_file_upload_enable' => true,
            'guest_ticket_otp' => false,
            'customer_ticket' => false,
            'ticket_rating' => false,
            'cc_email' => false,
        ]);

        // DB settings override cache/config defaults
        $settings = array_merge($settings, $dbSettings);

        return view('helpdesk::managers.helpdesk.settings.tickets', [
            'backups' => $settings,
        ]);
    }

    public function ticketsUpdate(Request $request)
    {
        $validated = $request->validate([
            // ID y Caracteres
            'customer_ticketid' => 'required|string|min:1|max:4',
            'ticket_character' => 'required|integer|min:10|max:500',

            // Restricciones de creación
            'restrict_to_create_ticket' => 'nullable|boolean',
            'maximum_allow_tickets' => 'nullable|integer|min:1|max:100',
            'maximum_allow_hours' => 'nullable|integer|min:1|max:168',

            // Restricciones de respuesta
            'restrict_to_reply_ticket' => 'nullable|boolean',
            'maximum_allow_replies' => 'nullable|integer|min:1|max:100',
            'reply_allow_in_hours' => 'nullable|integer|min:1|max:24',

            // Tiempo de respuesta automática
            'auto_responsetime_ticket' => 'nullable|boolean',
            'auto_responsetime_ticket_time' => 'nullable|integer|min:1|max:365',

            // Cierre automático
            'auto_close_ticket' => 'nullable|boolean',
            'auto_close_ticket_time' => 'nullable|integer|min:1|max:365',

            // Reapertura
            'user_reopen_issue' => 'nullable|boolean',
            'user_reopen_time' => 'nullable|integer|min:0|max:365',

            // Infracciones
            'auto_overdue_ticket' => 'nullable|boolean',
            'auto_overdue_ticket_time' => 'nullable|integer|min:1|max:100',

            // Edición de respuestas
            'restrict_reply_edit' => 'nullable|boolean',
            'reply_edit_with_in_time' => 'nullable|integer|min:1|max:1440',

            // Tickets vencidos
            'auto_overdue_customer' => 'nullable|boolean',

            // Eliminación automática
            'trashed_ticket_autodelete' => 'nullable|boolean',
            'trashed_ticket_delete_time' => 'nullable|integer|min:1|max:365',

            // Notificaciones
            'auto_notification_delete_enable' => 'nullable|boolean',
            'auto_notification_delete_days' => 'nullable|integer|min:1|max:365',

            // Privacidad
            'customer_panel_employee_protect' => 'nullable|boolean',
            'employee_protect_name' => 'nullable|string|min:3|max:50',

            // Opciones generales
            'guest_ticket' => 'nullable|boolean',
            'note_create_mails' => 'nullable|boolean',
            'restict_to_delete_ticket' => 'nullable|boolean',
            'user_file_upload_enable' => 'nullable|boolean',
            'guest_file_upload_enable' => 'nullable|boolean',
            'guest_ticket_otp' => 'nullable|boolean',
            'customer_ticket' => 'nullable|boolean',
            'ticket_rating' => 'nullable|boolean',
            'cc_email' => 'nullable|boolean',
        ]);

        // Convert null values to false for checkboxes
        foreach ($validated as $key => $value) {
            if (is_null($value) && strpos($key, '_') !== false && ! in_array($key, ['customer_ticketid', 'employee_protect_name'])) {
                $validated[$key] = false;
            }
        }

        $this->saveSettings('helpdesk.tickets', $validated);

        foreach ($validated as $key => $value) {
            Setting::set('tickets.'.$key, $value, 'tickets');
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuración de tickets actualizada correctamente',
        ]);
    }

    /**
     * LiveChat Settings
     */
    public function livechatIndex()
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
            'primary_color' => '#90bb13',
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
            'secret_key' => \Str::random(40),
        ]);

        $positions = [
            'bottom-right' => 'Abajo Derecha',
            'bottom-left' => 'Abajo Izquierda',
            'top-right' => 'Arriba Derecha',
            'top-left' => 'Arriba Izquierda',
        ];

        $settings = array_merge($settings, $dbSettings);

        return view('helpdesk::managers.helpdesk.settings.livechat', [
            'backups' => $settings,
            'positions' => $positions,
        ]);
    }

    public function livechatUpdate(Request $request)
    {
        $validated = $request->validate([
            // Widget - Home Screen
            'show_avatars' => 'boolean',
            'show_help_center' => 'boolean',
            'hide_suggested_articles' => 'boolean',
            'show_tickets_section' => 'boolean',
            'enable_send_message' => 'boolean',
            'enable_create_ticket' => 'boolean',
            'enable_search_help' => 'boolean',

            // Widget - Chat Screen
            'welcome_message' => 'required|string|max:200',
            'input_placeholder' => 'nullable|string|max:100',
            'no_agents_message' => 'nullable|string|max:500',
            'queue_message' => 'nullable|string|max:500',

            // Widget - Launcher
            'position' => 'required|in:bottom-right,bottom-left,top-right,top-left',
            'side_spacing' => 'nullable|integer|min:0|max:100',
            'bottom_spacing' => 'nullable|integer|min:0|max:100',
            'hide_launcher' => 'boolean',

            // Widget - Style
            'primary_color' => 'required|regex:/^#[0-9a-f]{6}$/i',
            'secondary_color' => 'required|regex:/^#[0-9a-f]{6}$/i',
            'header_title' => 'required|string|max:100',
            'show_dark_mode_preview' => 'boolean',

            // Widget - Additional Options
            'show_timestamps' => 'boolean',
            'typing_indicator' => 'boolean',
            'sound_notifications' => 'boolean',
            'enable_email_transcripts' => 'boolean',

            // Timeouts
            'enable_auto_transfer' => 'boolean',
            'auto_transfer_minutes' => 'nullable|integer|min:1|max:60',
            'enable_auto_inactive' => 'boolean',
            'auto_inactive_minutes' => 'nullable|integer|min:1|max:120',
            'enable_auto_close' => 'boolean',
            'auto_close_minutes' => 'nullable|integer|min:1|max:240',

            // Security
            'trusted_domains' => 'nullable|string',
            'enforce_identity_verification' => 'boolean',
        ]);

        // Map field names if needed
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

    /**
     * AI Settings
     */
    public function aiIndex()
    {
        $dbSettings = Setting::allAsArray('ai');
        $settings = $this->getSettings('helpdesk.ai', [
            'llm_provider' => 'openai',
            'openai_api_key' => '',
            'openai_model' => 'gpt-4o',
            'anthropic_api_key' => '',
            'anthropic_model' => 'claude-opus-4-5-20251101',
            'gemini_api_key' => '',
            'gemini_model' => 'gemini-2.0-flash',
            'embeddings_provider' => 'openai',
            'enable_embeddings' => true,
            'enable_rag' => false,
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'top_p' => 1.0,
        ]);

        $providers = [
            'openai' => 'OpenAI (GPT-4o)',
            'anthropic' => 'Anthropic (Claude)',
            'gemini' => 'Google Gemini',
        ];

        $settings = array_merge($settings, $dbSettings);

        return view('helpdesk::managers.helpdesk.settings.ai', [
            'backups' => $settings,
            'providers' => $providers,
        ]);
    }

    public function aiUpdate(Request $request)
    {
        $validated = $request->validate([
            'llm_provider' => 'required|in:openai,anthropic,gemini',
            'openai_api_key' => 'nullable|string',
            'openai_model' => 'nullable|string',
            'anthropic_api_key' => 'nullable|string',
            'anthropic_model' => 'nullable|string',
            'gemini_api_key' => 'nullable|string',
            'gemini_model' => 'nullable|string',
            'embeddings_provider' => 'required|in:openai,gemini',
            'enable_embeddings' => 'boolean',
            'enable_rag' => 'boolean',
            'temperature' => 'required|numeric|min:0|max:2',
            'max_tokens' => 'required|integer|min:100|max:128000',
            'top_p' => 'required|numeric|min:0|max:1',
        ]);

        $this->saveSettings('helpdesk.ai', $validated);

        foreach ($validated as $key => $value) {
            Setting::set('ai.'.$key, $value, 'ai');
        }

        return back()->with('success', 'Configuración de IA actualizada correctamente');
    }

    /**
     * Uploading Settings
     */
    public function uploadingIndex()
    {
        $dbSettings = Setting::allAsArray('uploading');
        $settings = $this->getSettings('helpdesk.uploading', [
            'max_file_size_mb' => 25,
            'allowed_extensions' => 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,zip',
            'enable_image_compression' => true,
            'image_max_width' => 1920,
            'image_max_height' => 1080,
            'image_quality' => 85,
            'enable_virus_scan' => false,
            'enable_quarantine' => true,
        ]);

        $settings = array_merge($settings, $dbSettings);

        return view('helpdesk::managers.helpdesk.settings.uploading', [
            'backups' => $settings,
        ]);
    }

    public function uploadingUpdate(Request $request)
    {
        $validated = $request->validate([
            'max_file_size_mb' => 'required|integer|min:1|max:1000',
            'allowed_extensions' => 'required|string',
            'enable_image_compression' => 'boolean',
            'image_max_width' => 'required|integer|min:100|max:4000',
            'image_max_height' => 'required|integer|min:100|max:4000',
            'image_quality' => 'required|integer|min:10|max:100',
            'enable_virus_scan' => 'boolean',
            'enable_quarantine' => 'boolean',
        ]);

        $this->saveSettings('helpdesk.uploading', $validated);

        foreach ($validated as $key => $value) {
            Setting::set('uploading.'.$key, $value, 'uploading');
        }

        return back()->with('success', 'Configuración de subida de archivos actualizada correctamente');
    }

    /**
     * Helper Methods
     */
    protected function getSettings($key, $defaults = [])
    {
        $stored = \Cache::get($key, []);

        return array_merge($defaults, $stored);
    }

    protected function saveSettings($key, $values)
    {
        \Cache::put($key, $values, now()->addDays(365));

        // Optional: Also save to database for persistence
        // backups()->set($key, $values);
    }
}

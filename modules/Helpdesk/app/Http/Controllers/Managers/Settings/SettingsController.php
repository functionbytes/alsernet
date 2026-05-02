<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Modules\Helpdesk\Http\Requests\UpdateAiSettingsRequest;
use Modules\Helpdesk\Http\Requests\UpdateTicketsSettingsRequest;
use Modules\Helpdesk\Http\Requests\UpdateUploadingSettingsRequest;
use Modules\Helpdesk\Models\Setting;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.settings.view')->only(['ticketsIndex', 'aiIndex', 'uploadingIndex']);
        $this->middleware('can:helpdesk.settings.update')->only(['ticketsUpdate', 'aiUpdate', 'uploadingUpdate']);
    }

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

        return view('helpdesk::settings.tickets.index', [
            'backups' => $settings,
        ]);
    }

    public function ticketsUpdate(UpdateTicketsSettingsRequest $request)
    {
        $validated = $request->validated();

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

        return view('helpdesk::settings.ai.index', [
            'backups' => $settings,
            'providers' => $providers,
        ]);
    }

    public function aiUpdate(UpdateAiSettingsRequest $request)
    {
        $validated = $request->validated();

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

        return view('helpdesk::settings.uploading.index', [
            'backups' => $settings,
        ]);
    }

    public function uploadingUpdate(UpdateUploadingSettingsRequest $request)
    {
        $validated = $request->validated();

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

<?php

namespace Modules\Chat\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Services\Settings\HelpdeskSettingsRepository;

class TicketsSettingsController extends Controller
{
    public function __construct(
        private readonly HelpdeskSettingsRepository $settings,
    ) {}

    public function index(): View
    {
        $settings = $this->settings->get('chat.tickets', [
            'customer_ticketid' => 'SPT',
            'ticket_character' => 100,
            'restrict_to_create_ticket' => false,
            'maximum_allow_tickets' => 5,
            'maximum_allow_hours' => 24,
            'restrict_to_reply_ticket' => false,
            'maximum_allow_replies' => 10,
            'reply_allow_in_hours' => 1,
            'auto_responsetime_ticket' => false,
            'auto_responsetime_ticket_time' => 48,
            'auto_close_ticket' => true,
            'auto_close_ticket_time' => 30,
            'user_reopen_issue' => true,
            'user_reopen_time' => 7,
            'auto_overdue_ticket' => false,
            'auto_overdue_ticket_time' => 5,
            'restrict_reply_edit' => false,
            'reply_edit_with_in_time' => 15,
            'auto_overdue_customer' => false,
            'trashed_ticket_autodelete' => true,
            'trashed_ticket_delete_time' => 30,
            'auto_notification_delete_enable' => true,
            'auto_notification_delete_days' => 15,
            'customer_panel_employee_protect' => false,
            'employee_protect_name' => 'Equipo de Soporte',
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

        return view('Chat::settings.tickets', compact('settings'));
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_ticketid' => 'required|string|min:1|max:4',
            'ticket_character' => 'required|integer|min:10|max:500',
            'restrict_to_create_ticket' => 'nullable|boolean',
            'maximum_allow_tickets' => 'nullable|integer|min:1|max:100',
            'maximum_allow_hours' => 'nullable|integer|min:1|max:168',
            'restrict_to_reply_ticket' => 'nullable|boolean',
            'maximum_allow_replies' => 'nullable|integer|min:1|max:100',
            'reply_allow_in_hours' => 'nullable|integer|min:1|max:24',
            'auto_responsetime_ticket' => 'nullable|boolean',
            'auto_responsetime_ticket_time' => 'nullable|integer|min:1|max:365',
            'auto_close_ticket' => 'nullable|boolean',
            'auto_close_ticket_time' => 'nullable|integer|min:1|max:365',
            'user_reopen_issue' => 'nullable|boolean',
            'user_reopen_time' => 'nullable|integer|min:0|max:365',
            'auto_overdue_ticket' => 'nullable|boolean',
            'auto_overdue_ticket_time' => 'nullable|integer|min:1|max:100',
            'restrict_reply_edit' => 'nullable|boolean',
            'reply_edit_with_in_time' => 'nullable|integer|min:1|max:1440',
            'auto_overdue_customer' => 'nullable|boolean',
            'trashed_ticket_autodelete' => 'nullable|boolean',
            'trashed_ticket_delete_time' => 'nullable|integer|min:1|max:365',
            'auto_notification_delete_enable' => 'nullable|boolean',
            'auto_notification_delete_days' => 'nullable|integer|min:1|max:365',
            'customer_panel_employee_protect' => 'nullable|boolean',
            'employee_protect_name' => 'nullable|string|min:3|max:50',
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

        $skipFalseCoercion = ['customer_ticketid', 'employee_protect_name'];

        foreach ($validated as $key => $value) {
            if ($value === null && str_contains($key, '_') && ! in_array($key, $skipFalseCoercion, strict: true)) {
                $validated[$key] = false;
            }
        }

        $this->settings->save('chat.tickets', $validated);

        return response()->json([
            'success' => true,
            'message' => 'Configuración de tickets actualizada correctamente',
        ]);
    }
}

<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTickets\Http\Requests\UpdateTicketGeneralSettingsRequest;

class TicketGeneralSettingsController extends Controller
{
    private const GROUP = 'tickets';

    private const DEFAULTS = [
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
    ];

    private const BOOL_KEYS = [
        'restrict_to_create_ticket',
        'restrict_to_reply_ticket',
        'auto_responsetime_ticket',
        'auto_close_ticket',
        'user_reopen_issue',
        'auto_overdue_ticket',
        'restrict_reply_edit',
        'auto_overdue_customer',
        'trashed_ticket_autodelete',
        'auto_notification_delete_enable',
        'customer_panel_employee_protect',
        'guest_ticket',
        'note_create_mails',
        'restict_to_delete_ticket',
        'user_file_upload_enable',
        'guest_file_upload_enable',
        'guest_ticket_otp',
        'customer_ticket',
        'ticket_rating',
        'cc_email',
    ];

    public function __construct()
    {
        $this->middleware('can:helpdesk.settings.view')->only('index');
        $this->middleware('can:helpdesk.settings.update')->only('update');
    }

    public function index(): View
    {
        $settings = array_merge(self::DEFAULTS, Setting::allAsFlatArray(self::GROUP));

        return view('helpdesktickets::managers.settings.general.index', compact('settings'));
    }

    public function update(UpdateTicketGeneralSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        foreach (self::BOOL_KEYS as $key) {
            $validated[$key] = $request->has($key);
        }

        foreach ($validated as $key => $value) {
            Setting::set(self::GROUP.'.'.$key, $value, self::GROUP);
        }

        return back()->with('success', 'Configuración de tickets actualizada correctamente.');
    }
}

<?php

namespace Modules\HelpdeskTickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_ticketid' => ['required', 'string', 'min:1', 'max:4'],
            'ticket_character' => ['required', 'integer', 'min:10', 'max:500'],

            'restrict_to_create_ticket' => ['nullable', 'boolean'],
            'maximum_allow_tickets' => ['nullable', 'integer', 'min:1', 'max:100'],
            'maximum_allow_hours' => ['nullable', 'integer', 'min:1', 'max:168'],

            'restrict_to_reply_ticket' => ['nullable', 'boolean'],
            'maximum_allow_replies' => ['nullable', 'integer', 'min:1', 'max:100'],
            'reply_allow_in_hours' => ['nullable', 'integer', 'min:1', 'max:24'],

            'auto_responsetime_ticket' => ['nullable', 'boolean'],
            'auto_responsetime_ticket_time' => ['nullable', 'integer', 'min:1', 'max:365'],

            'auto_close_ticket' => ['nullable', 'boolean'],
            'auto_close_ticket_time' => ['nullable', 'integer', 'min:1', 'max:365'],

            'user_reopen_issue' => ['nullable', 'boolean'],
            'user_reopen_time' => ['nullable', 'integer', 'min:0', 'max:365'],

            'auto_overdue_ticket' => ['nullable', 'boolean'],
            'auto_overdue_ticket_time' => ['nullable', 'integer', 'min:1', 'max:100'],

            'restrict_reply_edit' => ['nullable', 'boolean'],
            'reply_edit_with_in_time' => ['nullable', 'integer', 'min:1', 'max:1440'],

            'auto_overdue_customer' => ['nullable', 'boolean'],

            'trashed_ticket_autodelete' => ['nullable', 'boolean'],
            'trashed_ticket_delete_time' => ['nullable', 'integer', 'min:1', 'max:365'],

            'auto_notification_delete_enable' => ['nullable', 'boolean'],
            'auto_notification_delete_days' => ['nullable', 'integer', 'min:1', 'max:365'],

            'customer_panel_employee_protect' => ['nullable', 'boolean'],
            'employee_protect_name' => ['nullable', 'string', 'min:3', 'max:50'],

            'guest_ticket' => ['nullable', 'boolean'],
            'note_create_mails' => ['nullable', 'boolean'],
            'restict_to_delete_ticket' => ['nullable', 'boolean'],
            'user_file_upload_enable' => ['nullable', 'boolean'],
            'guest_file_upload_enable' => ['nullable', 'boolean'],
            'guest_ticket_otp' => ['nullable', 'boolean'],
            'customer_ticket' => ['nullable', 'boolean'],
            'ticket_rating' => ['nullable', 'boolean'],
            'cc_email' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_ticketid.required' => 'El prefijo de ticket es obligatorio.',
            'customer_ticketid.max' => 'El prefijo no puede superar 4 caracteres.',
            'ticket_character.required' => 'El máximo de caracteres es obligatorio.',
            'ticket_character.min' => 'El mínimo de caracteres permitidos es 10.',
            'ticket_character.max' => 'El máximo de caracteres permitidos es 500.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_ticketid' => 'prefijo de ticket',
            'ticket_character' => 'máximo de caracteres',
            'maximum_allow_tickets' => 'máximo de tickets permitidos',
            'maximum_allow_hours' => 'horas máximas permitidas',
            'maximum_allow_replies' => 'máximo de respuestas',
            'reply_allow_in_hours' => 'horas para responder',
            'employee_protect_name' => 'nombre protegido del equipo',
        ];
    }
}

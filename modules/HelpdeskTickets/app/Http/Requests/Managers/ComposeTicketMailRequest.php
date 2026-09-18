<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Rules\ValidMimeMagicBytes;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Services\HelpdeskSettings;

class ComposeTicketMailRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real (viewAny + policy sobre el ticket concreto) se
        // comprueba en el controller, igual que BulkReplyTicketRequest.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $settings = app(HelpdeskSettings::class);

        return [
            'ticket_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_tickets,id'],
            'to' => ['required', 'email', 'max:255'],
            // Remitente elegible. Se valida contra la lista cerrada de
            // direcciones que el sistema tiene configuradas de verdad (ver
            // TicketMailsController::availableSenders()): un campo libre
            // dejaría a cualquier agente falsificar el From de un correo que
            // sale con el SPF/DKIM del dominio corporativo.
            'from' => ['nullable', 'email', 'max:255'],
            'cc' => [
                'nullable',
                'array',
                Rule::prohibitedIf(fn () => ! filter_var(
                    Setting::get('tickets.cc_email', false),
                    FILTER_VALIDATE_BOOLEAN,
                )),
            ],
            'cc.*' => ['email'],
            'bcc' => ['nullable', 'array'],
            'bcc.*' => ['email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:helpdesk.helpdesk_ticket_categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'cancel_if_customer_replies' => ['nullable', 'boolean'],
            'is_internal' => ['nullable', 'boolean'],
            'attachments' => [
                'nullable',
                'array',
                'max:10',
                Rule::prohibitedIf(fn () => ! filter_var(
                    Setting::get('tickets.user_file_upload_enable', true),
                    FILTER_VALIDATE_BOOLEAN,
                )),
            ],
            'attachments.*' => [
                'file',
                'max:'.$settings->attachmentMaxKilobytes(),
                'mimes:'.implode(',', $settings->attachmentExtensions()),
                new ValidMimeMagicBytes($settings->attachmentMimeTypes()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'to.required' => 'El destinatario es obligatorio.',
            'to.email' => 'El destinatario no es un email válido.',
            'subject.required' => 'El asunto es obligatorio.',
            'body.required' => 'El mensaje es obligatorio.',
            'scheduled_at.after' => 'La fecha de programación debe ser futura.',
            'attachments.*.max' => 'El archivo adjunto no puede superar el límite configurado en Helpdesk.',
            'attachments.*.mimes' => 'El formato del archivo adjunto no es válido.',
            'attachments.max' => 'Puedes adjuntar como máximo 10 archivos.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ticket_id' => 'ticket',
            'to' => 'destinatario',
            'subject' => 'asunto',
            'body' => 'mensaje',
            'scheduled_at' => 'fecha de programación',
        ];
    }
}

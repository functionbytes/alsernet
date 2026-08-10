<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'ticket_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_tickets,id'],
            'to' => ['required', 'email', 'max:255'],
            'cc' => ['nullable', 'array'],
            'cc.*' => ['email'],
            'bcc' => ['nullable', 'array'],
            'bcc.*' => ['email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:helpdesk.helpdesk_ticket_categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'is_internal' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,zip,rar'],
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
            'attachments.*.max' => 'El archivo adjunto no puede superar los 10 MB.',
            'attachments.*.mimes' => 'El formato del archivo adjunto no es válido.',
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

<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketCannedReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'html_body' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'tags' => ['nullable', 'array'],
            'short_code' => ['nullable', 'string', 'max:50', Rule::unique('helpdesk.helpdesk_ticket_canned_replies', 'short_code')->ignore($this->route('reply'))],
            'is_global' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'ticket_categories' => ['nullable', 'array'],
            'ticket_categories.*' => ['exists:helpdesk.helpdesk_ticket_categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El titulo es obligatorio.',
            'title.max' => 'El titulo no puede superar los 255 caracteres.',
            'content.required' => 'El contenido es obligatorio.',
            'short_code.unique' => 'El codigo corto ya esta en uso.',
            'short_code.max' => 'El codigo corto no puede superar los 50 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'titulo',
            'content' => 'contenido',
            'category' => 'categoria',
            'short_code' => 'codigo corto',
            'is_global' => 'global',
            'is_active' => 'estado',
        ];
    }
}

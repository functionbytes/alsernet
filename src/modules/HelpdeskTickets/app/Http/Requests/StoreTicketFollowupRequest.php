<?php

namespace Modules\HelpdeskTickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketFollowupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.required' => 'La fecha del seguimiento es obligatoria.',
            'scheduled_at.after' => 'La fecha del seguimiento debe ser futura.',
            'note.max' => 'La nota no puede superar los 1000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'scheduled_at' => 'fecha del seguimiento',
            'note' => 'nota',
        ];
    }
}

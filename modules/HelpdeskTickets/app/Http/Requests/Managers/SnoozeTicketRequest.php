<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class SnoozeTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'snoozed_until' => ['required', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'snoozed_until.required' => 'La fecha para posponer es obligatoria.',
            'snoozed_until.after' => 'La fecha para posponer debe ser futura.',
        ];
    }

    public function attributes(): array
    {
        return [
            'snoozed_until' => 'fecha para posponer',
        ];
    }
}

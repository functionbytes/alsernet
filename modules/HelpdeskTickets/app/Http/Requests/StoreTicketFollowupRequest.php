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
            // Un paso suelto (compatibilidad con lo que ya llamaba a este
            // endpoint) o una secuencia entera de pasos.
            'scheduled_at' => ['required_without:steps', 'nullable', 'date', 'after:now'],
            'note' => ['nullable', 'string', 'max:1000'],
            'cancel_if_customer_replies' => ['nullable', 'boolean'],
            'steps' => ['nullable', 'array', 'max:6'],
            'steps.*.scheduled_at' => ['required', 'date', 'after:now'],
            'steps.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.required_without' => 'La fecha del seguimiento es obligatoria.',
            'steps.max' => 'Una secuencia admite como mucho 6 pasos.',
            'steps.*.scheduled_at.after' => 'Cada paso de la secuencia debe tener una fecha futura.',
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

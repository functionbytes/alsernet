<?php

namespace Modules\HelpdeskAgents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVacationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.schedule.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'reason' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:pending,approved,rejected'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'El agente es obligatorio.',
            'user_id.exists' => 'El agente seleccionado no existe.',
            'starts_at.required' => 'La fecha de inicio es obligatoria.',
            'starts_at.date' => 'La fecha de inicio no tiene un formato válido.',
            'ends_at.required' => 'La fecha de fin es obligatoria.',
            'ends_at.date' => 'La fecha de fin no tiene un formato válido.',
            'ends_at.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la de inicio.',
            'reason.max' => 'El motivo no puede superar los 500 caracteres.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser pendiente, aprobado o rechazado.',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'agente',
            'starts_at' => 'fecha de inicio',
            'ends_at' => 'fecha de fin',
            'reason' => 'motivo',
            'status' => 'estado',
        ];
    }
}

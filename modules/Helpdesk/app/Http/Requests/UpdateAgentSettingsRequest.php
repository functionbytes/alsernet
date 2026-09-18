<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'accepts_conversations' => ['required', 'in:yes,no,working_hours'],
            'max_concurrent_conversations' => ['nullable', 'integer', 'min:1', 'max:100'],
            'working_hours' => ['nullable', 'array'],
            'working_hours.*.enabled' => ['boolean'],
            'working_hours.*.start' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'working_hours.*.end' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'vacation_until' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'accepts_conversations.required' => 'La disponibilidad del agente es obligatoria.',
            'accepts_conversations.in' => 'La disponibilidad debe ser: sí, no o según horario laboral.',
            'max_concurrent_conversations.integer' => 'El máximo de conversaciones debe ser un número entero.',
            'max_concurrent_conversations.min' => 'El máximo de conversaciones simultáneas debe ser al menos 1.',
            'max_concurrent_conversations.max' => 'El máximo de conversaciones simultáneas no puede superar 100.',
            'working_hours.array' => 'El horario laboral debe ser un array válido.',
            'working_hours.*.start.regex' => 'La hora de inicio debe tener formato HH:mm.',
            'working_hours.*.end.regex' => 'La hora de fin debe tener formato HH:mm.',
            'vacation_until.date' => 'La fecha de vacaciones debe ser una fecha válida.',
            'vacation_until.after' => 'La fecha de vacaciones debe ser en el futuro.',
        ];
    }

    public function attributes(): array
    {
        return [
            'accepts_conversations' => 'disponibilidad',
            'max_concurrent_conversations' => 'máximo de conversaciones simultáneas',
            'working_hours' => 'horario laboral',
            'vacation_until' => 'vacaciones hasta',
        ];
    }
}

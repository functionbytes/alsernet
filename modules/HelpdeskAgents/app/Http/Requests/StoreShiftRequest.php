<?php

namespace Modules\HelpdeskAgents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'El agente es obligatorio.',
            'user_id.exists' => 'El agente seleccionado no existe.',
            'day_of_week.required' => 'El dia es obligatorio.',
            'day_of_week.between' => 'El dia debe estar entre 0 (domingo) y 6 (sabado).',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'start_time.date_format' => 'La hora de inicio debe tener formato HH:MM.',
            'end_time.required' => 'La hora de fin es obligatoria.',
            'end_time.date_format' => 'La hora de fin debe tener formato HH:MM.',
            'end_time.after' => 'La hora de fin debe ser posterior a la de inicio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'agente',
            'day_of_week' => 'dia de la semana',
            'start_time' => 'hora de inicio',
            'end_time' => 'hora de fin',
            'timezone' => 'zona horaria',
        ];
    }
}

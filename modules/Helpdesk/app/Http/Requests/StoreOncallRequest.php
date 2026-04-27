<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOncallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'shift_duration_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'started_at' => ['required', 'date'],
            'current_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'user_ids.required' => 'Debe seleccionar al menos un agente.',
            'user_ids.min' => 'Debe seleccionar al menos un agente.',
            'user_ids.*.exists' => 'Uno o más agentes seleccionados no existen.',
            'shift_duration_hours.required' => 'La duración del turno es obligatoria.',
            'shift_duration_hours.min' => 'La duración mínima es 1 hora.',
            'shift_duration_hours.max' => 'La duración máxima es 720 horas.',
            'started_at.required' => 'La fecha de inicio es obligatoria.',
            'started_at.date' => 'La fecha de inicio no tiene un formato válido.',
            'current_user_id.exists' => 'El agente actual seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'user_ids' => 'agentes',
            'shift_duration_hours' => 'duración del turno (horas)',
            'started_at' => 'fecha de inicio',
            'current_user_id' => 'agente actual',
        ];
    }
}

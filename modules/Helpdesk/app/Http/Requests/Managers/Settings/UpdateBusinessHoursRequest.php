<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.is_open' => ['boolean'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i', 'after:hours.*.opens_at'],
            'timezone' => ['required', 'string', 'max:50', 'timezone'],
        ];
    }

    public function messages(): array
    {
        return [
            'hours.required' => 'Los horarios son obligatorios.',
            'hours.size' => 'Deben configurarse los 7 días de la semana.',
            'hours.*.opens_at.date_format' => 'La hora de apertura debe tener el formato HH:MM.',
            'hours.*.closes_at.date_format' => 'La hora de cierre debe tener el formato HH:MM.',
            'hours.*.closes_at.after' => 'La hora de cierre debe ser posterior a la de apertura.',
            'timezone.required' => 'La zona horaria es obligatoria.',
            'timezone.timezone' => 'La zona horaria seleccionada no es válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'hours' => 'horarios',
            'hours.*.is_open' => 'abierto',
            'hours.*.opens_at' => 'hora de apertura',
            'hours.*.closes_at' => 'hora de cierre',
            'timezone' => 'zona horaria',
        ];
    }
}

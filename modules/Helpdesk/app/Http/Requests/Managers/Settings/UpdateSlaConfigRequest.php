<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSlaConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.sla-policies.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'sla' => ['required', 'array', 'min:1'],
            'sla.*.label' => ['required', 'string', 'max:100'],
            'sla.*.value' => ['required', 'integer', 'min:1', 'max:8760'],
            'pause_off_hours' => ['required', 'boolean'],
            'notify_supervisor' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sla.required' => 'Las políticas de SLA son obligatorias.',
            'sla.*.label.required' => 'Cada fila necesita una prioridad.',
            'sla.*.value.required' => 'El tiempo de primera respuesta es obligatorio.',
            'sla.*.value.integer' => 'El tiempo de primera respuesta debe ser un número entero de horas.',
            'sla.*.value.min' => 'El tiempo de primera respuesta debe ser al menos 1 hora.',
            'sla.*.value.max' => 'El tiempo de primera respuesta no puede superar 8760 horas (1 año).',
        ];
    }

    public function attributes(): array
    {
        return [
            'sla' => 'políticas de SLA',
            'pause_off_hours' => 'pausar SLA fuera de horario',
            'notify_supervisor' => 'notificar al supervisor',
        ];
    }
}

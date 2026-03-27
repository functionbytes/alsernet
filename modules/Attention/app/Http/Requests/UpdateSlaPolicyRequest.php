<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSlaPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $policyId = $this->route('policy');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('attention_sla_policies', 'name')->ignore($policyId)],
            'description' => ['nullable', 'string'],
            'response_time' => ['required', 'integer', 'min:1'],
            'resolution_time' => ['required', 'integer', 'min:1'],
            'closure_time' => ['required', 'integer', 'min:1'],
            'business_hours_only' => ['nullable', 'boolean'],
            'business_hours' => ['nullable', 'array'],
            'timezone' => ['required', 'string'],
            'type_multipliers' => ['nullable', 'array'],
            'enable_escalation' => ['nullable', 'in:0,1'],
            'escalation_threshold_percent' => ['nullable', 'integer', 'min:1', 'max:100'],
            'escalation_recipients' => ['nullable', 'array'],
            'active' => ['nullable', 'in:0,1'],
            'is_default' => ['nullable', 'in:0,1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'response_time' => 'tiempo de respuesta',
            'resolution_time' => 'tiempo de resolución',
            'closure_time' => 'tiempo de cierre',
            'business_hours_only' => 'solo horario laboral',
            'timezone' => 'zona horaria',
            'enable_escalation' => 'escalamiento habilitado',
            'escalation_threshold_percent' => 'porcentaje de escalamiento',
            'escalation_recipients' => 'destinatarios de escalamiento',
        ];
    }
}

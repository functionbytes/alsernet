<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSlaPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.sla-policies.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'first_response_time_hours' => ['required', 'integer', 'min:1', 'max:8760'],
            'resolution_time_hours' => ['required', 'integer', 'min:1', 'max:8760'],
            'warning_threshold_percent' => ['nullable', 'integer', 'min:1', 'max:100'],
            'is_active' => ['boolean'],
            'business_hours_only' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'first_response_time_hours.required' => 'El tiempo de primera respuesta es obligatorio.',
            'first_response_time_hours.integer' => 'El tiempo de primera respuesta debe ser un numero entero.',
            'first_response_time_hours.min' => 'El tiempo de primera respuesta debe ser al menos 1 hora.',
            'first_response_time_hours.max' => 'El tiempo de primera respuesta no puede superar 8760 horas (1 año).',
            'resolution_time_hours.required' => 'El tiempo de resolucion es obligatorio.',
            'resolution_time_hours.integer' => 'El tiempo de resolucion debe ser un numero entero.',
            'resolution_time_hours.min' => 'El tiempo de resolucion debe ser al menos 1 hora.',
            'resolution_time_hours.max' => 'El tiempo de resolucion no puede superar 8760 horas (1 año).',
            'warning_threshold_percent.integer' => 'El umbral de advertencia debe ser un numero entero.',
            'warning_threshold_percent.min' => 'El umbral de advertencia debe ser al menos 1%.',
            'warning_threshold_percent.max' => 'El umbral de advertencia no puede superar el 100%.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripcion',
            'first_response_time_hours' => 'tiempo de primera respuesta',
            'resolution_time_hours' => 'tiempo de resolucion',
            'warning_threshold_percent' => 'umbral de advertencia',
            'is_active' => 'activo',
            'business_hours_only' => 'solo horario laboral',
        ];
    }
}

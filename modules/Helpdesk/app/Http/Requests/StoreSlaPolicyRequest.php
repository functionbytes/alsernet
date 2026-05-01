<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSlaPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.sla-policies.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'first_response_minutes' => ['nullable', 'integer', 'min:1'],
            'resolution_minutes' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'business_hours_only' => ['boolean'],
            'escalation_enabled' => ['boolean'],
            'escalation_minutes' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'first_response_minutes.integer' => 'El tiempo de primera respuesta debe ser un numero entero.',
            'first_response_minutes.min' => 'El tiempo de primera respuesta debe ser al menos 1 minuto.',
            'resolution_minutes.integer' => 'El tiempo de resolucion debe ser un numero entero.',
            'resolution_minutes.min' => 'El tiempo de resolucion debe ser al menos 1 minuto.',
            'escalation_minutes.integer' => 'El tiempo de escalacion debe ser un numero entero.',
            'escalation_minutes.min' => 'El tiempo de escalacion debe ser al menos 1 minuto.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripcion',
            'first_response_minutes' => 'tiempo de primera respuesta',
            'resolution_minutes' => 'tiempo de resolucion',
            'is_active' => 'activo',
            'business_hours_only' => 'solo horario laboral',
            'escalation_enabled' => 'escalacion habilitada',
            'escalation_minutes' => 'tiempo de escalacion',
        ];
    }
}

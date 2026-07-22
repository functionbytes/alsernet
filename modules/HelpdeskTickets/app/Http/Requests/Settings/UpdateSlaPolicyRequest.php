<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSlaPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'channel' => ['nullable', 'string', 'max:50'],
            'first_response_time' => ['required', 'integer', 'min:1'],
            'next_response_time' => ['nullable', 'integer', 'min:1'],
            'resolution_time' => ['required', 'integer', 'min:1'],
            'business_hours_only' => ['nullable', 'boolean'],
            'business_hours' => ['nullable', 'array'],
            'timezone' => ['required', 'string', 'timezone'],
            'priority_multipliers' => ['nullable', 'array'],
            'enable_escalation' => ['nullable', 'boolean'],
            'escalation_threshold_percent' => ['nullable', 'integer', 'min:1', 'max:100'],
            'escalation_recipients' => ['nullable', 'array'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'first_response_time.required' => 'El tiempo de primera respuesta es obligatorio.',
            'first_response_time.min' => 'El tiempo de primera respuesta debe ser al menos 1 minuto.',
            'resolution_time.required' => 'El tiempo de resolucion es obligatorio.',
            'resolution_time.min' => 'El tiempo de resolucion debe ser al menos 1 minuto.',
            'timezone.required' => 'La zona horaria es obligatoria.',
            'timezone.timezone' => 'La zona horaria no es valida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripcion',
            'first_response_time' => 'tiempo de primera respuesta',
            'next_response_time' => 'tiempo de siguiente respuesta',
            'resolution_time' => 'tiempo de resolucion',
            'business_hours_only' => 'solo horas laborales',
            'timezone' => 'zona horaria',
            'enable_escalation' => 'habilitar escalamiento',
            'active' => 'estado',
        ];
    }
}

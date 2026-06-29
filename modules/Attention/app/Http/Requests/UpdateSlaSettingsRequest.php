<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSlaSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sla_enabled' => 'boolean',
            'sla_response_hours' => 'required|integer|min:1|max:720',
            'sla_resolution_hours' => 'required|integer|min:1|max:720',
            'sla_business_hours_only' => 'boolean',
            'sla_business_hours_start' => 'required|string|regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/',
            'sla_business_hours_end' => 'required|string|regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/',
            'sla_exclude_weekends' => 'boolean',
            'sla_auto_escalate' => 'boolean',
            'sla_escalation_email' => 'nullable|email|max:255',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sla_enabled' => 'SLA habilitado',
            'sla_response_hours' => 'horas para primera respuesta',
            'sla_resolution_hours' => 'horas para resolucion',
            'sla_business_hours_only' => 'solo horario laboral',
            'sla_business_hours_start' => 'inicio horario laboral',
            'sla_business_hours_end' => 'fin horario laboral',
            'sla_exclude_weekends' => 'excluir fines de semana',
            'sla_auto_escalate' => 'escalamiento automatico',
            'sla_escalation_email' => 'email de escalamiento',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sla_response_hours.required' => 'El campo :attribute es obligatorio.',
            'sla_response_hours.min' => 'El campo :attribute debe ser al menos :min hora.',
            'sla_response_hours.max' => 'El campo :attribute no puede exceder :max horas.',
            'sla_resolution_hours.required' => 'El campo :attribute es obligatorio.',
            'sla_resolution_hours.min' => 'El campo :attribute debe ser al menos :min hora.',
            'sla_resolution_hours.max' => 'El campo :attribute no puede exceder :max horas.',
            'sla_business_hours_start.required' => 'El campo :attribute es obligatorio.',
            'sla_business_hours_start.regex' => 'El campo :attribute debe estar en formato HH:MM (24 horas).',
            'sla_business_hours_end.required' => 'El campo :attribute es obligatorio.',
            'sla_business_hours_end.regex' => 'El campo :attribute debe estar en formato HH:MM (24 horas).',
            'sla_escalation_email.email' => 'El campo :attribute debe ser una direccion de email valida.',
        ];
    }

    /**
     * Validate that the end time is after the start time.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $start = $this->input('sla_business_hours_start');
            $end = $this->input('sla_business_hours_end');

            if ($start && $end && $start >= $end) {
                $validator->errors()->add(
                    'sla_business_hours_end',
                    'La hora de fin debe ser posterior a la hora de inicio.'
                );
            }
        });
    }
}

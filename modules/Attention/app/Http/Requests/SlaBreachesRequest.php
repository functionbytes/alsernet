<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SlaBreachesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attention.view-reports') ?? false;
    }

    public function rules(): array
    {
        return [
            'breach_type' => ['nullable', 'string', 'in:response,resolution,closure'],
            'department_id' => ['nullable', 'integer', 'exists:attention_departments,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'breach_type.in' => 'El tipo de incumplimiento debe ser: respuesta, resolución o cierre.',
            'department_id.exists' => 'El departamento seleccionado no existe.',
            'date_from.date' => 'La fecha de inicio debe ser una fecha válida.',
            'date_to.date' => 'La fecha de fin debe ser una fecha válida.',
            'date_to.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'per_page.min' => 'El número de registros por página debe ser al menos 1.',
            'per_page.max' => 'El número de registros por página no puede superar 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'breach_type' => 'tipo de incumplimiento',
            'department_id' => 'departamento',
            'date_from' => 'fecha de inicio',
            'date_to' => 'fecha de fin',
            'per_page' => 'registros por página',
        ];
    }
}

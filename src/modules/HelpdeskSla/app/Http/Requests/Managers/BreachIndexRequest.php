<?php

namespace Modules\HelpdeskSla\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class BreachIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesksla.view');
    }

    public function rules(): array
    {
        return [
            'sla_type' => ['nullable', 'string', 'in:first_response,resolution'],
            'resolved' => ['nullable', 'boolean'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'sla_type.in' => 'El tipo de SLA no es válido.',
            'to.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
            'per_page.max' => 'El máximo por página es 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'sla_type' => 'tipo de SLA',
            'resolved' => 'estado',
            'from' => 'fecha inicial',
            'to' => 'fecha final',
            'per_page' => 'por página',
        ];
    }
}

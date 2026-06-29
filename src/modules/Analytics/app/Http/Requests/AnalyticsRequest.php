<?php

namespace Modules\Analytics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnalyticsRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'range' => [
                'nullable',
                'string',
                'in:today,yesterday,last_7_days,last_30_days,this_month,last_month,this_year',
            ],
            'start_date' => [
                'nullable',
                'date',
                'required_with:end_date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
                'required_with:start_date',
            ],
            'metrics' => [
                'nullable',
                'array',
            ],
            'metrics.*' => [
                'string',
                'in:sessions,totalUsers,screenPageViews,bounceRate,engagementRate,averageSessionDuration',
            ],
            'dimensions' => [
                'nullable',
                'string|array',
            ],
            'limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'range' => 'rango de fechas',
            'start_date' => 'fecha de inicio',
            'end_date' => 'fecha de fin',
            'metrics' => 'métricas',
            'dimensions' => 'dimensiones',
            'limit' => 'límite',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'range.in' => 'El rango de fechas seleccionado no es válido.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'metrics.*.in' => 'La métrica seleccionada no es válida.',
            'limit.min' => 'El límite debe ser al menos :min.',
            'limit.max' => 'El límite no puede ser mayor a :max.',
        ];
    }
}

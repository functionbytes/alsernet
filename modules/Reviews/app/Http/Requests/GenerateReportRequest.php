<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.reports.generate');
    }

    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', 'in:location_summary,period_analysis,comparison,response_performance,detailed_export'],
            'format' => ['required', 'string', 'in:csv,excel,pdf'],
            'date_from' => ['required_unless:report_type,comparison', 'nullable', 'date'],
            'date_to' => ['required_unless:report_type,comparison', 'nullable', 'date', 'after_or_equal:date_from'],
            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['integer', 'exists:review_google_locations,id'],
            'group_by' => ['nullable', 'string', 'in:day,week,month'],
            'include_charts' => ['nullable', 'boolean'],
            'period1_from' => ['required_if:report_type,comparison', 'nullable', 'date'],
            'period1_to' => ['required_if:report_type,comparison', 'nullable', 'date', 'after_or_equal:period1_from'],
            'period2_from' => ['required_if:report_type,comparison', 'nullable', 'date'],
            'period2_to' => ['required_if:report_type,comparison', 'nullable', 'date', 'after_or_equal:period2_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'report_type.required' => 'Debe seleccionar un tipo de reporte.',
            'report_type.in' => 'El tipo de reporte seleccionado no es válido.',
            'format.required' => 'Debe seleccionar un formato.',
            'format.in' => 'El formato seleccionado no es válido.',
            'date_from.required_unless' => 'La fecha de inicio es obligatoria.',
            'date_to.required_unless' => 'La fecha de fin es obligatoria.',
            'date_to.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'period1_from.required_if' => 'La fecha de inicio del primer periodo es obligatoria para reportes de comparación.',
            'period2_from.required_if' => 'La fecha de inicio del segundo periodo es obligatoria para reportes de comparación.',
        ];
    }
}

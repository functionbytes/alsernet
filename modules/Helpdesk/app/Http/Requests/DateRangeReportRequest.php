<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DateRangeReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.metrics.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    public function messages(): array
    {
        return [
            'from.date' => 'La fecha de inicio no tiene un formato válido.',
            'to.date' => 'La fecha de fin no tiene un formato válido.',
            'to.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'from' => 'fecha de inicio',
            'to' => 'fecha de fin',
        ];
    }
}

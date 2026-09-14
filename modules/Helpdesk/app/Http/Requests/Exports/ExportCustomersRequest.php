<?php

namespace Modules\Helpdesk\Http\Requests\Exports;

use Illuminate\Foundation\Http\FormRequest;

class ExportCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.exports.create');
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_to.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ];
    }

    public function attributes(): array
    {
        return [
            'date_from' => 'fecha inicial',
            'date_to' => 'fecha final',
        ];
    }
}

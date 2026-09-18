<?php

namespace Modules\Helpdesk\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class CsatReportDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.reports.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_from.date' => 'La fecha de inicio no tiene un formato válido.',
            'date_to.date' => 'La fecha de fin no tiene un formato válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'date_from' => 'fecha de inicio',
            'date_to' => 'fecha de fin',
        ];
    }
}

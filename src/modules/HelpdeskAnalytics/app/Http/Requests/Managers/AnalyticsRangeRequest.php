<?php

namespace Modules\HelpdeskAnalytics\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class AnalyticsRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdeskanalytics.view');
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
            'to.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
        ];
    }

    public function attributes(): array
    {
        return [
            'from' => 'fecha inicial',
            'to' => 'fecha final',
        ];
    }
}

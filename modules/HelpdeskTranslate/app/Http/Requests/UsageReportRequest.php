<?php

namespace Modules\HelpdeskTranslate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UsageReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk-translate.settings.view') ?? false;
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
            'from.date' => __('helpdesktranslate::messages.validation.usage_from_date'),
            'to.date' => __('helpdesktranslate::messages.validation.usage_to_date'),
            'to.after_or_equal' => __('helpdesktranslate::messages.validation.usage_to_after_from'),
        ];
    }

    public function attributes(): array
    {
        return [
            'from' => __('helpdesktranslate::messages.attributes.usage_from'),
            'to' => __('helpdesktranslate::messages.attributes.usage_to'),
        ];
    }
}

<?php

namespace Modules\HelpdeskTranslate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DetectLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk-translate.use') ?? false;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => __('helpdesktranslate::messages.validation.detect_text_required'),
            'text.max' => __('helpdesktranslate::messages.validation.detect_text_max'),
        ];
    }

    public function attributes(): array
    {
        return [
            'text' => __('helpdesktranslate::messages.attributes.text'),
        ];
    }
}

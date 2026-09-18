<?php

namespace Modules\HelpdeskTranslate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TranslateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk-translate.use') ?? false;
    }

    public function rules(): array
    {
        return [
            'target' => ['required', 'string', 'regex:/^[A-Za-z]{2}(-[A-Za-z]{2})?$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'target.required' => __('helpdesktranslate::messages.validation.target_required'),
            'target.regex' => __('helpdesktranslate::messages.validation.target_regex'),
        ];
    }

    public function attributes(): array
    {
        return [
            'target' => __('helpdesktranslate::messages.attributes.target'),
        ];
    }
}

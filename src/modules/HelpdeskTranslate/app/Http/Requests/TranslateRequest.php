<?php

namespace Modules\HelpdeskTranslate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TranslateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk-translate.use') ?? false;
    }

    public function rules(): array
    {
        $sourceLanguages = ['auto', ...config('helpdesktranslate.source_languages', [])];

        return [
            'text' => ['required', 'string', 'max:2000'],
            'from' => ['nullable', 'string', 'in:'.implode(',', $sourceLanguages)],
            'to' => ['required', 'string', 'regex:/^[A-Za-z]{2}(-[A-Za-z]{2})?$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => __('helpdesktranslate::messages.validation.text_required'),
            'text.max' => __('helpdesktranslate::messages.validation.text_max'),
            'from.in' => __('helpdesktranslate::messages.validation.from_in'),
            'to.required' => __('helpdesktranslate::messages.validation.to_required'),
            'to.regex' => __('helpdesktranslate::messages.validation.to_regex'),
        ];
    }

    public function attributes(): array
    {
        return [
            'text' => __('helpdesktranslate::messages.attributes.text'),
            'from' => __('helpdesktranslate::messages.attributes.from'),
            'to' => __('helpdesktranslate::messages.attributes.to'),
        ];
    }
}

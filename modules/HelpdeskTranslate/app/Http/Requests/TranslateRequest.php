<?php

namespace Modules\HelpdeskTranslate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'to' => ['required', 'string', Rule::in(config('helpdesktranslate.supported_languages', []))],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => __('helpdesktranslate::messages.validation.text_required'),
            'text.max' => __('helpdesktranslate::messages.validation.text_max'),
            'from.in' => __('helpdesktranslate::messages.validation.from_in'),
            'to.required' => __('helpdesktranslate::messages.validation.to_required'),
            'to.in' => __('helpdesktranslate::messages.validation.to_in'),
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

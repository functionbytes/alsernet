<?php

namespace Modules\HelpdeskTranslate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TranslateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk-translate.use') ?? false;
    }

    public function rules(): array
    {
        return [
            'target' => ['required', 'string', Rule::in(config('helpdesktranslate.supported_languages', []))],
        ];
    }

    public function messages(): array
    {
        return [
            'target.required' => __('helpdesktranslate::messages.validation.target_required'),
            'target.in' => __('helpdesktranslate::messages.validation.target_in'),
        ];
    }

    public function attributes(): array
    {
        return [
            'target' => __('helpdesktranslate::messages.attributes.target'),
        ];
    }
}

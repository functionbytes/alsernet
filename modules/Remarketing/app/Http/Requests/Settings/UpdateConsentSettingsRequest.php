<?php

namespace Modules\Remarketing\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConsentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.settings.update');
    }

    public function rules(): array
    {
        return [
            'policy_version' => ['required', 'string', 'max:20'],
            'locale_texts' => ['required', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'policy_version' => 'versión de política',
            'locale_texts' => 'textos por idioma',
        ];
    }
}

<?php

namespace Modules\Template\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomCssRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'custom_css' => ['nullable', 'string', 'max:65535'],
        ];
    }

    public function attributes(): array
    {
        return [
            'custom_css' => 'CSS personalizado',
        ];
    }

    public function messages(): array
    {
        return [
            'custom_css.max' => 'El CSS no puede exceder 65535 caracteres.',
        ];
    }
}

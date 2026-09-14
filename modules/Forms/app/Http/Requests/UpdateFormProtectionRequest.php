<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormProtectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.forms.edit');
    }

    public function rules(): array
    {
        return [
            'honeypot_enabled' => ['sometimes', 'boolean'],
            'captcha_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'honeypot_enabled' => 'honeypot',
            'captcha_enabled' => 'captcha',
        ];
    }
}

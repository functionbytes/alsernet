<?php

namespace Modules\Helpdesk\Http\Requests\Compliance;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código',
        ];
    }
}

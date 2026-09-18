<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código de verificación es obligatorio.',
            'code.size' => 'El código debe tener exactamente 6 dígitos.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código de verificación',
        ];
    }
}

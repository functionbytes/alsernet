<?php

namespace Modules\Ecommerce\Http\Requests\Api\V1\Auth;

use App\Http\Api\V1\BaseApiRequest;

class ForgotPasswordRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo electrónico',
        ];
    }
}

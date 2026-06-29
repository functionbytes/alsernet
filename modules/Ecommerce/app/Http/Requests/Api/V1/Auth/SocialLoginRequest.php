<?php

namespace Modules\Ecommerce\Http\Requests\Api\V1\Auth;

use App\Http\Api\V1\BaseApiRequest;

class SocialLoginRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'access_token' => ['required', 'string', 'max:4096'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function messages(): array
    {
        return [
            'access_token.required' => 'El token de acceso del proveedor es obligatorio.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'access_token' => ['description' => 'Token de acceso del proveedor social (Google/Apple/Facebook).', 'example' => 'ya29.a0AfH6SMC...'],
            'device_name' => ['description' => 'Nombre del dispositivo para el token Sanctum.', 'example' => 'Android de Juan'],
        ];
    }
}

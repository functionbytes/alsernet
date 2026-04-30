<?php

namespace Modules\Ecommerce\Http\Requests\Api\V1\Device;

use App\Http\Api\V1\BaseApiRequest;

class RegisterDeviceRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', 'string', 'in:ios,android'],
            'device_id' => ['nullable', 'string', 'max:120'],
            'app_version' => ['nullable', 'string', 'max:32'],
            'locale' => ['nullable', 'string', 'max:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'El token del dispositivo es obligatorio.',
            'platform.required' => 'La plataforma es obligatoria.',
            'platform.in' => 'Plataforma inválida. Debe ser ios o android.',
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'token',
            'platform' => 'plataforma',
            'device_id' => 'identificador del dispositivo',
            'app_version' => 'versión de la app',
            'locale' => 'idioma',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'token' => ['description' => 'Token FCM del dispositivo para push notifications.', 'example' => 'fMb_xyz123...'],
            'platform' => ['description' => 'Plataforma del dispositivo.', 'example' => 'android'],
        ];
    }
}

<?php

namespace Modules\Notification\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RegisterPushTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'device_type' => ['nullable', 'string', 'in:android,ios'],
            'device_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'El token es obligatorio.',
            'device_type.in' => 'El tipo de dispositivo debe ser android o ios.',
            'device_id.max' => 'El identificador del dispositivo no puede exceder 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'token',
            'device_type' => 'tipo de dispositivo',
            'device_id' => 'identificador del dispositivo',
        ];
    }
}

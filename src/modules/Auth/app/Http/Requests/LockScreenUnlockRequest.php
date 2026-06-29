<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LockScreenUnlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'La contraseña es obligatoria para desbloquear.',
        ];
    }

    public function attributes(): array
    {
        return [
            'password' => 'contraseña',
        ];
    }
}

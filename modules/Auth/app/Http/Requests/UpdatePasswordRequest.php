<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Rules\PasswordNotReused;
use Modules\Auth\Rules\StrongPassword;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'confirmed', new StrongPassword, new PasswordNotReused($this->user())],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'La contraseña actual es obligatoria.',
            'new_password.required' => 'La nueva contraseña es obligatoria.',
            'new_password.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => 'contraseña actual',
            'new_password' => 'nueva contraseña',
        ];
    }
}

<?php

namespace Modules\Auth\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Rules\PasswordNotReused;
use Modules\Auth\Rules\StrongPassword;

class ResetPasswordRequest extends FormRequest
{
    // Public endpoint — no auth required
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = User::where('email', $this->input('email'))->first();

        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => array_filter([
                'required',
                'string',
                'confirmed',
                new StrongPassword,
                $user ? new PasswordNotReused($user) : null,
            ]),
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'El token de restablecimiento es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'token',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
        ];
    }
}

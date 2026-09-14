<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string'],
            'confirmation' => ['required', 'string', 'in:ELIMINAR MI CUENTA'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'La contraseña es obligatoria.',
            'confirmation.required' => 'La confirmación es obligatoria.',
            'confirmation.in' => 'Debes escribir exactamente "ELIMINAR MI CUENTA" para confirmar.',
        ];
    }

    public function attributes(): array
    {
        return [
            'password' => 'contraseña',
            'confirmation' => 'confirmación',
        ];
    }
}

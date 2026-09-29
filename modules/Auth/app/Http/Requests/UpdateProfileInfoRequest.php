<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'min:2', 'max:100'],
            'lastname' => ['required', 'string', 'min:2', 'max:100'],
            // 29-sep-2026: el email ya no se cambia aquí (sin contraseña ni confirmación
            // permitía tomar la cuenta con una sesión robada). Lo cambia un administrador
            // desde Usuarios, o el flujo EmailChangeController (pide contraseña y confirma).
            'cellphone' => ['nullable', 'string', 'max:20'],
            'locale' => ['nullable', 'in:es,en'],
        ];
    }

    public function messages(): array
    {
        return [
            'firstname.required' => 'El nombre es obligatorio.',
            'firstname.min' => 'El nombre debe tener al menos 2 caracteres.',
            'lastname.required' => 'El apellido es obligatorio.',
            'lastname.min' => 'El apellido debe tener al menos 2 caracteres.',
            'cellphone.max' => 'El teléfono no puede superar los 20 caracteres.',
            'locale.in' => 'El idioma seleccionado no es válido.',
        ];
    }
}

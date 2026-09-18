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
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.auth()->id()],
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
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Este correo ya está registrado por otro usuario.',
            'cellphone.max' => 'El teléfono no puede superar los 20 caracteres.',
            'locale.in' => 'El idioma seleccionado no es válido.',
        ];
    }
}

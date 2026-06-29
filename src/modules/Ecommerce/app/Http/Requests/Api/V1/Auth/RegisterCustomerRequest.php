<?php

namespace Modules\Ecommerce\Http\Requests\Api\V1\Auth;

use App\Http\Api\V1\BaseApiRequest;

class RegisterCustomerRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:ecommerce_customers,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:30'],
            'device_name' => ['nullable', 'string', 'max:80'],
            'accepts_terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'accepts_terms.accepted' => 'Debes aceptar los términos y condiciones.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'phone' => 'teléfono',
            'device_name' => 'nombre del dispositivo',
            'accepts_terms' => 'términos y condiciones',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Nombre completo del cliente.', 'example' => 'María García'],
            'email' => ['description' => 'Correo electrónico único.', 'example' => 'maria@ejemplo.com'],
            'password' => ['description' => 'Contraseña mínimo 8 caracteres.', 'example' => 'MiPassword123'],
            'password_confirmation' => ['description' => 'Confirmación de la contraseña.', 'example' => 'MiPassword123'],
            'phone' => ['description' => 'Teléfono de contacto.', 'example' => '+57 300 123 4567'],
            'device_name' => ['description' => 'Nombre identificador del dispositivo.', 'example' => 'iPhone de María'],
            'accepts_terms' => ['description' => 'Aceptación de términos y condiciones.', 'example' => true],
        ];
    }
}

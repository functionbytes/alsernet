<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Helpdesk\Models\Customer;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Customer::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:helpdesk.helpdesk_customers,email'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[\+\d][\d\s\-\(\)\.]{3,}$/'],
            'country' => ['nullable', 'string', 'max:2'],
            'language' => ['nullable', 'string', 'max:5'],
            'timezone' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.unique' => 'Ya existe un cliente con ese correo electrónico.',
            'phone.max' => 'El teléfono no puede superar los 30 caracteres.',
            'phone.regex' => 'El teléfono solo puede contener dígitos, espacios, +, -, ( ) y puntos.',
            'country.max' => 'El código de país debe tener máximo 2 caracteres.',
            'language.max' => 'El código de idioma debe tener máximo 5 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'country' => 'país',
            'language' => 'idioma',
            'timezone' => 'zona horaria',
        ];
    }
}

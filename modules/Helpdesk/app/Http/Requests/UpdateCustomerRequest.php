<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('customer')) ?? false;
    }

    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('helpdesk_customers', 'email')->ignore($customer?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:2'],
            'language' => ['nullable', 'string', 'max:5'],
            'timezone' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
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
            'phone.max' => 'El teléfono no puede superar los 20 caracteres.',
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
            'internal_notes' => 'notas internas',
        ];
    }
}

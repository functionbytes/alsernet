<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class GenerateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.conversations.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no es válido.',
            'address.required' => 'La dirección es obligatoria.',
            'city.required' => 'La ciudad es obligatoria.',
            'country.required' => 'El país es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo',
            'phone' => 'teléfono',
            'address' => 'dirección',
            'city' => 'ciudad',
            'state' => 'provincia',
            'country' => 'país',
            'zip_code' => 'código postal',
        ];
    }
}

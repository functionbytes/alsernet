<?php

namespace Modules\Ecommerce\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderShippingAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede tener mas de :max caracteres.',
            'phone.string' => 'El telefono debe ser texto.',
            'phone.max' => 'El telefono no puede tener mas de :max caracteres.',
            'email.email' => 'El correo no tiene un formato valido.',
            'email.max' => 'El correo no puede tener mas de :max caracteres.',
            'address.string' => 'La direccion debe ser texto.',
            'address.max' => 'La direccion no puede tener mas de :max caracteres.',
            'city.string' => 'La ciudad debe ser texto.',
            'city.max' => 'La ciudad no puede tener mas de :max caracteres.',
            'state.string' => 'El estado debe ser texto.',
            'state.max' => 'El estado no puede tener mas de :max caracteres.',
            'country.string' => 'El pais debe ser texto.',
            'country.max' => 'El pais no puede tener mas de :max caracteres.',
        ];
    }
}

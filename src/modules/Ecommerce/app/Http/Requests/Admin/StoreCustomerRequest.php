<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:ecommerce_customers,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', 'in:active,inactive,pending'],
            'avatar' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electronico',
            'password' => 'contrasena',
            'phone' => 'telefono',
            'status' => 'estado',
            'avatar' => 'avatar',
        ];
    }
}

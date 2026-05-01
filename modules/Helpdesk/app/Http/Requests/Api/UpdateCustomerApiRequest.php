<?php

namespace Modules\Helpdesk\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @bodyParam name string Customer display name. Example: María García
 * @bodyParam email string Customer email address. Example: maria@example.com
 * @bodyParam phone string Customer phone number. Example: +34600123456
 */
class UpdateCustomerApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.customers.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('helpdesk.helpdesk_customers', 'email')->ignore($this->route('customer')),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'El email no tiene un formato válido.',
            'email.unique' => 'Ya existe un cliente con ese email.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
        ];
    }
}

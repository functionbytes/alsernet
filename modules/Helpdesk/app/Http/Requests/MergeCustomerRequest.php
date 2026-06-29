<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Helpdesk\Models\Customer;

class MergeCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.customers.merge');
    }

    public function rules(): array
    {
        return [
            'base_customer_id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! Customer::withoutTrashed()->where('id', $value)->exists()) {
                        $fail('El contacto base no existe.');
                    }
                },
            ],
            'mergee_customer_id' => [
                'required',
                'integer',
                'different:base_customer_id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! Customer::withoutTrashed()->where('id', $value)->exists()) {
                        $fail('El contacto a fusionar no existe.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'base_customer_id.required' => 'El contacto base es obligatorio.',
            'base_customer_id.integer' => 'El ID del contacto base debe ser un número entero.',
            'mergee_customer_id.required' => 'El contacto a fusionar es obligatorio.',
            'mergee_customer_id.integer' => 'El ID del contacto a fusionar debe ser un número entero.',
            'mergee_customer_id.different' => 'No se puede fusionar un contacto consigo mismo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'base_customer_id' => 'contacto base',
            'mergee_customer_id' => 'contacto a fusionar',
        ];
    }
}

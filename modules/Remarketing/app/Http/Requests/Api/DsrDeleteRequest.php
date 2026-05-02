<?php

namespace Modules\Remarketing\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DsrDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.customers.manage');
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:remarketing_customers,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'El ID de cliente es obligatorio.',
            'customer_id.exists' => 'El cliente seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
        ];
    }
}

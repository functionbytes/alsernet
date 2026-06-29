<?php

namespace Modules\HelpdeskPrestashop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskprestashop.orders.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_email' => ['nullable', 'email:rfc'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_email.email' => 'El correo del cliente no tiene un formato válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_email' => 'correo del cliente',
        ];
    }
}

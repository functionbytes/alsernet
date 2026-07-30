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
            // Obligatorio: sin el email, el pedido se resuelve solo por ID
            // secuencial en el bridge PrestaShop, sin verificar que pertenezca
            // al cliente — permitía consultar pedidos de otros clientes.
            'customer_email' => ['required', 'email:rfc'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_email.required' => 'El correo del cliente es obligatorio.',
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

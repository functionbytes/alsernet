<?php

namespace Modules\HelpdeskPrestashop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskprestashop.orders.return') ?? false;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_detail_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.reason' => ['nullable', 'string', 'max:500'],
            // Obligatorio: sin el email, la devolucion se inicia solo por ID
            // secuencial en el bridge PrestaShop, sin verificar que el pedido
            // pertenezca al cliente — permitía devolver pedidos de otros.
            'customer_email' => ['required', 'email:rfc'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debe indicar al menos un artículo para devolver.',
            'items.array' => 'El campo artículos debe ser una lista.',
            'items.min' => 'Debe incluir al menos un artículo.',
            'items.*.order_detail_id.required' => 'El identificador de línea de pedido es obligatorio.',
            'items.*.order_detail_id.integer' => 'El identificador de línea de pedido debe ser un número entero.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.integer' => 'La cantidad debe ser un número entero.',
            'items.*.quantity.min' => 'La cantidad mínima a devolver es 1.',
            'items.*.reason.string' => 'El motivo debe ser texto.',
            'items.*.reason.max' => 'El motivo no puede superar los 500 caracteres.',
            'customer_email.required' => 'El correo del cliente es obligatorio.',
            'customer_email.email' => 'El correo del cliente no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'items' => 'artículos',
            'items.*.order_detail_id' => 'identificador de línea',
            'items.*.quantity' => 'cantidad',
            'items.*.reason' => 'motivo',
            'customer_email' => 'correo del cliente',
        ];
    }
}

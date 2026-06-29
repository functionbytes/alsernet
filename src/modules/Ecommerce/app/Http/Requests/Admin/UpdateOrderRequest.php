<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:pending,processing,shipped,completed,cancelled'],
            'shipping_method' => ['nullable', 'string', 'max:255'],
            'shipping_option' => ['nullable', 'string', 'max:255'],
            'payment_status' => ['required', 'string', 'in:pending,paid,failed,refunded'],
            'customer_note' => ['nullable', 'string'],
            'admin_note' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'estado',
            'shipping_method' => 'metodo de envio',
            'shipping_option' => 'opcion de envio',
            'payment_status' => 'estado de pago',
            'customer_note' => 'nota del cliente',
            'admin_note' => 'nota admin',
        ];
    }
}

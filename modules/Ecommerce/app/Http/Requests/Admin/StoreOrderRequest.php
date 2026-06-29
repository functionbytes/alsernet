<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'exists:ecommerce_customers,id'],
            'status' => ['required', 'string', 'in:pending,processing,shipped,completed,cancelled'],
            'sub_total' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'customer_note' => ['nullable', 'string'],
            'admin_note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:ecommerce_products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
            'status' => 'estado',
            'sub_total' => 'subtotal',
            'tax_amount' => 'impuestos',
            'shipping_amount' => 'envio',
            'discount_amount' => 'descuento',
            'total' => 'total',
            'payment_method' => 'metodo de pago',
            'customer_note' => 'nota del cliente',
            'admin_note' => 'nota admin',
        ];
    }
}

<?php

namespace Modules\Ecommerce\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;

class SaveManageOrderRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'sub_total' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'shipping_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string'],
            'payment_status' => ['nullable', 'string'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'La orden debe tener al menos un producto.',
            'items.min' => 'La orden debe tener al menos un producto.',
            'items.*.product_id.required' => 'Cada item debe tener un producto.',
            'items.*.qty.required' => 'La cantidad es obligatoria.',
            'items.*.qty.integer' => 'La cantidad debe ser un numero entero.',
            'items.*.qty.min' => 'La cantidad debe ser al menos 1.',
            'items.*.price.required' => 'El precio es obligatorio.',
            'items.*.price.numeric' => 'El precio debe ser un numero.',
            'items.*.price.min' => 'El precio no puede ser negativo.',
            'sub_total.required' => 'El subtotal es obligatorio.',
            'sub_total.numeric' => 'El subtotal debe ser un numero.',
            'sub_total.min' => 'El subtotal no puede ser negativo.',
            'total.required' => 'El total es obligatorio.',
            'total.numeric' => 'El total debe ser un numero.',
            'total.min' => 'El total no puede ser negativo.',
            'transaction_id.max' => 'El ID de transaccion no puede tener mas de :max caracteres.',
            'customer_note.max' => 'La nota no puede tener mas de :max caracteres.',
        ];
    }
}

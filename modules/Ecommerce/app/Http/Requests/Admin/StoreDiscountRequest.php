<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255', 'unique:ecommerce_discounts,code'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'value' => ['required', 'numeric', 'min:0'],
            'type' => ['required', 'string', 'in:fixed,percentage,free_shipping'],
            'min_order_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'titulo',
            'code' => 'codigo',
            'start_date' => 'fecha inicio',
            'end_date' => 'fecha fin',
            'quantity' => 'cantidad',
            'value' => 'valor',
            'type' => 'tipo',
            'min_order_price' => 'precio minimo de orden',
            'is_active' => 'activo',
            'description' => 'descripcion',
        ];
    }
}

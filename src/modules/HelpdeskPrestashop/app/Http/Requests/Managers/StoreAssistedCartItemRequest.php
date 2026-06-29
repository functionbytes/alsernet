<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssistedCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.conversations.update');
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:ecommerce_products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'conversation_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Debes seleccionar un producto.',
            'product_id.exists' => 'El producto seleccionado no existe.',
            'quantity.min' => 'La cantidad mínima es 1.',
            'quantity.max' => 'La cantidad máxima es 999.',
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'producto',
            'quantity' => 'cantidad',
        ];
    }
}

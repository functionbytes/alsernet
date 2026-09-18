<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class StartOrderReturnRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Selecciona al menos un artículo para devolver.',
            'items.min' => 'Selecciona al menos un artículo para devolver.',
            'items.*.order_detail_id.required' => 'Falta el identificador de línea del pedido.',
            'items.*.quantity.min' => 'La cantidad a devolver debe ser al menos 1.',
        ];
    }

    public function attributes(): array
    {
        return [
            'items' => 'artículos',
            'items.*.quantity' => 'cantidad',
        ];
    }
}

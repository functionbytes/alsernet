<?php

namespace Modules\GiftMessage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateGiftMessagePdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('giftmessage.create');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['envelope', 'card'])],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.id_order' => ['required', 'integer'],
            'rows.*.gift_message' => ['required', 'string'],
            'rows.*.firstname' => ['nullable', 'string'],
            'rows.*.lastname' => ['nullable', 'string'],
            'rows.*.id_gestion' => ['nullable', 'string'],
            'rows.*.npedidocli' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'El tipo es obligatorio.',
            'type.in' => 'El tipo debe ser sobre o tarjeta.',
            'rows.required' => 'Debes seleccionar al menos un pedido.',
            'rows.min' => 'Debes seleccionar al menos un pedido.',
            'rows.*.id_order.required' => 'Cada pedido debe tener un id valido.',
            'rows.*.gift_message.required' => 'Cada pedido debe tener un mensaje regalo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'tipo',
            'rows' => 'pedidos',
        ];
    }
}

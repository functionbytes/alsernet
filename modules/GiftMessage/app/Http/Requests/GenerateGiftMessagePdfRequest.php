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
            'rows' => ['required', 'array', 'min:1', 'max:500'],
            'rows.*.id_order' => ['required', 'integer'],
            'rows.*.gift_message' => ['required', 'string', 'max:5000'],
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
            'rows.max' => 'Como mucho 500 pedidos por PDF; divide el lote en varios.',
            'rows.*.gift_message.max' => 'Algun mensaje regalo supera los 5.000 caracteres y no se puede imprimir.',
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

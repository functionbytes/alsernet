<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Registrar un vínculo pedido ↔ conversación desde el navegador. Solo las
 * fuentes que ve el cliente: "action" la registra el servidor al terminar una
 * escritura sobre el pedido (OrderlinkRecordStoreAction), no el navegador.
 * La autorización (conversación visible + permiso de pedidos) va en el
 * controlador, que ya tiene la conversación resuelta.
 */
class OrderlinkStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ps_order_id' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'ps_order_reference' => ['nullable', 'string', 'max:64', 'regex:/^#?[A-Za-z0-9_\-]+$/'],
            'source' => ['required', 'in:opened,card_sent'],
        ];
    }

    public function messages(): array
    {
        return [
            'ps_order_id.required' => 'Falta el pedido.',
            'ps_order_reference.regex' => 'La referencia del pedido no es válida.',
            'source.in' => 'Origen del vínculo no válido.',
        ];
    }
}

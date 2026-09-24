<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CartpayConvertRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El permiso de estados "pagados" y el acceso a ESTE cliente
        // (CustomerPolicy) los comprueba el controlador.
        return $this->user()?->can('helpdeskprestashop.cartpay.convert') ?? false;
    }

    public function rules(): array
    {
        return [
            'state' => ['required', 'string', Rule::in(array_keys((array) config('helpdeskprestashop.ext.cartpay.convert.states', [])))],
            'conversation_id' => ['nullable', 'integer'],
            // Opcional: si falta, la tienda envía order_conf como siempre
            // (otros llamantes, p. ej. Contactos 360, no lo mandan).
            'send_confirmation' => ['sometimes', 'boolean'],
        ];
    }

    /** true salvo que se pida explícitamente no enviar el correo. */
    public function sendConfirmation(): bool
    {
        return $this->has('send_confirmation') ? $this->boolean('send_confirmation') : true;
    }

    public function messages(): array
    {
        return [
            'state.required' => 'Elige el estado del pedido a crear.',
            'state.in' => 'Ese estado no se puede usar al crear un pedido.',
            'send_confirmation.boolean' => 'Indica si se envía o no el correo de confirmación.',
        ];
    }
}

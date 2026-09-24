<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reembolso parcial (pieza 08). El permiso y el límite por rol los comprueba
 * el controlador, que además exige acceso a ESTE cliente (CustomerPolicy).
 */
class RefundsIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // Sin líneas solo tiene sentido si se devuelve el envío (after()).
            // No 'present': jQuery no serializa un array vacío, así que un
            // reembolso solo de envío llega sin la clave 'lines'.
            'lines' => ['nullable', 'array', 'max:100'],
            'lines.*.order_detail_id' => ['required', 'integer', 'min:1', 'distinct'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'refund_shipping' => ['sometimes', 'boolean'],
            'destination' => ['required', 'in:payment,voucher'],
            'restock' => ['sometimes', 'boolean'],
            'conversation_id' => ['nullable', 'integer'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if (! $this->boolean('refund_shipping') && count((array) $this->input('lines', [])) === 0) {
                    $validator->errors()->add('lines', 'Marca al menos una línea o los gastos de envío.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'lines.*.order_detail_id.distinct' => 'Hay una línea repetida.',
            'lines.*.quantity.min' => 'La cantidad mínima por línea es 1.',
            'destination.in' => 'Elige a dónde va el reembolso.',
        ];
    }
}

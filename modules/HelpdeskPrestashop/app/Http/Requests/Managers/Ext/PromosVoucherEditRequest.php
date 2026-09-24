<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;

class PromosVoucherEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El acceso a ESTE cliente (CustomerPolicy) lo comprueba el controlador.
        return $this->user()?->can('helpdeskprestashop.vouchers.edit') ?? false;
    }

    public function rules(): array
    {
        $cfg = (array) config('helpdeskprestashop.ext.promos.edit', []);

        return [
            'mode' => ['required', 'string', 'in:edit,duplicate'],
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:500'],
            'percent' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
            'minimum' => ['required', 'numeric', 'min:0', 'max:'.(float) ($cfg['max_minimum'] ?? 10000)],
            // El tope de días lo aplica el puente, que sabe si la fecha es la
            // que el cupón ya tenía (conservarla siempre vale).
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'conversation_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'mode.in' => 'Acción no válida.',
            'amount.min' => 'El importe debe ser mayor que 0.',
            'amount.max' => 'El importe máximo es 500,00 €.',
            'percent.min' => 'El porcentaje debe ser mayor que 0.',
            'percent.max' => 'El porcentaje no puede superar el 100 %.',
            'minimum.required' => 'Indica el pedido mínimo (0 si no hay).',
            'minimum.max' => 'El pedido mínimo es demasiado alto.',
            'date_to.required' => 'Indica la fecha de caducidad.',
            'date_to.after_or_equal' => 'La caducidad no puede ser anterior a hoy.',
            'quantity.required' => 'Indica los usos.',
            'quantity.min' => 'El cupón necesita al menos 1 uso.',
            'quantity.max' => 'Demasiados usos para un cupón personal.',
        ];
    }

    public function attributes(): array
    {
        return [
            'amount' => 'importe',
            'percent' => 'porcentaje',
            'minimum' => 'pedido mínimo',
            'date_to' => 'caducidad',
            'quantity' => 'usos',
        ];
    }
}

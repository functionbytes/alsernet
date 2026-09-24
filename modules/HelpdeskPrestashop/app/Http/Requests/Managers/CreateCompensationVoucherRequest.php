<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCompensationVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El permiso y el límite por rol los comprueba el controlador, que
        // además exige acceso a ESTE cliente (CustomerPolicy).
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:500'],
            'validity_days' => ['required', 'integer', Rule::in(config('helpdeskprestashop.vouchers.validity_days', [30, 60, 90]))],
            'reason' => ['required', 'string', Rule::in(array_keys(config('helpdeskprestashop.vouchers.reasons', [])))],
            'conversation_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Indica el importe del vale.',
            'amount.min' => 'El importe mínimo es 1,00 €.',
            'amount.max' => 'El importe máximo es 500,00 €.',
            'validity_days.in' => 'La validez debe ser de 30, 60 o 90 días.',
            'reason.in' => 'Elige un motivo de la lista.',
        ];
    }
}

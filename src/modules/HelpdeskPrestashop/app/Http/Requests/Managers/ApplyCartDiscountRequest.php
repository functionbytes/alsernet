<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCartDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.conversations.update');
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:50'],
            'shipping_amount' => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.max' => 'El código no puede superar los 50 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código de descuento',
            'shipping_amount' => 'gastos de envío',
        ];
    }
}

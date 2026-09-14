<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class SendOrderEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskprestashop.orders.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:order_conf,shipped,order_customer_comment'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Selecciona el tipo de correo.',
            'type.in' => 'El tipo de correo no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'tipo de correo',
        ];
    }
}

<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class ChangeOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskprestashop.orders.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'state_id' => ['required', 'integer', 'min:1'],
            'notify' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'state_id.required' => 'Selecciona un estado de destino.',
        ];
    }

    public function attributes(): array
    {
        return [
            'state_id' => 'estado',
            'notify' => 'notificar al cliente',
        ];
    }
}

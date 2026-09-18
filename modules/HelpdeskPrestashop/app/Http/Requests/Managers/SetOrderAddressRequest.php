<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class SetOrderAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskprestashop.orders.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'address_id' => ['required', 'integer', 'min:1'],
            'type' => ['nullable', 'in:delivery,invoice,both'],
        ];
    }

    public function messages(): array
    {
        return [
            'address_id.required' => 'Selecciona una dirección.',
        ];
    }

    public function attributes(): array
    {
        return [
            'address_id' => 'dirección',
            'type' => 'tipo de dirección',
        ];
    }
}

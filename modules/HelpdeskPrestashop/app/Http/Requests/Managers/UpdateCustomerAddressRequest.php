<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        // addresses.manage es el permiso propio del alta/edición; carts.manage
        // se sigue aceptando porque era el que la protegía antes.
        $user = $this->user();

        return (bool) ($user?->can('helpdeskprestashop.addresses.manage') || $user?->can('helpdeskprestashop.carts.manage'));
    }

    public function rules(): array
    {
        return [
            'alias' => ['sometimes', 'string', 'max:32'],
            'firstname' => ['sometimes', 'string', 'max:64'],
            'lastname' => ['sometimes', 'string', 'max:64'],
            'company' => ['sometimes', 'nullable', 'string', 'max:64'],
            'address1' => ['sometimes', 'string', 'max:128'],
            'address2' => ['sometimes', 'nullable', 'string', 'max:128'],
            'postcode' => ['sometimes', 'string', 'max:12'],
            'city' => ['sometimes', 'string', 'max:64'],
            'id_country' => ['sometimes', 'integer', 'min:1'],
            'id_state' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'dni' => ['sometimes', 'nullable', 'string', 'max:16'],
            'default' => ['sometimes', 'boolean'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'phone_mobile' => ['sometimes', 'nullable', 'string', 'max:32'],
        ];
    }

    public function attributes(): array
    {
        return [
            'firstname' => 'nombre',
            'lastname' => 'apellidos',
            'address1' => 'dirección',
            'postcode' => 'código postal',
            'city' => 'ciudad',
            'id_country' => 'país',
            'id_state' => 'provincia',
            'dni' => 'documento de identidad',
        ];
    }
}

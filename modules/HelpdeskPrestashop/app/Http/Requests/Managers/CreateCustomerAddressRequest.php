<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class CreateCustomerAddressRequest extends FormRequest
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
            'alias' => ['nullable', 'string', 'max:32'],
            'firstname' => ['required', 'string', 'max:64'],
            'lastname' => ['required', 'string', 'max:64'],
            'company' => ['nullable', 'string', 'max:64'],
            'address1' => ['required', 'string', 'max:128'],
            'address2' => ['nullable', 'string', 'max:128'],
            'postcode' => ['required', 'string', 'max:12'],
            'city' => ['required', 'string', 'max:64'],
            // País/provincia/DNI: el bridge valida la combinación contra la
            // configuración real del país (provincias, formato de CP, DNI).
            'id_country' => ['nullable', 'integer', 'min:1'],
            'id_state' => ['nullable', 'integer', 'min:1'],
            'dni' => ['nullable', 'string', 'max:16'],
            'default' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:32'],
            'phone_mobile' => ['nullable', 'string', 'max:32'],
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

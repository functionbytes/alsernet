<?php

namespace Modules\Ecommerce\Http\Requests\Api\V1\Address;

use App\Http\Api\V1\BaseApiRequest;

class UpdateAddressRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('address'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'country' => ['sometimes', 'string', 'max:120'],
            'state' => ['sometimes', 'nullable', 'string', 'max:120'],
            'city' => ['sometimes', 'string', 'max:120'],
            'address' => ['sometimes', 'string', 'max:255'],
            'zip_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'phone' => 'teléfono',
            'country' => 'país',
            'state' => 'estado',
            'city' => 'ciudad',
            'address' => 'dirección',
            'zip_code' => 'código postal',
            'is_default' => 'predeterminada',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Nombre del destinatario.', 'example' => 'María García'],
            'phone' => ['description' => 'Teléfono del destinatario.', 'example' => '+57 300 123 4567'],
            'address' => ['description' => 'Dirección completa.', 'example' => 'Calle 123 # 45-67'],
            'city' => ['description' => 'Ciudad.', 'example' => 'Bogotá'],
            'state' => ['description' => 'Departamento/Estado.', 'example' => 'Cundinamarca'],
            'country' => ['description' => 'Código de país ISO 3166-1 alpha-2.', 'example' => 'CO'],
            'is_default' => ['description' => 'Marcar como dirección predeterminada.', 'example' => true],
        ];
    }
}

<?php

namespace Modules\Ecommerce\Http\Requests\Api\V1\Address;

use App\Http\Api\V1\BaseApiRequest;

class StoreAddressRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'country' => ['required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'country.required' => 'El país es obligatorio.',
            'city.required' => 'La ciudad es obligatoria.',
            'address.required' => 'La dirección es obligatoria.',
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
}

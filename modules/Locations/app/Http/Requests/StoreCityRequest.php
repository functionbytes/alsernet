<?php

namespace Modules\Locations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Locations\Models\State;

class StoreCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('locations.cities.create');
    }

    public function rules(): array
    {
        return [
            'country_id' => ['required', 'integer', 'exists:locations_countries,id'],
            'state_id' => ['required', 'integer', 'exists:locations_states,id'],
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['country_id', 'state_id'])) {
                    return;
                }

                $stateId = $this->input('state_id');
                $countryId = $this->input('country_id');

                $stateExists = State::query()
                    ->where('id', $stateId)
                    ->where('country_id', $countryId)
                    ->exists();

                if (! $stateExists) {
                    $validator->errors()->add('state_id', 'El estado/provincia no pertenece al país seleccionado.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'country_id.required' => 'El país es obligatorio.',
            'country_id.exists' => 'El país seleccionado no existe.',
            'state_id.required' => 'El estado/provincia es obligatorio.',
            'state_id.exists' => 'El estado/provincia seleccionado no existe.',
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 120 caracteres.',
            'order.integer' => 'El orden debe ser un número entero.',
            'order.min' => 'El orden debe ser mayor o igual a 0.',
        ];
    }

    public function attributes(): array
    {
        return [
            'country_id' => 'país',
            'state_id' => 'estado/provincia',
            'name' => 'nombre',
            'is_active' => 'estado',
            'order' => 'orden',
        ];
    }
}

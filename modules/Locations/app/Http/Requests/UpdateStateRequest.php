<?php

namespace Modules\Locations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('locations.states.update');
    }

    public function rules(): array
    {
        return [
            'country_id' => ['required', 'integer', 'exists:locations_countries,id'],
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'country_id.required' => 'El país es obligatorio.',
            'country_id.exists' => 'El país seleccionado no existe.',
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 120 caracteres.',
            'code.max' => 'El código no puede superar los 20 caracteres.',
            'order.integer' => 'El orden debe ser un número entero.',
            'order.min' => 'El orden debe ser mayor o igual a 0.',
        ];
    }

    public function attributes(): array
    {
        return [
            'country_id' => 'país',
            'name' => 'nombre',
            'code' => 'código',
            'is_active' => 'estado',
            'order' => 'orden',
        ];
    }
}

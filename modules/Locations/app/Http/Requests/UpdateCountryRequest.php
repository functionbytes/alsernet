<?php

namespace Modules\Locations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('locations.countries.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:10', Rule::unique('locations_countries', 'code')->ignore($this->route('country'))],
            'phone_code' => ['nullable', 'string', 'max:10'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'currency_symbol' => ['nullable', 'string', 'max:10'],
            'flag_emoji' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 120 caracteres.',
            'code.required' => 'El código es obligatorio.',
            'code.max' => 'El código no puede superar los 10 caracteres.',
            'code.unique' => 'Ya existe un país con ese código.',
            'phone_code.max' => 'El código telefónico no puede superar los 10 caracteres.',
            'currency_code.max' => 'El código de moneda no puede superar los 10 caracteres.',
            'currency_symbol.max' => 'El símbolo de moneda no puede superar los 10 caracteres.',
            'flag_emoji.max' => 'El emoji de bandera no puede superar los 10 caracteres.',
            'order.integer' => 'El orden debe ser un número entero.',
            'order.min' => 'El orden debe ser mayor o igual a 0.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'code' => 'código',
            'phone_code' => 'código telefónico',
            'currency_code' => 'código de moneda',
            'currency_symbol' => 'símbolo de moneda',
            'flag_emoji' => 'emoji de bandera',
            'is_active' => 'estado',
            'order' => 'orden',
        ];
    }
}

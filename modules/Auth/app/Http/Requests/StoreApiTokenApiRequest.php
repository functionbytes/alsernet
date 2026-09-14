<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApiTokenApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del token es obligatorio.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'abilities.array' => 'Las habilidades deben ser un arreglo.',
            'abilities.*.string' => 'Cada habilidad debe ser una cadena de texto.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre del token',
            'abilities' => 'habilidades',
        ];
    }
}

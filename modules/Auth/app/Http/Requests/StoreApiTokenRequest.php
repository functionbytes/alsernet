<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del token es obligatorio.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre del token',
        ];
    }
}

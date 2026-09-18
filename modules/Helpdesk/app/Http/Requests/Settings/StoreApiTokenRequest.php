<?php

namespace Modules\Helpdesk\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super-admin', 'super-settings']) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 80 caracteres.',
            'abilities.array' => 'Los permisos deben ser una lista.',
            'abilities.*.max' => 'Cada permiso no puede superar los 64 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'abilities' => 'permisos',
        ];
    }
}

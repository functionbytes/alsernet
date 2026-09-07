<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormAccessTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.forms.edit');
    }

    public function rules(): array
    {
        return [
            'email' => ['nullable', 'email'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'El correo electrónico no es válido.',
            'max_uses.min' => 'El número máximo de usos debe ser al menos 1.',
            'expires_at.after' => 'La fecha de expiración debe ser posterior a ahora.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo electrónico',
            'max_uses' => 'máximo de usos',
            'expires_at' => 'fecha de expiración',
        ];
    }
}

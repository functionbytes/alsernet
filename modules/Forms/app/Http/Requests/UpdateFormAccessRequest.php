<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.settings.manage');
    }

    public function rules(): array
    {
        return [
            'access_control' => ['required', 'in:public,authenticated,roles'],
            'allowed_roles' => ['nullable', 'array'],
            'allowed_roles.*' => ['string'],
            'is_password_protected' => ['boolean'],
            'password' => ['nullable', 'string', 'min:6', 'max:100'],
            'limit_per_user' => ['boolean'],
            'prevent_duplicate_email' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'access_control.in' => 'El tipo de acceso no es válido.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'access_control' => 'control de acceso',
            'allowed_roles' => 'roles permitidos',
            'is_password_protected' => 'protección por contraseña',
            'password' => 'contraseña',
        ];
    }
}

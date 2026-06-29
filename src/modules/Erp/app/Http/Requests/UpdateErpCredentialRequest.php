<?php

namespace Modules\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateErpCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'auth_type' => 'required|in:none,basic,bearer,api_key,custom',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:500',
            'token' => 'nullable|string|max:500',
            'api_key' => 'nullable|string|max:500',
            'api_key_header' => 'nullable|string|max:100',
            'custom_headers' => 'nullable|array',
            'custom_headers.*' => 'string|max:500',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'auth_type.required' => 'El tipo de autenticación es requerido',
        ];
    }
}

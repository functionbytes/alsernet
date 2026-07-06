<?php

namespace Modules\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreErpCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('erp.endpoints.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'auth_type' => 'required|in:none,basic,bearer,api_key,custom',
            // La columna es NOT NULL: sin regla `required` una credencial sin
            // nombre reventaba con 500 en el insert en vez de un 422 limpio.
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'username' => 'required_if:auth_type,basic|nullable|string|max:255',
            'password' => 'required_if:auth_type,basic|nullable|string|max:500',
            'token' => 'required_if:auth_type,bearer|nullable|string|max:500',
            'api_key' => 'required_if:auth_type,api_key|nullable|string|max:500',
            'api_key_header' => 'required_if:auth_type,api_key|nullable|string|max:100',
            'custom_headers' => 'required_if:auth_type,custom|nullable|array',
            'custom_headers.*' => 'string|max:500',
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'auth_type.required' => 'El tipo de autenticación es requerido',
            'name.required' => 'El nombre de la credencial es requerido',
            'username.required_if' => 'El usuario es requerido para autenticación básica',
            'password.required_if' => 'La contraseña es requerida para autenticación básica',
            'token.required_if' => 'El token es requerido para autenticación bearer',
            'api_key.required_if' => 'La clave API es requerida',
            'api_key_header.required_if' => 'El encabezado de la clave API es requerido',
        ];
    }
}

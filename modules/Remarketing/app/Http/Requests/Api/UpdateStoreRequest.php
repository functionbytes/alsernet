<?php

namespace Modules\Remarketing\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.stores.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'domain' => ['sometimes', 'string', 'max:255'],
            'access_token' => ['nullable', 'string', 'max:1000'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'string', 'in:active,paused,disconnected'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'status.in' => 'El estado debe ser active, paused o disconnected.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'domain' => 'dominio',
            'access_token' => 'token de acceso',
            'api_key' => 'clave API',
            'status' => 'estado',
            'settings' => 'configuración',
        ];
    }
}

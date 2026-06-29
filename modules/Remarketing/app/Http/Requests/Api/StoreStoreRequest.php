<?php

namespace Modules\Remarketing\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.stores.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'string', 'in:shopify,woocommerce,prestashop,magento,bigcommerce'],
            'domain' => ['required', 'string', 'max:255'],
            'access_token' => ['nullable', 'string', 'max:1000'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la tienda es obligatorio.',
            'platform.required' => 'La plataforma es obligatoria.',
            'platform.in' => 'La plataforma debe ser shopify, woocommerce, prestashop, magento o bigcommerce.',
            'domain.required' => 'El dominio es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'platform' => 'plataforma',
            'domain' => 'dominio',
            'access_token' => 'token de acceso',
            'api_key' => 'clave API',
            'settings' => 'configuración',
        ];
    }
}

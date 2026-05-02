<?php

namespace Modules\Remarketing\Http\Requests\Web;

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
            'access_token' => ['nullable', 'string'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'api_secret' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'platform.required' => 'La plataforma es obligatoria.',
            'platform.in' => 'La plataforma seleccionada no es válida.',
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
            'api_key' => 'api key',
            'api_secret' => 'api secret',
        ];
    }
}

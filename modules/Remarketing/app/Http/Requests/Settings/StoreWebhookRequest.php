<?php

namespace Modules\Remarketing\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.settings.update');
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', 'exists:remarketing_stores,id'],
            'url' => ['required', 'url', 'max:500'],
            'secret' => ['nullable', 'string', 'max:64'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_id.required' => 'La tienda es obligatoria.',
            'store_id.exists' => 'La tienda seleccionada no existe.',
            'url.required' => 'La URL del endpoint es obligatoria.',
            'url.url' => 'La URL no tiene un formato válido.',
            'events.required' => 'Selecciona al menos un evento.',
            'events.min' => 'Selecciona al menos un evento.',
        ];
    }

    public function attributes(): array
    {
        return [
            'store_id' => 'tienda',
            'url' => 'URL del endpoint',
            'secret' => 'secreto',
            'events' => 'eventos',
        ];
    }
}

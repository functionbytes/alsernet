<?php

namespace Modules\Mailrelay\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('mailrelay.settings.webhooks');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:255'],
            'event_type' => ['required', 'string', 'max:100'],
            'secret' => ['nullable', 'string', 'max:255'],
            'active' => ['boolean'],
            'verify_ssl' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del webhook es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'url.required' => 'La URL del webhook es obligatoria.',
            'url.url' => 'La URL del webhook no es válida.',
            'url.max' => 'La URL no puede superar los 255 caracteres.',
            'event_type.required' => 'El tipo de evento es obligatorio.',
            'event_type.max' => 'El tipo de evento no puede superar los 100 caracteres.',
            'secret.max' => 'El secreto no puede superar los 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'url' => 'URL',
            'event_type' => 'tipo de evento',
            'secret' => 'secreto',
            'active' => 'activo',
            'verify_ssl' => 'verificar SSL',
        ];
    }
}

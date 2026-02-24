<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.settings.manage');
    }

    public function rules(): array
    {
        return [
            'sync_interval_minutes' => ['sometimes', 'integer', 'min:5', 'max:1440'],
            'auto_publish_replies' => ['sometimes', 'boolean'],
            'default_moderation_visible' => ['sometimes', 'boolean'],
            'google_client_id' => ['sometimes', 'string', 'max:500'],
            'google_client_secret' => ['sometimes', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'sync_interval_minutes.integer' => 'El intervalo debe ser un número entero',
            'sync_interval_minutes.min' => 'El intervalo mínimo es de 5 minutos',
            'sync_interval_minutes.max' => 'El intervalo máximo es de 1440 minutos (24 horas)',
            'auto_publish_replies.boolean' => 'Debe ser un valor booleano',
            'default_moderation_visible.boolean' => 'Debe ser un valor booleano',
            'google_client_id.max' => 'El Client ID no puede exceder 500 caracteres',
            'google_client_secret.max' => 'El Client Secret no puede exceder 500 caracteres',
        ];
    }
}

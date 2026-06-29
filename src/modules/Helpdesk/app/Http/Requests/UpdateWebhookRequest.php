<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\WebhooksController;

class UpdateWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.webhooks.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
            'integration_type' => ['nullable', 'string', 'in:generic,slack,discord,teams'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', 'in:'.implode(',', array_keys(WebhooksController::AVAILABLE_EVENTS))],
            'secret' => ['nullable', 'string', 'max:64'],
            'headers' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'url.required' => 'La URL es obligatoria.',
            'url.url' => 'La URL no tiene un formato valido.',
            'url.max' => 'La URL no puede superar los 500 caracteres.',
            'events.required' => 'Debes seleccionar al menos un evento.',
            'events.min' => 'Debes seleccionar al menos un evento.',
            'events.*.in' => 'Uno o más eventos seleccionados no son válidos.',
            'secret.max' => 'El secreto no puede superar los 64 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'url' => 'URL',
            'integration_type' => 'tipo de integración',
            'events' => 'eventos',
            'secret' => 'secreto',
            'headers' => 'cabeceras',
            'is_active' => 'activo',
        ];
    }
}

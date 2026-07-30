<?php

namespace Modules\Helpdesk\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSlackIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.slack.manage');
    }

    public function rules(): array
    {
        return [
            'webhook_url' => ['nullable', 'url', 'starts_with:https://hooks.slack.com/'],
            'channel_name' => ['required', 'string', 'max:100'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['in:sla_breach,urgent_assigned,csat_low,sentiment_negative'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'webhook_url.url' => 'La URL del webhook no tiene un formato valido.',
            'webhook_url.starts_with' => 'La URL debe ser un webhook entrante de Slack (https://hooks.slack.com/).',
            'channel_name.required' => 'El nombre del canal es obligatorio.',
            'channel_name.max' => 'El nombre del canal no puede superar los 100 caracteres.',
            'events.required' => 'Debes seleccionar al menos un evento.',
            'events.min' => 'Debes seleccionar al menos un evento.',
            'events.*.in' => 'Uno de los eventos seleccionados no es valido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'webhook_url' => 'URL del webhook',
            'channel_name' => 'nombre del canal',
            'events' => 'eventos',
            'is_active' => 'estado',
        ];
    }
}

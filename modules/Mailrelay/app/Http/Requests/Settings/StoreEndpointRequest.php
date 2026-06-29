<?php

namespace Modules\Mailrelay\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreEndpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('mailrelay.settings.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'endpoint_key' => ['required', 'string', 'max:255', 'unique:mailrelay_endpoints,endpoint_key', 'alpha_dash'],
            'description' => ['nullable', 'string'],
            'template_id' => ['required', 'exists:mails_email_templates,id'],
            'status' => ['in:active,inactive'],
            'rate_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'allowed_ips' => ['nullable', 'string'],
            'webhook_url' => ['nullable', 'url', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del endpoint es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'endpoint_key.required' => 'La clave del endpoint es obligatoria.',
            'endpoint_key.unique' => 'Esta clave de endpoint ya está en uso.',
            'endpoint_key.alpha_dash' => 'La clave solo puede contener letras, números, guiones y guiones bajos.',
            'template_id.required' => 'Debe seleccionar una plantilla.',
            'template_id.exists' => 'La plantilla seleccionada no existe.',
            'status.in' => 'El estado seleccionado no es válido.',
            'rate_limit.min' => 'El límite de tasa debe ser al menos 1.',
            'rate_limit.max' => 'El límite de tasa no puede superar 1000.',
            'webhook_url.url' => 'La URL del webhook no es válida.',
            'webhook_url.max' => 'La URL del webhook no puede superar los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'endpoint_key' => 'clave del endpoint',
            'description' => 'descripción',
            'template_id' => 'plantilla',
            'status' => 'estado',
            'rate_limit' => 'límite de tasa',
            'allowed_ips' => 'IPs permitidas',
            'webhook_url' => 'URL del webhook',
        ];
    }
}

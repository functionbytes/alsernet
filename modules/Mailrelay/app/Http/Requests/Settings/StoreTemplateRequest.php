<?php

namespace Modules\Mailrelay\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('mailrelay.settings.templates');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'active' => ['boolean'],
            'use_html' => ['boolean'],
            'mailrelay_template_id' => ['nullable', 'integer'],
            'layout_id' => ['nullable', 'exists:mailrelay_layouts,id'],
            'use_layout' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la plantilla es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'event_type.required' => 'El tipo de evento es obligatorio.',
            'event_type.max' => 'El tipo de evento no puede superar los 100 caracteres.',
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'body.required' => 'El cuerpo de la plantilla es obligatorio.',
            'layout_id.exists' => 'El layout seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'event_type' => 'tipo de evento',
            'subject' => 'asunto',
            'body' => 'cuerpo',
            'active' => 'activo',
            'use_html' => 'usar HTML',
            'mailrelay_template_id' => 'ID plantilla Mailrelay',
            'layout_id' => 'layout',
            'use_layout' => 'usar layout',
        ];
    }
}

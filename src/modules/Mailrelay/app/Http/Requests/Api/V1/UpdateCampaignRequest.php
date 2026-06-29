<?php

namespace Modules\Mailrelay\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via $this->authorize() in controller
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'mailer_template_id' => ['nullable', 'exists:mailer_templates,id'],
            'html_content' => ['nullable', 'string'],
            'lang_id' => ['nullable', 'exists:langs,id'],
            'mail_provider_id' => ['required', 'exists:mail_providers,id'],
            'template_variables' => ['nullable', 'array'],
            'track_opens' => ['boolean'],
            'track_clicks' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la campaña es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'mail_provider_id.required' => 'El proveedor de correo es obligatorio.',
            'mail_provider_id.exists' => 'El proveedor de correo seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'subject' => 'asunto',
            'mailer_template_id' => 'plantilla',
            'html_content' => 'contenido HTML',
            'lang_id' => 'idioma',
            'mail_provider_id' => 'proveedor de correo',
            'template_variables' => 'variables de plantilla',
            'track_opens' => 'rastrear aperturas',
            'track_clicks' => 'rastrear clics',
        ];
    }
}

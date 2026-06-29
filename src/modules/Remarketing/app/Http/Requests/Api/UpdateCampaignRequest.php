<?php

namespace Modules\Remarketing\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.campaigns.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'subject' => ['sometimes', 'string', 'max:255'],
            'preheader' => ['nullable', 'string', 'max:255'],
            'from_name' => ['sometimes', 'string', 'max:255'],
            'from_email' => ['sometimes', 'email', 'max:255'],
            'template_id' => ['nullable', 'integer', 'exists:remarketing_templates,id'],
            'segment_id' => ['nullable', 'integer', 'exists:remarketing_segments,id'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_email.email' => 'El email del remitente no tiene un formato válido.',
            'template_id.exists' => 'La plantilla seleccionada no existe.',
            'segment_id.exists' => 'El segmento seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'subject' => 'asunto',
            'preheader' => 'preencabezado',
            'from_name' => 'nombre del remitente',
            'from_email' => 'email del remitente',
            'template_id' => 'plantilla',
            'segment_id' => 'segmento',
        ];
    }
}

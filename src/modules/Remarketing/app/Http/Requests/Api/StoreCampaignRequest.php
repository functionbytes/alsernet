<?php

namespace Modules\Remarketing\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.campaigns.create');
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', 'exists:remarketing_stores,id'],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'preheader' => ['nullable', 'string', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'from_email' => ['required', 'email', 'max:255'],
            'template_id' => ['nullable', 'integer', 'exists:remarketing_templates,id'],
            'segment_id' => ['nullable', 'integer', 'exists:remarketing_segments,id'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_id.required' => 'La tienda es obligatoria.',
            'store_id.exists' => 'La tienda seleccionada no existe.',
            'name.required' => 'El nombre de la campaña es obligatorio.',
            'subject.required' => 'El asunto es obligatorio.',
            'from_name.required' => 'El nombre del remitente es obligatorio.',
            'from_email.required' => 'El email del remitente es obligatorio.',
            'from_email.email' => 'El email del remitente no tiene un formato válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'store_id' => 'tienda',
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

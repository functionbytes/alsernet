<?php

namespace Modules\Remarketing\Http\Requests\Web;

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
            'from_name' => ['required', 'string', 'max:100'],
            'from_email' => ['required', 'email'],
            'template_id' => ['nullable', 'integer', 'exists:remarketing_templates,id'],
            'segment_id' => ['nullable', 'integer', 'exists:remarketing_segments,id'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'subject' => 'asunto',
            'preheader' => 'preheader',
            'from_name' => 'remitente',
            'from_email' => 'email del remitente',
            'template_id' => 'plantilla',
            'segment_id' => 'segmento',
        ];
    }
}

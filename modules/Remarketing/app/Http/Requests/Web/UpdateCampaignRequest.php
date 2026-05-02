<?php

namespace Modules\Remarketing\Http\Requests\Web;

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
            'from_name' => 'remitente',
            'from_email' => 'email del remitente',
        ];
    }
}

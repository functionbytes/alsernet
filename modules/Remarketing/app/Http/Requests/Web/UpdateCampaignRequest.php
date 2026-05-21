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
            'lang_id' => ['nullable', 'integer', 'exists:langs,id'],
            'segment_id' => ['nullable', 'integer', 'exists:remarketing_segments,id'],
            'send_mode' => ['nullable', 'string', 'in:now,schedule'],
            'scheduled_at' => ['nullable', 'date'],
            'settings' => ['nullable', 'array'],
            'settings.holdout_pct' => ['nullable', 'integer', 'between:0,50'],
            'ab_winner_metric' => ['nullable', 'string', 'in:open_rate,ctr,revenue'],
            'ab_variants' => ['nullable', 'array'],
            'ab_variants.*.id' => ['nullable', 'integer', 'exists:remarketing_campaign_variants,id'],
            'ab_variants.*.name' => ['required_with:ab_variants', 'string', 'max:100'],
            'ab_variants.*.subject' => ['nullable', 'string', 'max:255'],
            'ab_variants.*.weight' => ['nullable', 'integer', 'between:1,99'],
        ];
    }

    public function messages(): array
    {
        return [
            'settings.holdout_pct.between' => 'El holdout debe estar entre 0% y 50%.',
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

<?php

namespace Modules\Helpdesk\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollDripCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.drip-campaigns.manage');
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('helpdesk.helpdesk_customers', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'El cliente es obligatorio.',
            'customer_id.integer' => 'El cliente no es válido.',
            'customer_id.exists' => 'El cliente indicado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
        ];
    }
}

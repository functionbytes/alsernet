<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollDripCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.drip-campaigns.manage') ?? false;
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
            'customer_id.integer' => 'El cliente debe ser un número entero.',
            'customer_id.exists' => 'El cliente seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
        ];
    }
}

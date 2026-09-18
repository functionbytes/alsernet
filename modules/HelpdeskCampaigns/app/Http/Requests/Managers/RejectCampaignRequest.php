<?php

namespace Modules\HelpdeskCampaigns\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class RejectCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.campaigns.manage');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'El motivo del rechazo es obligatorio.',
            'reason.max' => 'El motivo no puede superar los 1000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'reason' => 'motivo',
        ];
    }
}

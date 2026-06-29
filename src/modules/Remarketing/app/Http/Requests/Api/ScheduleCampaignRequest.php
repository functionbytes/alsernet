<?php

namespace Modules\Remarketing\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.campaigns.update');
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.required' => 'La fecha de envío es obligatoria.',
            'scheduled_at.date' => 'La fecha de envío no tiene un formato válido.',
            'scheduled_at.after' => 'La fecha de envío debe ser posterior a la fecha actual.',
        ];
    }

    public function attributes(): array
    {
        return [
            'scheduled_at' => 'fecha de envío',
        ];
    }
}

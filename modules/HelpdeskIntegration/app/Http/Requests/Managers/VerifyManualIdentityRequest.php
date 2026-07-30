<?php

namespace Modules\HelpdeskIntegration\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyManualIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('view', $this->route('customer'));
    }

    public function rules(): array
    {
        return [
            'conversation_id' => [
                'nullable',
                'integer',
                Rule::exists('helpdesk.helpdesk_conversations', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'conversation_id.exists' => 'La conversación indicada no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'conversation_id' => 'conversación',
        ];
    }
}

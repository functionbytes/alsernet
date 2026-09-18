<?php

namespace Modules\HelpdeskIntegration\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyIdentityCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('view', $this->route('customer'));
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6'],
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
            'code.required' => 'El código es obligatorio.',
            'code.size' => 'El código debe tener 6 dígitos.',
            'conversation_id.exists' => 'La conversación indicada no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código',
            'conversation_id' => 'conversación',
        ];
    }
}

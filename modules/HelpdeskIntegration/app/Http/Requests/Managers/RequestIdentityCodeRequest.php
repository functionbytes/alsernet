<?php

namespace Modules\HelpdeskIntegration\Http\Requests\Managers;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestIdentityCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('view', $this->route('customer'));
    }

    public function rules(): array
    {
        return [
            'channel' => [
                'required',
                'string',
                Rule::in(['email', 'sms']),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === 'sms' && ! helpdesk_integration_identity_sms_enabled()) {
                        $fail('El canal SMS no está disponible.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'channel.required' => 'El canal es obligatorio.',
            'channel.in' => 'Canal no válido.',
        ];
    }

    public function attributes(): array
    {
        return ['channel' => 'canal'];
    }
}

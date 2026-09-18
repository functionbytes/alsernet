<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SimulateSocialRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesksocial.rules.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
            'platform' => ['required', 'string', 'in:facebook,instagram'],
            'intent' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'El texto es obligatorio.',
            'platform.required' => 'La plataforma es obligatoria.',
            'platform.in' => 'La plataforma seleccionada no es válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'body' => 'texto',
            'platform' => 'plataforma',
            'intent' => 'intención',
        ];
    }
}

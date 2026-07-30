<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocialRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesksocial.rules.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'platform' => 'nullable|string|in:facebook,instagram,whatsapp',
            'conditions' => 'required|array',
            'conditions.*.field' => 'required|string',
            'conditions.*.operator' => 'required|string',
            'conditions.*.value' => 'required',
            'actions' => 'required|array',
            'actions.*.type' => 'required|string',
            'actions.*.params' => 'nullable|array',
            'priority' => 'nullable|integer|min:0|max:9999',
            'stop_processing' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la regla es obligatorio.',
            'conditions.required' => 'Las condiciones son obligatorias.',
            'actions.required' => 'Las acciones son obligatorias.',
        ];
    }
}

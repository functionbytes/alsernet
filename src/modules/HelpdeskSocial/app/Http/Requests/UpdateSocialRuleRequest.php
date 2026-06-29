<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('helpdesksocial.rules.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'platform' => 'nullable|string|in:facebook,instagram,whatsapp',
            'conditions' => 'sometimes|array',
            'actions' => 'sometimes|array',
            'priority' => 'sometimes|integer|min:0|max:9999',
            'is_active' => 'boolean',
            'stop_processing' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
        ];
    }
}

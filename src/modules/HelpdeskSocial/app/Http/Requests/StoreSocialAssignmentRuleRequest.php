<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocialAssignmentRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('helpdesksocial.rules.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'conditions' => 'required|array',
            'assignee_user_id' => 'nullable|exists:users,id',
            'assignment_strategy' => 'nullable|string|in:round_robin,workload,intent',
            'priority' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'conditions.required' => 'Las condiciones son obligatorias.',
        ];
    }
}

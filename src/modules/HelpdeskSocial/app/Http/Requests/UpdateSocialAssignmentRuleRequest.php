<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialAssignmentRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('helpdesksocial.rules.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'conditions' => 'sometimes|array',
            'assignee_user_id' => 'nullable|exists:users,id',
            'assignment_strategy' => 'nullable|string|in:round_robin,workload,intent',
            'priority' => 'sometimes|integer|min:0|max:9999',
            'is_active' => 'boolean',
        ];
    }
}

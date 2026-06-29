<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocialApprovalRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('helpdesksocial.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'social_comment_id' => 'required|exists:helpdesk_social_comments,id',
            'action_type' => 'required|string|in:reply,hide,escalate,delete',
            'payload' => 'nullable|array',
            'approver_user_id' => 'nullable|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'social_comment_id.required' => 'El comentario es obligatorio.',
            'action_type.required' => 'El tipo de acción es obligatorio.',
        ];
    }
}

<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RespondSocialApprovalRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('helpdesksocial.approver') ?? false;
    }

    public function rules(): array
    {
        return [
            'approver_note' => 'nullable|string|max:1000',
        ];
    }
}

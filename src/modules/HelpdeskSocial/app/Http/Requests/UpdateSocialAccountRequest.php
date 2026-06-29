<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('helpdesksocial.accounts.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'username' => 'nullable|string|max:255',
            'profile_url' => 'nullable|url|max:500',
            'page_access_token' => 'nullable|string',
            'user_access_token' => 'nullable|string',
            'is_active' => 'boolean',
            'comments_enabled' => 'boolean',
            'messages_enabled' => 'boolean',
            'auto_reply_enabled' => 'boolean',
            'settings' => 'nullable|array',
        ];
    }
}

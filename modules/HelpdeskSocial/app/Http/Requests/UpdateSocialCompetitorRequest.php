<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialCompetitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesksocial.analytics.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'social_account_id' => 'sometimes|exists:helpdesk_social_accounts,id',
            'name' => 'sometimes|string|max:255',
            'platform' => 'sometimes|string|in:facebook,instagram,tiktok,x,linkedin',
            'external_id' => 'sometimes|string|max:255',
            'username' => 'nullable|string|max:255',
            'profile_url' => 'nullable|url|max:500',
            'is_active' => 'boolean',
        ];
    }
}

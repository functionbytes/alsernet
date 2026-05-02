<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocialCompetitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('helpdesksocial.view-analytics') ?? false;
    }

    public function rules(): array
    {
        return [
            'social_account_id' => 'required|exists:helpdesk_social_accounts,id',
            'name' => 'required|string|max:255',
            'platform' => 'required|string|in:facebook,instagram,tiktok,x,linkedin',
            'external_id' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'profile_url' => 'nullable|url|max:500',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'social_account_id.required' => 'La cuenta social es obligatoria.',
            'name.required' => 'El nombre es obligatorio.',
            'platform.required' => 'La plataforma es obligatoria.',
            'external_id.required' => 'El ID externo es obligatorio.',
        ];
    }
}

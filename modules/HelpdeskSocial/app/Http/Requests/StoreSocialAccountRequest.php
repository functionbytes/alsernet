<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocialAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('helpdesksocial.accounts.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'platform' => 'required|string|in:facebook,instagram,whatsapp,tiktok,x,linkedin',
            'account_type' => 'nullable|string|in:page,profile,business',
            'external_id' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'profile_url' => 'nullable|url|max:500',
            'page_access_token' => 'nullable|string',
            'user_access_token' => 'nullable|string',
            'comments_enabled' => 'boolean',
            'messages_enabled' => 'boolean',
            'auto_reply_enabled' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'platform.required' => 'La plataforma es obligatoria.',
            'platform.in' => 'La plataforma seleccionada no es válida.',
            'external_id.required' => 'El ID externo es obligatorio.',
            'profile_url.url' => 'La URL del perfil no es válida.',
        ];
    }
}

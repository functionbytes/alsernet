<?php

namespace Modules\HelpdeskSocial\Http\Requests\Managers;

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
            'social_account_id' => ['nullable', 'exists:helpdesk_social_accounts,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'platform' => ['sometimes', 'string', 'in:facebook,instagram,tiktok,x,linkedin'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'profile_url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'platform.in' => 'La plataforma seleccionada no es válida.',
            'social_account_id.exists' => 'La cuenta social seleccionada no es válida.',
            'profile_url.url' => 'La URL del perfil no es válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'social_account_id' => 'cuenta relacionada',
            'name' => 'nombre',
            'platform' => 'plataforma',
            'external_id' => 'ID externo',
            'username' => 'usuario',
            'profile_url' => 'URL del perfil',
            'is_active' => 'estado',
        ];
    }
}

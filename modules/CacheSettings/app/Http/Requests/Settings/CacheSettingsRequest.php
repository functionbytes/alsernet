<?php

namespace Modules\CacheSettings\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class CacheSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_menu_enabled' => 'nullable|in:1',
            'frontend_menu_enabled' => 'nullable|in:1',
            'user_avatars_enabled' => 'nullable|in:1',
            'sitemap_enabled' => 'nullable|in:1',
            'sitemap_ttl' => 'required|integer|min:1|max:10080',
        ];
    }

    public function messages(): array
    {
        return [
            'sitemap_ttl.required' => 'El TTL del sitemap es obligatorio.',
            'sitemap_ttl.integer' => 'El TTL debe ser un numero entero.',
            'sitemap_ttl.min' => 'El TTL minimo es 1 minuto.',
            'sitemap_ttl.max' => 'El TTL maximo es 10080 minutos (7 dias).',
        ];
    }
}

<?php

namespace Modules\Cache\Http\Requests\Settings;

use App\Helpers\ModuleStatusHelper;
use Illuminate\Foundation\Http\FormRequest;

class CacheSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('Cache.settings.update') ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'admin_menu_enabled' => 'nullable|in:1',
            'frontend_menu_enabled' => 'nullable|in:1',
            'user_avatars_enabled' => 'nullable|in:1',
            'sitemap_enabled' => 'nullable|in:1',
            'sitemap_ttl' => 'required|integer|min:1|max:10080',
        ];

        // Only validate pages fields if Page module is active
        if (ModuleStatusHelper::isModuleEnabled('Page')) {
            $rules['pages_enabled'] = 'nullable|in:1';
            $rules['pages_ttl'] = 'required|integer|min:1|max:10080';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'sitemap_ttl.required' => 'El TTL del sitemap es obligatorio.',
            'sitemap_ttl.integer' => 'El TTL debe ser un numero entero.',
            'sitemap_ttl.min' => 'El TTL minimo es 1 minuto.',
            'sitemap_ttl.max' => 'El TTL maximo es 10080 minutos (7 dias).',
            'pages_ttl.required' => 'El TTL de páginas es obligatorio.',
            'pages_ttl.integer' => 'El TTL debe ser un numero entero.',
            'pages_ttl.min' => 'El TTL minimo es 1 minuto.',
            'pages_ttl.max' => 'El TTL maximo es 10080 minutos (7 dias).',
        ];
    }
}

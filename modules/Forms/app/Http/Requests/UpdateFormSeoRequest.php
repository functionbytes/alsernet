<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormSeoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.settings.manage');
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'url', 'max:500'],
            'og_type' => ['nullable', 'string', 'max:50'],
            'twitter_card' => ['nullable', 'in:summary,summary_large_image'],
            'twitter_title' => ['nullable', 'string', 'max:255'],
            'twitter_description' => ['nullable', 'string', 'max:500'],
            'twitter_image' => ['nullable', 'url', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'robots' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function messages(): array
    {
        return [
            'og_image.url' => 'La URL de la imagen OG no es válida.',
            'twitter_card.in' => 'El Twitter card debe ser summary o summary_large_image.',
            'canonical_url.url' => 'La URL canónica no es válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título SEO',
            'description' => 'meta description',
            'keywords' => 'keywords',
            'canonical_url' => 'URL canónica',
        ];
    }
}

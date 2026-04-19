<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeoTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('Seo.metas.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'model_type' => 'nullable|string|max:200',
            'title_pattern' => 'nullable|string|max:200',
            'description_pattern' => 'nullable|string|max:500',
            'og_type' => 'nullable|string|in:website,article,product,profile,book',
            'twitter_card' => 'nullable|string|in:summary,summary_large_image,app,player',
            'robots' => 'required|string|max:100',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0|max:255',
        ];
    }
}

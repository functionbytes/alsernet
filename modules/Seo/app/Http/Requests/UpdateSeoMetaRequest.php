<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSeoMetaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'title_b' => 'nullable|string|max:60',
            'description' => 'nullable|string|max:500',
            'description_b' => 'nullable|string|max:160',
            'ab_winner' => 'nullable|in:a,b',
            'keywords' => 'nullable|string|max:500',
            'target_keyword' => 'nullable|string|max:100',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|url|max:500',
            'og_type' => 'nullable|string|in:website,article,product,profile|max:50',
            'twitter_card' => 'nullable|string|in:summary,summary_large_image,app,player|max:50',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string|max:500',
            'twitter_image' => 'nullable|url|max:500',
            'canonical_url' => 'nullable|url|max:500',
            'robots' => 'nullable|string|in:index\,follow,noindex\,nofollow,noindex\,follow,index\,nofollow|max:100',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'og_title' => 'Open Graph title',
            'og_description' => 'Open Graph description',
            'og_image' => 'Open Graph image',
            'og_type' => 'Open Graph type',
            'twitter_card' => 'Twitter card type',
            'twitter_title' => 'Twitter title',
            'twitter_description' => 'Twitter description',
            'twitter_image' => 'Twitter image',
            'canonical_url' => 'canonical URL',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.max' => 'The SEO title should not exceed 255 characters.',
            'description.max' => 'The meta description should not exceed 500 characters.',
            'og_image.url' => 'The Open Graph image must be a valid URL.',
            'twitter_image.url' => 'The Twitter image must be a valid URL.',
            'canonical_url.url' => 'The canonical URL must be a valid URL.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Remove empty strings and convert to null
        $this->merge(
            collect($this->all())
                ->map(fn ($value) => $value === '' ? null : $value)
                ->all()
        );
    }
}

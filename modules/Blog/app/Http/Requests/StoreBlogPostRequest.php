<?php

namespace Modules\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Blog\Enums\PostStatus;

class StoreBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'status' => ['required', new Enum(PostStatus::class)],
            'is_featured' => ['boolean'],
            'image' => ['nullable', 'string', 'max:255'],
            'format_type' => ['nullable', 'string', 'max:30'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'seo_keywords' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
            'send_newsletter' => ['boolean'],
            'translations' => ['nullable', 'array'],
            'translations.*.title' => ['nullable', 'string', 'max:255'],
            'translations.*.slug' => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string', 'max:500'],
            'translations.*.content' => ['nullable', 'string'],
            'translations.*.seo_title' => ['nullable', 'string', 'max:255'],
            'translations.*.seo_description' => ['nullable', 'string'],
            'translations.*.seo_keywords' => ['nullable', 'string'],
        ];
    }
}

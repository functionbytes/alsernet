<?php

namespace Modules\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlogTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('blog.tag.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:400'],
            'status' => ['required', 'in:published,draft'],
            'translations' => ['nullable', 'array'],
            'translations.*.name' => ['nullable', 'string', 'max:120'],
            'translations.*.slug' => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string', 'max:400'],
        ];
    }
}

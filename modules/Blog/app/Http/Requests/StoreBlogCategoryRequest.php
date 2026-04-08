<?php

namespace Modules\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $flags = $this->input('flags', []);
        $this->merge([
            'is_featured' => in_array('is_featured', (array) $flags),
            'is_default' => in_array('is_default', (array) $flags),
        ]);
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
            'parent_id' => ['nullable', 'integer'],
            'status' => ['required', 'in:published,draft'],
            'icon' => ['nullable', 'string', 'max:60'],
            'order' => ['nullable', 'integer'],
            'is_featured' => ['boolean'],
            'is_default' => ['boolean'],
            'translations' => ['nullable', 'array'],
            'translations.*.name' => ['nullable', 'string', 'max:120'],
            'translations.*.slug' => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string', 'max:400'],
        ];
    }
}

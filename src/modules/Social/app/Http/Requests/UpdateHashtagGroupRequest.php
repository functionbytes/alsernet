<?php

namespace Modules\Social\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHashtagGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('hashtag'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'hashtags' => ['required', 'string'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-F]{6}$/i'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalize hashtags
        if ($this->has('hashtags') && is_string($this->hashtags)) {
            $tags = preg_split('/[\s,]+/', $this->hashtags);

            $tags = array_filter(
                array_map(fn ($tag) => trim($tag, '#'), $tags),
                fn ($tag) => ! empty($tag)
            );

            $this->merge([
                'hashtags' => implode(',', $tags),
            ]);
        }
    }
}

<?php

namespace Modules\Social\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Social\Models\HashtagGroup;

class StoreHashtagGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', HashtagGroup::class);
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'exists:chat_accounts,id'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'hashtags' => ['required', 'string'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-F]{6}$/i'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del grupo es obligatorio',
            'hashtags.required' => 'Debes ingresar al menos un hashtag',
            'color.regex' => 'El color debe ser un código hexadecimal válido',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalize hashtags: convert to comma-separated string
        if ($this->has('hashtags') && is_string($this->hashtags)) {
            // Split by spaces or commas
            $tags = preg_split('/[\s,]+/', $this->hashtags);

            // Filter and normalize
            $tags = array_filter(
                array_map(fn ($tag) => trim($tag, '#'), $tags),
                fn ($tag) => ! empty($tag)
            );

            $this->merge([
                'hashtags' => implode(',', $tags),
            ]);
        }

        // Add account_id
        $this->merge([
            'account_id' => auth()->user()->account_id,
        ]);
    }
}

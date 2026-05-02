<?php

namespace Modules\Social\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Social\Models\Template;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Template::class);
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'exists:chat_accounts,id'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'content' => ['required', 'string', 'max:5000'],
            'hashtags' => ['nullable', 'array'],
            'variables' => ['nullable', 'array'],
            'networks' => ['nullable', 'array'],
            'networks.*' => ['string', 'in:facebook,instagram,twitter,linkedin,pinterest,youtube,tiktok'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la plantilla es obligatorio',
            'content.required' => 'El contenido de la plantilla es obligatorio',
            'content.max' => 'El contenido no puede exceder 5000 caracteres',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Auto-detect variables from content
        if ($this->has('content')) {
            preg_match_all('/\{([^}]+)\}/', $this->content, $matches);
            $variables = array_unique($matches[1]);

            $this->merge([
                'variables' => $variables,
            ]);
        }

        // Convert hashtags string to array
        if ($this->has('hashtags') && is_string($this->hashtags)) {
            $hashtags = array_filter(
                array_map('trim', explode(' ', $this->hashtags)),
                fn ($tag) => ! empty($tag)
            );

            $this->merge([
                'hashtags' => $hashtags,
            ]);
        }

        // Add account_id
        $this->merge([
            'account_id' => auth()->user()->account_id,
        ]);
    }
}

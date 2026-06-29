<?php

namespace Modules\Social\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Social\Models\ShortUrl;

class StoreShortUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ShortUrl::class);
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'exists:chat_accounts,id'],
            'user_id' => ['required', 'exists:users,id'],
            'original_url' => ['required', 'url', 'max:2000'],
            'custom_alias' => ['nullable', 'string', 'max:20', 'regex:/^[a-zA-Z0-9-_]+$/', 'unique:social_short_urls,custom_alias'],
            'short_code' => ['required', 'string', 'max:20'],
            'utm_source' => ['nullable', 'string', 'max:100'],
            'utm_medium' => ['nullable', 'string', 'max:100'],
            'utm_campaign' => ['nullable', 'string', 'max:100'],
            'utm_content' => ['nullable', 'string', 'max:100'],
            'utm_parameters' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'original_url.required' => 'La URL original es obligatoria',
            'original_url.url' => 'Debe ser una URL válida',
            'custom_alias.regex' => 'El alias solo puede contener letras, números, guiones y guiones bajos',
            'custom_alias.unique' => 'Este alias ya está en uso',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Build UTM parameters array
        $utmParams = [];
        if ($this->utm_source) {
            $utmParams['utm_source'] = $this->utm_source;
        }
        if ($this->utm_medium) {
            $utmParams['utm_medium'] = $this->utm_medium;
        }
        if ($this->utm_campaign) {
            $utmParams['utm_campaign'] = $this->utm_campaign;
        }
        if ($this->utm_content) {
            $utmParams['utm_content'] = $this->utm_content;
        }

        // Generate short code if no custom alias
        $shortCode = $this->custom_alias ?? ShortUrl::generateShortCode();

        $this->merge([
            'account_id' => auth()->user()->account_id,
            'user_id' => auth()->id(),
            'short_code' => $shortCode,
            'utm_parameters' => ! empty($utmParams) ? $utmParams : null,
        ]);
    }
}

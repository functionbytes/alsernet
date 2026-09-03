<?php

namespace Modules\HelpdeskEmailActivity\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreBounceMailboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskemailactivity.settings.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['nullable', 'in:ssl,tls,'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'folder' => ['nullable', 'string', 'max:255'],
            'module_scope' => ['nullable', 'array'],
            'module_scope.*' => ['string', 'max:100'],
            'enabled' => ['nullable', 'boolean'],
        ];
    }
}

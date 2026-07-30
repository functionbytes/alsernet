<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesksocial.templates.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'platform' => 'nullable|string|in:facebook,instagram,whatsapp',
            'body' => 'required|string|max:5000',
            'variables' => 'nullable|array',
            'quick_replies' => 'nullable|array',
            'category' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }
}

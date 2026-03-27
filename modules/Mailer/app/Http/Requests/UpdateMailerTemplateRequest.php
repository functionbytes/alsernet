<?php

namespace Modules\Mailer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMailerTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'subject' => ['nullable', 'string', 'max:255'],
            'preheader' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'layout_id' => ['nullable', 'exists:mailer_layouts,id'],
            'is_enabled' => ['nullable', 'boolean'],
            'is_protected' => ['nullable', 'boolean'],
            'lang_id' => ['required', 'exists:langs,id'],
            'description' => ['nullable', 'string'],
            'translation_uid' => ['nullable', 'string'],
        ];
    }
}

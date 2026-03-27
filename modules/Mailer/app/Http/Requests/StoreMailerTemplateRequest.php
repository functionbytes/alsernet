<?php

namespace Modules\Mailer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMailerTemplateRequest extends FormRequest
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
            'key' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'preheader' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'layout_id' => ['nullable', 'exists:mailer_layouts,id'],
            'module' => ['required', 'string', 'in:core,documents,orders,notifications'],
            'lang_id' => ['required', 'exists:langs,id'],
            'is_protected' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}

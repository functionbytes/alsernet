<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:5000'],
            'is_internal' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.max' => 'El borrador no puede superar los 5000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'body' => 'borrador',
            'is_internal' => 'nota interna',
        ];
    }
}

<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToggleReactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'emoji' => ['required', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'emoji.required' => 'El emoji es obligatorio.',
            'emoji.max' => 'El emoji no puede superar los 10 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'emoji' => 'emoji',
        ];
    }
}

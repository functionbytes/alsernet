<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseAttentionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attention.close') ?? false;
    }

    public function rules(): array
    {
        return [
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.max' => 'El comentario no puede superar los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'comment' => 'comentario',
        ];
    }
}

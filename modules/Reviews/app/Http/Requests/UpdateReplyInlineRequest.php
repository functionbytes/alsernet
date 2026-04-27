<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReplyInlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.replies.create');
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'El texto de la respuesta es obligatorio.',
            'text.max' => 'La respuesta no puede superar los 4096 caracteres.',
        ];
    }
}

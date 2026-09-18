<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplySocialCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesksocial.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'body' => 'required|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'El texto de respuesta es obligatorio.',
            'body.max' => 'La respuesta no puede exceder 2000 caracteres.',
        ];
    }
}

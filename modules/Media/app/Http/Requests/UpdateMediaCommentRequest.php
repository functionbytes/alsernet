<?php

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.view');
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'El contenido del comentario es obligatorio.',
            'content.max' => 'El comentario no puede superar los 2000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'content' => 'contenido',
        ];
    }
}

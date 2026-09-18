<?php

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.view');
    }

    public function rules(): array
    {
        return [
            'commentable_id' => ['required', 'integer'],
            'commentable_type' => ['required', 'in:file,folder'],
            'parent_id' => ['nullable', 'integer', 'exists:media_comments,id'],
            'content' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'commentable_id.required' => 'El recurso es obligatorio.',
            'commentable_type.required' => 'El tipo de recurso es obligatorio.',
            'commentable_type.in' => 'El tipo de recurso debe ser archivo o carpeta.',
            'parent_id.exists' => 'El comentario padre no existe.',
            'content.required' => 'El contenido del comentario es obligatorio.',
            'content.max' => 'El comentario no puede superar los 2000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'commentable_id' => 'recurso',
            'commentable_type' => 'tipo de recurso',
            'parent_id' => 'comentario padre',
            'content' => 'contenido',
        ];
    }
}

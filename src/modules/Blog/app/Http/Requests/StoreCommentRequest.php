<?php

namespace Modules\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public endpoint: anonymous users are allowed to submit comments.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'max:100'],
            'author_email' => ['required', 'email', 'max:150'],
            'content' => ['required', 'string', 'min:10', 'max:2000'],
            'parent_id' => ['nullable', 'integer'],
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'author_name.required' => 'El nombre es obligatorio.',
            'author_name.max' => 'El nombre no puede superar los 100 caracteres.',
            'author_email.required' => 'El correo electrónico es obligatorio.',
            'author_email.email' => 'El correo electrónico no tiene un formato válido.',
            'author_email.max' => 'El correo electrónico no puede superar los 150 caracteres.',
            'content.required' => 'El comentario es obligatorio.',
            'content.min' => 'El comentario debe tener al menos 10 caracteres.',
            'content.max' => 'El comentario no puede superar los 2000 caracteres.',
            'parent_id.integer' => 'La referencia al comentario padre no es válida.',
        ];
    }
}

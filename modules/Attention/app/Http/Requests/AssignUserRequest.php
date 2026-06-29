<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attention.assign') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'El usuario es obligatorio.',
            'user_id.exists' => 'El usuario seleccionado no existe.',
            'comment.max' => 'El comentario no puede superar los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'usuario',
            'comment' => 'comentario',
        ];
    }
}

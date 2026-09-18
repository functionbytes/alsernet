<?php

namespace Modules\HelpdeskHelpcenter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoteArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vote' => ['required', 'integer', 'in:1,-1'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'vote.required' => 'El voto es obligatorio.',
            'vote.in' => 'El voto debe ser 1 (útil) o -1 (no útil).',
            'comment.max' => 'El comentario no puede superar los 1000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'vote' => 'voto',
            'comment' => 'comentario',
        ];
    }
}

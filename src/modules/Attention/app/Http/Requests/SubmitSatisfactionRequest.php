<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Public endpoint — no authentication required, no permission check.
class SubmitSatisfactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'La calificación es obligatoria.',
            'rating.integer' => 'La calificación debe ser un número entero.',
            'rating.min' => 'La calificación mínima es 1.',
            'rating.max' => 'La calificación máxima es 5.',
            'comment.max' => 'El comentario no puede superar los 1000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'rating' => 'calificación',
            'comment' => 'comentario',
        ];
    }
}

<?php

namespace Modules\HelpdeskTickets\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class RateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'rating_comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'La valoracion es obligatoria.',
            'rating.integer' => 'La valoracion debe ser un numero entero.',
            'rating.min' => 'La valoracion minima es 1.',
            'rating.max' => 'La valoracion maxima es 5.',
            'rating_comment.max' => 'El comentario no puede superar los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'rating' => 'valoracion',
            'rating_comment' => 'comentario',
        ];
    }
}

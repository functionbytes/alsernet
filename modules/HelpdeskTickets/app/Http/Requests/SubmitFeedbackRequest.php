<?php

namespace Modules\HelpdeskTickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reasonKeys = array_keys((array) config('helpdesktickets.csat_reasons', []));

        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'reason' => ['nullable', 'string', 'in:'.implode(',', $reasonKeys)],
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

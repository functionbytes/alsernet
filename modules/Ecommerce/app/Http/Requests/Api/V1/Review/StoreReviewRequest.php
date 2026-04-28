<?php

namespace Modules\Ecommerce\Http\Requests\Api\V1\Review;

use App\Http\Api\V1\BaseApiRequest;

class StoreReviewRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'star' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'star.required' => 'La calificación es obligatoria.',
            'star.min' => 'La calificación mínima es 1.',
            'star.max' => 'La calificación máxima es 5.',
            'comment.required' => 'El comentario es obligatorio.',
            'comment.min' => 'El comentario debe tener al menos 5 caracteres.',
        ];
    }
}

<?php

namespace Modules\Ecommerce\Http\Requests\Api\V1\Review;

use App\Http\Api\V1\BaseApiRequest;

class UpdateReviewRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $this->user() !== null
            && $review !== null
            && $review->customer_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'star' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'comment' => ['sometimes', 'string', 'min:5', 'max:2000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'star' => ['description' => 'Nueva calificación (opcional).', 'example' => 4],
            'comment' => ['description' => 'Texto actualizado de la reseña (opcional).', 'example' => 'Actualizo mi reseña, sigue siendo muy bueno.'],
        ];
    }
}

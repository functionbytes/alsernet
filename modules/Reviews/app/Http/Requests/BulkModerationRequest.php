<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Rules\ValidJsonArray;

class BulkModerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', Review::class);
    }

    public function rules(): array
    {
        return [
            'review_ids' => ['required', 'array', 'min:1'],
            'review_ids.*' => ['required', 'integer', 'exists:reviews,id'],
            'action' => ['required', 'string', Rule::in(['visible', 'hidden', 'featured', 'unfeatured', 'add_tags', 'remove_tags'])],
            'tags' => [
                'required_if:action,add_tags,remove_tags',
                new ValidJsonArray(maxItems: 50, maxStringLength: 50),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'review_ids.required' => 'Debe seleccionar al menos una reseña',
            'review_ids.min' => 'Debe seleccionar al menos una reseña',
            'review_ids.*.exists' => 'Una o más reseñas no existen',
            'action.required' => 'Debe especificar una acción',
            'action.in' => 'Acción no válida',
            'tags.required_if' => 'Debe especificar al menos una etiqueta',
        ];
    }
}

<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Reviews\Models\Review;

class ReviewFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Review::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_array($this->input('search'))) {
            $this->merge(['search' => $this->input('search.value') ?: null]);
        }
    }

    public function rules(): array
    {
        return [
            'location_id' => ['nullable', 'integer', 'exists:review_google_locations,id'],
            'rating' => ['nullable', 'string', Rule::in(['ONE', 'TWO', 'THREE', 'FOUR', 'FIVE'])],
            'has_comment' => ['nullable', 'boolean'],
            'has_reply' => ['nullable', 'boolean'],
            'is_visible' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date', 'before_or_equal:date_to'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'location_id.exists' => 'La ubicación seleccionada no existe',
            'rating.in' => 'La calificación debe ser ONE, TWO, THREE, FOUR o FIVE',
            'search.max' => 'La búsqueda no puede exceder 255 caracteres',
            'date_from.before_or_equal' => 'La fecha de inicio debe ser anterior o igual a la fecha de fin',
            'date_to.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio',
        ];
    }
}

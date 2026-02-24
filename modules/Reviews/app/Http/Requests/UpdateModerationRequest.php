<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateModerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.moderate');
    }

    public function rules(): array
    {
        return [
            'is_visible' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50'],
            'internal_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'tags.array' => 'Las etiquetas deben ser un array',
            'tags.*.max' => 'Cada etiqueta no puede exceder 50 caracteres',
            'internal_notes.max' => 'Las notas internas no pueden exceder 1000 caracteres',
        ];
    }
}

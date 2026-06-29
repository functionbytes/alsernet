<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Reviews\Rules\ValidJsonArray;

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
            'tags' => ['sometimes', new ValidJsonArray(maxItems: 50, maxStringLength: 50)],
            'internal_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'internal_notes.max' => 'Las notas internas no pueden exceder 1000 caracteres',
        ];
    }
}

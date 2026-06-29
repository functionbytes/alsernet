<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.reviews.view');
    }

    public function rules(): array
    {
        return [
            'translated_text' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'translated_text.required' => 'El texto traducido es obligatorio.',
            'translated_text.max' => 'El texto traducido no puede superar los 5000 caracteres.',
        ];
    }
}

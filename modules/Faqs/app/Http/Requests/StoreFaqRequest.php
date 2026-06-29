<?php

namespace Modules\Faqs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Faqs\Enums\FaqStatus;

class StoreFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('faqs.create');
    }

    public function rules(): array
    {
        return [
            'translations' => ['required', 'array'],
            'translations.*.locale' => ['required', 'string', 'max:10'],
            'translations.*.question' => ['required', 'string', 'max:1000'],
            'translations.*.answer' => ['required', 'string'],
            'category_id' => ['required', 'exists:faq_categories,id'],
            'status' => ['required', 'string', Rule::in(array_map(fn ($s) => $s->value, FaqStatus::cases()))],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'translations.required' => 'Debe proporcionar al menos una traducción.',
            'translations.*.question.required' => 'La pregunta es obligatoria para cada idioma.',
            'translations.*.answer.required' => 'La respuesta es obligatoria para cada idioma.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
        ];
    }
}

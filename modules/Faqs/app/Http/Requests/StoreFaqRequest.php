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
            'question' => ['required', 'string', 'max:1000'],
            'answer' => ['required', 'string'],
            'category_id' => ['required', 'exists:faq_categories,id'],
            'status' => ['required', 'string', Rule::in(array_map(fn ($s) => $s->value, FaqStatus::cases()))],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'question.required' => 'La pregunta es obligatoria.',
            'answer.required' => 'La respuesta es obligatoria.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
        ];
    }
}

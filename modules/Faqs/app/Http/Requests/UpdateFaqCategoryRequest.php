<?php

namespace Modules\Faqs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Faqs\Enums\FaqStatus;

class UpdateFaqCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('faqs.categories.update');
    }

    public function rules(): array
    {
        return [
            'translations' => ['required', 'array'],
            'translations.*.locale' => ['required', 'string', 'max:10'],
            'translations.*.name' => ['required', 'string', 'max:120'],
            'translations.*.description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string', Rule::in(array_map(fn ($s) => $s->value, FaqStatus::cases()))],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'translations.required' => 'Debe proporcionar al menos una traducción.',
            'translations.*.name.required' => 'El nombre es obligatorio para cada idioma.',
        ];
    }
}

<?php

namespace Modules\Faqs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Faqs\Enums\FaqStatus;

class StoreFaqCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('faqs.categories.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string', Rule::in(array_map(fn ($s) => $s->value, FaqStatus::cases()))],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoría es obligatorio.',
        ];
    }
}

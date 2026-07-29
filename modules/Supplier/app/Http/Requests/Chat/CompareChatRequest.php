<?php

namespace Modules\Supplier\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class CompareChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.view.products');
    }

    public function rules(): array
    {
        return [
            'message' => ['nullable', 'string'],
            'prompt_uid' => ['nullable', 'string'],
            'model_a' => ['required', 'string', 'in:gpt-4o-mini,gpt-4o,claude-3-5-haiku,claude-3-5-sonnet'],
            'model_b' => ['required', 'string', 'in:gpt-4o-mini,gpt-4o,claude-3-5-haiku,claude-3-5-sonnet'],
            'web_search' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'model_a.required' => 'El modelo A es obligatorio.',
            'model_a.in' => 'El modelo A seleccionado no es valido.',
            'model_b.required' => 'El modelo B es obligatorio.',
            'model_b.in' => 'El modelo B seleccionado no es valido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'message' => 'mensaje',
            'prompt_uid' => 'prompt',
            'model_a' => 'modelo A',
            'model_b' => 'modelo B',
            'web_search' => 'busqueda web',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'web_search' => $this->boolean('web_search', false),
        ]);
    }
}

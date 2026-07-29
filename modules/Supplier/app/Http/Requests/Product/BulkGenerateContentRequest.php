<?php

namespace Modules\Supplier\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class BulkGenerateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.configure');
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'exists:supplier_products,id'],
            'prompt_uid' => ['required', 'string', 'exists:supplier_prompts,uid'],
            'model' => ['required', 'string', 'in:gpt-4o,gpt-4o-mini,claude-3-5-haiku,claude-3-5-sonnet'],
            'web_search' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Debe seleccionar al menos un producto.',
            'ids.min' => 'Debe seleccionar al menos un producto.',
            'ids.max' => 'No puede seleccionar mas de 100 productos.',
            'ids.*.exists' => 'Uno de los productos seleccionados no existe.',
            'prompt_uid.required' => 'El prompt es obligatorio.',
            'prompt_uid.exists' => 'El prompt seleccionado no existe.',
            'model.required' => 'El modelo es obligatorio.',
            'model.in' => 'El modelo seleccionado no es valido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ids' => 'productos',
            'ids.*' => 'producto',
            'prompt_uid' => 'prompt',
            'model' => 'modelo',
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

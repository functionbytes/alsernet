<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavedFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.view');
    }

    public function rules(): array
    {
        $filterId = $this->route('saved_filter')?->id;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:review_saved_filters,name,'.$filterId.',id,user_id,'.$this->user()->id,
            ],
            'filters_json' => [$isUpdate ? 'sometimes' : 'required', 'json'],
            'is_default' => ['sometimes', 'boolean'],
            'is_shared' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del filtro es obligatorio',
            'name.max' => 'El nombre no puede exceder 100 caracteres',
            'name.unique' => 'Ya tienes un filtro guardado con este nombre',
            'filters_json.required' => 'Los datos del filtro son obligatorios',
            'filters_json.json' => 'El formato de los filtros no es válido',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Convert filters array to JSON if it's an array
        if ($this->has('filters_json') && is_array($this->filters_json)) {
            $this->merge([
                'filters_json' => json_encode($this->filters_json),
            ]);
        }
    }
}

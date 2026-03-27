<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttentionCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $categoryId = $this->route('attention_category') ?? $this->route('id');

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('attention_categories', 'name')->ignore($categoryId)],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'exists:attention_categories,id'],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede exceder los 150 caracteres.',
            'name.unique' => 'Ya existe una categoría con este nombre.',

            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max' => 'La descripción no puede exceder los 500 caracteres.',

            'parent_id.exists' => 'La categoría padre seleccionada no existe.',

            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'parent_id' => 'categoría padre',
            'is_active' => 'estado activo',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $categoryId = $this->route('attention_category') ?? $this->route('id');

            // Validar que no se pueda asignar a sí misma como padre
            if ($this->input('parent_id') == $categoryId) {
                $validator->errors()->add('parent_id', 'Una categoría no puede ser su propia categoría padre.');
            }
        });
    }
}

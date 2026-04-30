<?php

namespace Modules\Chat\Http\Requests\CustomerSegment;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerSegmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'filter_criteria' => 'nullable|array',
            'filter_criteria.*.field' => 'required_with:filter_criteria|string',
            'filter_criteria.*.operator' => 'required_with:filter_criteria|string',
            'filter_criteria.*.value' => 'nullable',
            'is_dynamic' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del segmento es obligatorio.',
            'name.string' => 'El nombre del segmento debe ser texto.',
            'name.max' => 'El nombre del segmento no puede exceder 255 caracteres.',
            'description.string' => 'La descripción debe ser texto.',
            'filter_criteria.array' => 'Los criterios de filtro deben ser un array.',
            'filter_criteria.*.field.required_with' => 'El campo es obligatorio cuando se especifican criterios de filtro.',
            'filter_criteria.*.field.string' => 'El campo debe ser texto.',
            'filter_criteria.*.operator.required_with' => 'El operador es obligatorio cuando se especifican criterios de filtro.',
            'filter_criteria.*.operator.string' => 'El operador debe ser texto.',
            'is_dynamic.boolean' => 'El valor de dinámico debe ser booleano.',
        ];
    }
}

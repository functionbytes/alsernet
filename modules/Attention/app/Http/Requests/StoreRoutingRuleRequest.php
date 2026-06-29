<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoutingRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'priority' => ['required', 'integer', 'min:1', 'max:100'],
            'condition_type_id' => ['nullable', 'integer', 'exists:attention_types,id'],
            'condition_category_id' => ['nullable', 'integer', 'exists:attention_categories,id'],
            'condition_sede_id' => ['nullable', 'integer', 'exists:attention_sedes,id'],
            'assign_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'assign_to_department_id' => ['nullable', 'integer', 'exists:attention_departments,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la regla es obligatorio.',
            'priority.required' => 'La prioridad es obligatoria.',
            'priority.min' => 'La prioridad mínima es 1.',
            'priority.max' => 'La prioridad máxima es 100.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}

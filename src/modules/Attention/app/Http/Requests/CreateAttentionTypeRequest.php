<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAttentionTypeRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:100', 'unique:attention_types,name'],
            'code' => ['required', 'string', 'max:10', 'unique:attention_types,code'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
            'sla_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
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
            'name.max' => 'El nombre no puede exceder los 100 caracteres.',
            'name.unique' => 'Ya existe un tipo de atención con este nombre.',

            'code.required' => 'El código es obligatorio.',
            'code.string' => 'El código debe ser una cadena de texto.',
            'code.max' => 'El código no puede exceder los 10 caracteres.',
            'code.unique' => 'Ya existe un tipo de atención con este código.',

            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max' => 'La descripción no puede exceder los 500 caracteres.',

            'color.string' => 'El color debe ser una cadena de texto.',
            'color.max' => 'El color no puede exceder los 7 caracteres.',
            'color.regex' => 'El color debe ser un código hexadecimal válido (ej: #FF5733).',

            'icon.string' => 'El ícono debe ser una cadena de texto.',
            'icon.max' => 'El ícono no puede exceder los 50 caracteres.',

            'sla_days.integer' => 'Los días de SLA deben ser un número entero.',
            'sla_days.min' => 'Los días de SLA deben ser al menos 1.',
            'sla_days.max' => 'Los días de SLA no pueden exceder 365.',

            'order.integer' => 'El orden debe ser un número entero.',

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
            'code' => 'código',
            'description' => 'descripción',
            'color' => 'color',
            'icon' => 'ícono',
            'sla_days' => 'días de SLA',
            'order' => 'orden',
            'is_active' => 'estado activo',
        ];
    }
}

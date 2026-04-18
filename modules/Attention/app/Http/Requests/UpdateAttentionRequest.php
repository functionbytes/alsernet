<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request validation for updating an existing peticiones (settings only)
 */
class UpdateAttentionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Basic authorization check - fine-grained permissions should be handled in controller
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Type and category
            'type_id' => [
                'sometimes',
                'integer',
                Rule::exists('attention_types', 'id')->where('is_active', true),
            ],
            'category_id' => [
                'sometimes',
                'integer',
                Rule::exists('attention_categories', 'id')->where('is_active', true),
            ],
            'sede_id' => [
                'nullable',
                'integer',
                Rule::exists('attention_sedes', 'id')->where('is_active', true),
            ],

            // Subject and description
            'subject' => [
                'sometimes',
                'string',
                'max:255',
                'min:10',
            ],
            'description' => [
                'sometimes',
                'string',
                'min:20',
                'max:5000',
            ],

            // Customer information
            'is_anonymous' => [
                'sometimes',
                'boolean',
            ],
            'customer_firstname' => [
                'nullable',
                'string',
                'max:100',
                'min:2',
            ],
            'customer_lastname' => [
                'nullable',
                'string',
                'max:100',
                'min:2',
            ],
            'customer_email' => [
                'nullable',
                'email',
                'max:150',
            ],
            'customer_cellphone' => [
                'nullable',
                'string',
                'regex:/^[0-9]{10}$/',
            ],
            'customer_dni' => [
                'nullable',
                'string',
                'max:20',
            ],
            'customer_address' => [
                'nullable',
                'string',
                'max:255',
            ],

            // Assignment (basic update, detailed assignment has separate endpoints)
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('attention_departments', 'id')->where('is_active', true),
            ],
            'assigned_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('is_active', true),
            ],

            // Response preference
            'response_type' => [
                'nullable',
                'string',
                'in:email,presencial,correo_fisico,telefono,no_requiere',
            ],
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'type_id.exists' => 'El tipo de solicitud seleccionado no es válido',
            'category_id.exists' => 'La categoría seleccionada no es válida',
            'sede_id.exists' => 'La sede seleccionada no es válida',

            'subject.min' => 'El asunto debe tener al menos :min caracteres',
            'subject.max' => 'El asunto no puede exceder :max caracteres',
            'description.min' => 'La descripción debe tener al menos :min caracteres',
            'description.max' => 'La descripción no puede exceder :max caracteres',

            'customer_firstname.min' => 'El nombre debe tener al menos :min caracteres',
            'customer_lastname.min' => 'El apellido debe tener al menos :min caracteres',
            'customer_email.email' => 'Debe proporcionar un correo electrónico válido',
            'customer_cellphone.regex' => 'El número de celular debe tener 10 dígitos',

            'department_id.exists' => 'El departamento seleccionado no es válido',
            'assigned_user_id.exists' => 'El usuario asignado no es válido',

            'response_type.in' => 'El tipo de respuesta seleccionado no es válido',
        ];
    }

    /**
     * Get custom attributes for error messages
     */
    public function attributes(): array
    {
        return [
            'type_id' => 'tipo de solicitud',
            'category_id' => 'categoría',
            'sede_id' => 'sede',
            'subject' => 'asunto',
            'description' => 'descripción',
            'customer_firstname' => 'nombre',
            'customer_lastname' => 'apellido',
            'customer_email' => 'correo electrónico',
            'customer_cellphone' => 'celular',
            'customer_dni' => 'documento de identidad',
            'customer_address' => 'dirección',
            'department_id' => 'departamento',
            'assigned_user_id' => 'usuario asignado',
            'response_type' => 'tipo de respuesta',
        ];
    }
}

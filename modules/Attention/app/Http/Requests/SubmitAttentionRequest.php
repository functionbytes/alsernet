<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request validation for submitting a new peticiones (public endpoint)
 */
class SubmitAttentionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint - no authentication required
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Required fields
            'type_id' => [
                'required',
                'integer',
                Rule::exists('attention_types', 'id')->where('is_active', true),
            ],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('attention_categories', 'id')->where('is_active', true),
            ],
            'subject' => [
                'required',
                'string',
                'max:255',
                'min:10',
            ],
            'description' => [
                'required',
                'string',
                'min:20',
                'max:5000',
            ],

            // Optional sede
            'sede_id' => [
                'nullable',
                'integer',
                Rule::exists('attention_sedes', 'id')->where('is_active', true),
            ],

            // Conditional citizen information (required if not anonymous)
            'is_anonymous' => [
                'sometimes',
                'boolean',
            ],
            'customer_firstname' => [
                'required_if:is_anonymous,false',
                'nullable',
                'string',
                'max:100',
                'min:2',
            ],
            'customer_lastname' => [
                'required_if:is_anonymous,false',
                'nullable',
                'string',
                'max:100',
                'min:2',
            ],
            'customer_email' => [
                'required_if:is_anonymous,false',
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

            // Files validation
            'attachments' => [
                'nullable',
                'array',
                'max:5', // Maximum 5 files
            ],
            'attachments.*' => [
                'file',
                'max:10240', // 10MB per file
                'mimes:pdf,doc,docx,jpg,jpeg,png,gif,txt,zip,rar',
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
            // Type and Category
            'type_id.required' => 'Debe seleccionar el tipo de solicitud (Petición, Queja, Reclamo, etc.)',
            'type_id.exists' => 'El tipo de solicitud seleccionado no es válido',
            'category_id.required' => 'Debe seleccionar una categoría para su solicitud',
            'category_id.exists' => 'La categoría seleccionada no es válida',

            // Subject and Description
            'subject.required' => 'El asunto es obligatorio',
            'subject.min' => 'El asunto debe tener al menos :min caracteres',
            'subject.max' => 'El asunto no puede exceder :max caracteres',
            'description.required' => 'La descripción es obligatoria',
            'description.min' => 'La descripción debe tener al menos :min caracteres',
            'description.max' => 'La descripción no puede exceder :max caracteres',

            // Customer information
            'customer_firstname.required_if' => 'El nombre es obligatorio para solicitudes no anónimas',
            'customer_firstname.min' => 'El nombre debe tener al menos :min caracteres',
            'customer_lastname.required_if' => 'El apellido es obligatorio para solicitudes no anónimas',
            'customer_lastname.min' => 'El apellido debe tener al menos :min caracteres',
            'customer_email.required_if' => 'El correo electrónico es obligatorio para solicitudes no anónimas',
            'customer_email.email' => 'Debe proporcionar un correo electrónico válido',
            'customer_cellphone.regex' => 'El número de celular debe tener 10 dígitos',
            'customer_dni.max' => 'El documento de identidad no puede exceder :max caracteres',
            'customer_address.max' => 'La dirección no puede exceder :max caracteres',

            // Files
            'attachments.max' => 'No puede adjuntar más de :max archivos',
            'attachments.*.file' => 'Cada adjunto debe ser un archivo válido',
            'attachments.*.max' => 'Cada archivo no puede exceder 10MB',
            'attachments.*.mimes' => 'Solo se permiten archivos PDF, Word, imágenes, texto y archivos comprimidos',

            // Sede
            'sede_id.exists' => 'La sede seleccionada no es válida',

            // Response type
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
            'attachments' => 'archivos adjuntos',
            'response_type' => 'tipo de respuesta',
        ];
    }
}

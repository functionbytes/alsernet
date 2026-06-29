<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attention\Enums\ResponseType;

/**
 * Request validation for resolving a peticiones
 */
class ResolveAttentionRequest extends FormRequest
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
        $responseTypes = collect(ResponseType::cases())->pluck('value')->toArray();

        return [
            'resolution' => [
                'required',
                'string',
                'min:50',
                'max:5000',
            ],
            'response_type' => [
                'required',
                'string',
                'in:'.implode(',', $responseTypes),
            ],
            'send_notification' => [
                'sometimes',
                'boolean',
            ],
            'attachments' => [
                'nullable',
                'array',
                'max:5', // Maximum 5 files in resolution
            ],
            'attachments.*' => [
                'file',
                'max:10240', // 10MB per file
                'mimes:pdf,doc,docx,jpg,jpeg,png,gif,txt,zip,rar',
            ],
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'resolution.required' => 'Debe proporcionar una respuesta para resolver el peticiones',
            'resolution.min' => 'La respuesta debe tener al menos :min caracteres para ser considerada válida',
            'resolution.max' => 'La respuesta no puede exceder :max caracteres',

            'response_type.required' => 'Debe seleccionar el tipo de respuesta',
            'response_type.in' => 'El tipo de respuesta seleccionado no es válido',

            'attachments.max' => 'No puede adjuntar más de :max archivos en la respuesta',
            'attachments.*.file' => 'Cada adjunto debe ser un archivo válido',
            'attachments.*.max' => 'Cada archivo no puede exceder 10MB',
            'attachments.*.mimes' => 'Solo se permiten archivos PDF, Word, imágenes, texto y archivos comprimidos',
        ];
    }

    /**
     * Get custom attributes for error messages
     */
    public function attributes(): array
    {
        return [
            'resolution' => 'respuesta',
            'response_type' => 'tipo de respuesta',
            'attachments' => 'archivos adjuntos',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Default send_notification to true if not provided
        if (! $this->has('send_notification')) {
            $this->merge([
                'send_notification' => true,
            ]);
        }
    }
}

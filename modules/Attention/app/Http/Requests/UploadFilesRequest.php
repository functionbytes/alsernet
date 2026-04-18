<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for uploading files to a peticiones
 */
class UploadFilesRequest extends FormRequest
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
            'files' => [
                'required',
                'array',
                'min:1',
                'max:10', // Maximum 10 files per upload
            ],
            'files.*' => [
                'required',
                'file',
                'max:10240', // 10MB per file
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,bmp,txt,csv,zip,rar,7z',
            ],
            'collection' => [
                'sometimes',
                'string',
                'in:attachments,resolutions,internal', // Collection name for media library
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'files.required' => 'Debe seleccionar al menos un archivo para subir',
            'files.array' => 'Los archivos deben enviarse en formato de arreglo',
            'files.min' => 'Debe seleccionar al menos un archivo',
            'files.max' => 'No puede subir más de :max archivos a la vez',

            'files.*.required' => 'Cada elemento debe ser un archivo válido',
            'files.*.file' => 'Cada elemento debe ser un archivo válido',
            'files.*.max' => 'Cada archivo no puede exceder 10MB de tamaño',
            'files.*.mimes' => 'Solo se permiten los siguientes tipos de archivo: PDF, Word, Excel, PowerPoint, imágenes, texto y archivos comprimidos',

            'collection.in' => 'El tipo de colección no es válido',
            'description.max' => 'La descripción no puede exceder :max caracteres',
        ];
    }

    /**
     * Get custom attributes for error messages
     */
    public function attributes(): array
    {
        return [
            'files' => 'archivos',
            'files.*' => 'archivo',
            'collection' => 'tipo de documento',
            'description' => 'descripción',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Default collection to 'attachments' if not provided
        if (! $this->has('collection')) {
            $this->merge([
                'collection' => 'attachments',
            ]);
        }
    }
}

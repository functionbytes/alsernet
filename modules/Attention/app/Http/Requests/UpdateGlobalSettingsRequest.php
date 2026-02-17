<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGlobalSettingsRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'radicado_prefix' => 'required|string|max:10|alpha_num',
            'auto_assign' => 'boolean',
            'require_attachments' => 'boolean',
            'enable_anonymous' => 'boolean',
            'max_attachments' => 'required|integer|min:1|max:20',
            'attachment_max_size' => 'required|integer|min:1024|max:102400',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'radicado_prefix' => 'prefijo de radicado',
            'auto_assign' => 'asignacion automatica',
            'require_attachments' => 'requerir archivos adjuntos',
            'enable_anonymous' => 'permitir casos anonimos',
            'max_attachments' => 'maximo de archivos adjuntos',
            'attachment_max_size' => 'tamano maximo de archivo',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'radicado_prefix.required' => 'El :attribute es obligatorio.',
            'radicado_prefix.max' => 'El :attribute no puede exceder :max caracteres.',
            'radicado_prefix.alpha_num' => 'El :attribute solo puede contener letras y numeros.',
            'max_attachments.min' => 'El :attribute debe ser al menos :min.',
            'max_attachments.max' => 'El :attribute no puede exceder :max.',
            'attachment_max_size.min' => 'El :attribute debe ser al menos :min KB.',
            'attachment_max_size.max' => 'El :attribute no puede exceder :max KB.',
        ];
    }
}

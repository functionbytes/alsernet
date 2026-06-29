<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for sending custom emails
 */
class SendCustomEmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'recipient' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:10000',
            'template_id' => 'nullable|exists:mailer_templates,id',
            'send_copy' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'recipient.required' => 'El destinatario es obligatorio',
            'recipient.email' => 'El destinatario debe ser un email válido',
            'subject.required' => 'El asunto es obligatorio',
            'subject.max' => 'El asunto no puede exceder 255 caracteres',
            'message.required' => 'El mensaje es obligatorio',
            'message.max' => 'El mensaje no puede exceder 10000 caracteres',
            'template_id.exists' => 'La plantilla seleccionada no existe',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'recipient' => 'destinatario',
            'subject' => 'asunto',
            'message' => 'mensaje',
            'template_id' => 'plantilla',
            'send_copy' => 'enviar copia',
        ];
    }
}

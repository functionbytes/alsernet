<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendCustomEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient' => [
                'nullable',
                'email',
                'max:255'
            ],
            'subject' => [
                'required',
                'string',
                'max:255'
            ],
            'content' => [
                'required',
                'string',
                'max:10000'
            ],
            'template' => [
                'nullable',
                'string',
                'in:custom,update,request_info,shipping_reminder'
            ],
            'attachments' => [
                'nullable',
                'array',
                'max:3' // Máximo 3 archivos
            ],
            'attachments.*' => [
                'file',
                'max:5120', // 5MB
                'mimes:pdf,jpg,jpeg,png,doc,docx'
            ],
            'send_copy' => [
                'nullable',
                'boolean'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'El asunto es obligatorio',
            'content.required' => 'El contenido del email es obligatorio',
            'content.max' => 'El contenido no puede exceder 10,000 caracteres',
            'attachments.*.max' => 'Cada archivo no puede exceder 5MB',
            'attachments.*.mimes' => 'Solo se permiten archivos PDF, imágenes, Word'
        ];
    }
}

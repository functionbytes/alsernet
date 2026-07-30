<?php

namespace Modules\HelpdeskTickets\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class ReplyTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:5000'],
            'attachments.*' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,zip'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'El mensaje es obligatorio.',
            'message.max' => 'El mensaje no puede superar los 5000 caracteres.',
            'attachments.*.file' => 'El archivo adjunto debe ser un archivo valido.',
            'attachments.*.max' => 'El archivo adjunto no puede superar los 5 MB.',
            'attachments.*.mimes' => 'El formato del archivo adjunto no es valido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'message' => 'mensaje',
            'attachments.*' => 'archivo adjunto',
        ];
    }
}

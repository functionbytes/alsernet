<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:10000'],
            'is_internal' => ['sometimes', 'boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,zip,rar'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'El mensaje es obligatorio.',
            'body.min' => 'El mensaje debe tener al menos 1 caracter.',
            'body.max' => 'El mensaje no puede superar los 10000 caracteres.',
            'attachments.*.file' => 'El archivo adjunto debe ser un archivo valido.',
            'attachments.*.max' => 'El archivo adjunto no puede superar los 10 MB.',
            'attachments.*.mimes' => 'El formato del archivo adjunto no es valido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'body' => 'mensaje',
            'is_internal' => 'mensaje interno',
            'attachments' => 'archivos adjuntos',
            'attachments.*' => 'archivo adjunto',
        ];
    }
}

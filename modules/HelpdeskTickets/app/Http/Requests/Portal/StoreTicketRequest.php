<?php

namespace Modules\HelpdeskTickets\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Rules\ValidMimeMagicBytes;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer'],
            'priority' => ['nullable', 'string'],
            // ValidMimeMagicBytes además de mimes: comprueba la firma binaria
            // real del fichero, no solo lo que declara la extensión. El alta
            // interna (StoreTicketRequest) ya lo hacía; el portal — que es la
            // entrada abierta a cualquiera con un enlace mágico — se quedaba
            // en la comprobación más débil de las dos.
            'attachments.*' => [
                'nullable',
                'file',
                'max:5120',
                'mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,zip',
                new ValidMimeMagicBytes(config('helpdesk.attachments.allowed_mime_types', [])),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'description.required' => 'La descripcion es obligatoria.',
            'description.max' => 'La descripcion no puede superar los 5000 caracteres.',
            'attachments.*.file' => 'El archivo adjunto debe ser un archivo valido.',
            'attachments.*.max' => 'El archivo adjunto no puede superar los 5 MB.',
            'attachments.*.mimes' => 'El formato del archivo adjunto no es valido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'subject' => 'asunto',
            'description' => 'descripcion',
            'category_id' => 'categoria',
            'priority' => 'prioridad',
            'attachments.*' => 'archivo adjunto',
        ];
    }
}

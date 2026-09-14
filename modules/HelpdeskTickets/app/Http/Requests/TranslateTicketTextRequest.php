<?php

namespace Modules\HelpdeskTickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TranslateTicketTextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:10000'],
            'target_lang' => ['required', 'string', 'max:10'],
            'source_lang' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'El texto a traducir es obligatorio.',
            'text.max' => 'El texto no puede superar los 10000 caracteres.',
            'target_lang.required' => 'El idioma de destino es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'text' => 'texto',
            'target_lang' => 'idioma de destino',
            'source_lang' => 'idioma de origen',
        ];
    }
}

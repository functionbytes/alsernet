<?php

namespace Modules\HelpdeskTickets\Http\Requests\Agents;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:65535'],
            'is_internal' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'El mensaje es obligatorio.',
            'body.max' => 'El mensaje no puede superar los 65535 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'body' => 'mensaje',
            'is_internal' => 'mensaje interno',
        ];
    }
}

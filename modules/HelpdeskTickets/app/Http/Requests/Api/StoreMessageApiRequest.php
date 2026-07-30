<?php

namespace Modules\HelpdeskTickets\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.tickets.update');
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
            'is_internal' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'El cuerpo del mensaje es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'body' => 'mensaje',
            'is_internal' => 'nota interna',
        ];
    }
}

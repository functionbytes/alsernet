<?php

namespace Modules\Helpdesk\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam body string required Message content. Example: Gracias por contactarnos.
 * @bodyParam is_internal boolean Whether the note is internal (not visible to customer). Example: false
 */
class StoreMessageApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.conversations.reply');
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:50000'],
            'is_internal' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'El cuerpo del mensaje es obligatorio.',
            'body.max' => 'El mensaje no puede superar los 50,000 caracteres.',
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

<?php

namespace Modules\HelpdeskLivechat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPreChatFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // La propiedad de la conversación se verifica con el token del widget
            // (X-Conversation-Token) en el controller, no con customer_id/email del
            // body (eran adivinables → IDOR), que por eso ya no se aceptan aquí.
            'conversation_id' => ['required', 'integer'],
            'data' => ['required', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'conversation_id.required' => 'La conversación es obligatoria.',
            'data.required' => 'Los datos del formulario son obligatorios.',
        ];
    }

    public function attributes(): array
    {
        return [
            'conversation_id' => 'conversación',
            'data' => 'datos del formulario',
        ];
    }
}

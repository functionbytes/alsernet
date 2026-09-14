<?php

namespace Modules\Helpdesk\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam customer_id integer required Customer ID. Example: 12
 * @bodyParam subject string required Subject/title of the conversation. Example: Problema con mi pedido
 * @bodyParam priority string Priority. Enum: low,normal,high,urgent. Example: normal
 * @bodyParam message string required Opening message body. Example: Hola, necesito ayuda.
 * @bodyParam assignee_id integer Assign to agent user ID. Example: 3
 */
class StoreConversationApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.conversations.create');
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_customers,id'],
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'message' => ['required', 'string', 'max:10000'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'El cliente es obligatorio.',
            'customer_id.exists' => 'El cliente no existe.',
            'subject.required' => 'El asunto es obligatorio.',
            'message.required' => 'El mensaje inicial es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
            'subject' => 'asunto',
            'priority' => 'prioridad',
            'message' => 'mensaje',
            'assignee_id' => 'agente asignado',
        ];
    }
}

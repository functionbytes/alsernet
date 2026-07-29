<?php

namespace Modules\Helpdesk\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Models\Customer;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'exists:helpdesk.helpdesk_customers,id',
                // 'exists' solo confirma que el cliente existe en la tabla, no que
                // el agente pueda tocarlo — sin este chequeo un agente restringido
                // a su bandeja podia mandar el customer_id de un cliente ajeno y
                // el controller creaba la conversacion (y disparaba el envio real)
                // igual. Mismo scope que ya usa la busqueda de contactos del wizard.
                function (string $attribute, mixed $value, Closure $fail) {
                    if (! Customer::query()->forAgent($this->user())->whereKey($value)->exists()) {
                        $fail('No tienes acceso a este cliente.');
                    }
                },
            ],
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            // Nullable: cuando no se manda, el controller usa ConversationStatus::getDefault().
            'status_id' => ['nullable', 'exists:helpdesk.helpdesk_conversation_statuses,id'],
            // Nullable: el formulario tradicional de "crear conversación" no lo manda
            // (siempre queda 'web'); el modal "Nueva conversación" del inbox sí lo envía.
            'channel' => ['nullable', 'string', Rule::in(['whatsapp', 'facebook', 'instagram', 'web', 'email', 'sms'])],
            // Mensaje inicial opcional del modal "Nueva conversación" — se envía por el
            // canal real de la conversación (WhatsApp/Messenger/Instagram) tras crearla.
            'first_message' => ['nullable', 'string', 'max:4096'],
            // Checkbox "Asignar conversación al agente actual" del modal "Nueva conversación".
            'assign_self' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'El cliente es obligatorio.',
            'customer_id.exists' => 'El cliente seleccionado no existe.',
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'priority.required' => 'La prioridad es obligatoria.',
            'priority.in' => 'La prioridad debe ser: baja, normal, alta o urgente.',
            'status_id.exists' => 'El estado seleccionado no existe.',
            'channel.in' => 'El canal seleccionado no es válido.',
            'first_message.max' => 'El primer mensaje no puede superar los 4096 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
            'subject' => 'asunto',
            'priority' => 'prioridad',
            'status_id' => 'estado',
            'channel' => 'canal',
            'first_message' => 'primer mensaje',
            'assign_self' => 'asignar a mí',
        ];
    }
}

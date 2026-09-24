<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Descarga del PDF de un documento del pedido. purpose distingue en el log de
 * actividad si el agente se lo bajó a su equipo o lo mandó por el chat (el
 * envío por el chat lo hace el propio inbox subiendo este mismo PDF).
 */
class OrderdocsDownloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskprestashop.orders.documents') ?? false;
    }

    public function rules(): array
    {
        return [
            'purpose' => ['nullable', 'string', 'in:download,chat'],
            // Para enviarlo por el chat la conversación es obligatoria: el
            // controlador comprueba que sea del mismo cliente.
            'conversation_id' => ['nullable', 'required_if:purpose,chat', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'purpose.in' => 'Uso del documento no válido.',
            'conversation_id.required_if' => 'Abre la conversación del cliente para enviarle el documento.',
        ];
    }
}

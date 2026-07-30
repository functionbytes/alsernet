<?php

namespace Modules\Helpdesk\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class StartSimulatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public endpoint — access is gated by the HELPDESK_SIMULATOR_PUBLIC flag
        // in the controller constructor, not by user permissions.
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'channel' => ['required', 'string', 'in:whatsapp,facebook,instagram,web'],
            'name' => ['nullable', 'string', 'max:120'],
            'identifier' => ['nullable', 'string', 'max:191'],
            'message' => ['required', 'string', 'max:2000'],
            // Reloj simulado opcional para probar nodos dependientes del tiempo (horario de atención).
            'simulated_datetime' => ['nullable', 'date'],
            // Datos de cliente / vínculos e-commerce (opcionales)
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:191'],
            'prestashop_id' => ['nullable', 'integer', 'min:1'],
            'gestion_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'channel.required' => 'Selecciona un canal.',
            'channel.in' => 'El canal seleccionado no es válido.',
            'message.required' => 'Escribe un mensaje para iniciar la conversación.',
            'message.max' => 'El mensaje no puede superar los 2000 caracteres.',
            'name.max' => 'El nombre no puede superar los 120 caracteres.',
            'identifier.max' => 'El identificador no puede superar los 191 caracteres.',
            'email.email' => 'El email no es válido.',
            'prestashop_id.integer' => 'El ID de PrestaShop debe ser numérico.',
            'gestion_id.integer' => 'El ID de gestión debe ser numérico.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'channel' => 'canal',
            'name' => 'nombre',
            'identifier' => 'identificador',
            'message' => 'mensaje',
            'phone' => 'celular',
            'email' => 'email',
            'prestashop_id' => 'ID PrestaShop',
            'gestion_id' => 'ID gestión',
        ];
    }
}

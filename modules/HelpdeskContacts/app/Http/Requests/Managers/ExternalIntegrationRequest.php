<?php

namespace Modules\HelpdeskContacts\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Compartida entre "crear contacto desde resultado externo" (sin customer
 * aún) y "vincular resultado externo a un contacto existente" — mismos dos
 * campos en ambos casos, la diferencia la resuelve el controlador según la
 * ruta ({customer} presente o no).
 */
class ExternalIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contacts.update');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'platform' => ['required', 'string', 'max:50'],
            'external_id' => ['required', 'string', 'max:255'],
            // Solo lo usa externalCreate() (viene de la ficha completa/preview);
            // externalLink() lo ignora — el contacto destino ya tiene su propio teléfono.
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'platform.required' => 'La plataforma es obligatoria.',
            'external_id.required' => 'El identificador externo es obligatorio.',
        ];
    }
}

<?php

namespace Modules\HelpdeskContacts\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class ExternalSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contacts.view');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            // Nullable: sin plataforma se busca en todas las vinculables a la
            // vez (ver ContactsController::externalSearch) — ya no obliga a
            // elegir ERP o PrestaShop antes de buscar.
            'platform' => ['nullable', 'string', 'max:50'],
            'query' => ['required', 'string', 'min:2', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'query.required' => 'Escribe al menos 2 caracteres para buscar.',
            'query.min' => 'Escribe al menos 2 caracteres para buscar.',
            'type.required' => 'El tipo de búsqueda es obligatorio.',
        ];
    }
}

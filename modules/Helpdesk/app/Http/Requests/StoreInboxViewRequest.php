<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Quick "save current filters as a view" action from the inbox sidebar.
 * Distinct from StoreConversationViewRequest, which backs the fuller
 * views CRUD under panel/settings/helpdesk.
 */
class StoreInboxViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'filters' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la vista es obligatorio.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'filters.array' => 'Los filtros no tienen un formato válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'filters' => 'filtros',
        ];
    }
}

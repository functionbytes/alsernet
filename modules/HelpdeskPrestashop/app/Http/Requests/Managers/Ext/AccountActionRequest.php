<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Acciones de cuenta sin datos propios (restablecer contraseña): solo la
 * conversación desde la que se lanza, para la auditoría.
 */
class AccountActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'conversation_id' => ['nullable', 'integer'],
        ];
    }
}

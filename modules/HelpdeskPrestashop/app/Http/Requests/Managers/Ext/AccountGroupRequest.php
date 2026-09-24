<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;

class AccountGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'group_id' => ['required', 'integer', 'min:1'],
            'version' => ['required', 'string', 'date_format:Y-m-d H:i:s'],
            // El JS solo lo manda tras el paso de confirmación.
            'confirmed' => ['accepted'],
            'conversation_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirmed.accepted' => 'Confirma el cambio de grupo antes de guardarlo.',
        ];
    }
}

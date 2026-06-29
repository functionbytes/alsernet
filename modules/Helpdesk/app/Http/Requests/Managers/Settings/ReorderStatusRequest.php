<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class ReorderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.statuses.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:helpdesk_conversation_statuses,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Los identificadores son obligatorios.',
            'ids.array' => 'Los identificadores deben ser un arreglo.',
            'ids.*.exists' => 'Uno o más estados seleccionados no existen.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ids' => 'identificadores',
        ];
    }
}

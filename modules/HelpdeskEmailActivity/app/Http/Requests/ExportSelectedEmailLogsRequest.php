<?php

namespace Modules\HelpdeskEmailActivity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportSelectedEmailLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskemailactivity.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'uids' => ['required', 'array', 'min:1', 'max:1000'],
            'uids.*' => ['string', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'uids.required' => 'Selecciona al menos un registro.',
            'uids.min' => 'Selecciona al menos un registro.',
            'uids.max' => 'No puedes exportar más de 1000 registros a la vez.',
            'uids.*.uuid' => 'Identificador inválido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'uids' => 'registros seleccionados',
        ];
    }
}

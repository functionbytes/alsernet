<?php

namespace Modules\HelpdeskEmailLog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkResendEmailLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskemaillog.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'uids' => ['required', 'array', 'min:1', 'max:200'],
            'uids.*' => ['string', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'uids.required' => 'Selecciona al menos un registro.',
            'uids.min' => 'Selecciona al menos un registro.',
            'uids.max' => 'No puedes reenviar más de 200 emails a la vez.',
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

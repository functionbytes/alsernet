<?php

namespace Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:mark_read,delete'],
            'ids' => ['required', 'array'],
            'ids.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'La acción es obligatoria.',
            'action.in' => 'La acción debe ser: marcar como leída o eliminar.',
            'ids.required' => 'Debe seleccionar al menos una notificación.',
            'ids.array' => 'Las notificaciones deben ser un arreglo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'action' => 'acción',
            'ids' => 'notificaciones',
        ];
    }
}

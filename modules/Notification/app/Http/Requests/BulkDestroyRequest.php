<?php

namespace Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDestroyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Debe seleccionar al menos una notificación.',
            'ids.array' => 'Las notificaciones deben ser un arreglo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ids' => 'notificaciones',
        ];
    }
}

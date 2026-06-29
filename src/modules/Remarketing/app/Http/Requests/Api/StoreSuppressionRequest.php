<?php

namespace Modules\Remarketing\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSuppressionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.suppressions.create');
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', 'exists:remarketing_stores,id'],
            'email' => ['required', 'email', 'max:255'],
            'reason' => ['required', 'string', 'in:unsubscribe,bounce,complaint,manual'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_id.required' => 'La tienda es obligatoria.',
            'store_id.exists' => 'La tienda seleccionada no existe.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email no tiene un formato válido.',
            'reason.required' => 'El motivo es obligatorio.',
            'reason.in' => 'El motivo debe ser unsubscribe, bounce, complaint o manual.',
        ];
    }

    public function attributes(): array
    {
        return [
            'store_id' => 'tienda',
            'email' => 'correo electrónico',
            'reason' => 'motivo',
            'notes' => 'notas',
        ];
    }
}

<?php

namespace Modules\Supplier\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class RejectContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.content.manage');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'El motivo del rechazo es obligatorio.',
            'reason.max' => 'El motivo no puede superar los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'reason' => 'motivo',
            'notes' => 'notas',
        ];
    }
}

<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class AddOrderNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskprestashop.orders.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'Escribe la nota.',
            'note.max' => 'La nota no puede superar los 2000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'note' => 'nota',
        ];
    }
}

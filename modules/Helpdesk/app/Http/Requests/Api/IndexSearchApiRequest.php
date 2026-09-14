<?php

namespace Modules\Helpdesk\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class IndexSearchApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.tickets.view');
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'type' => ['nullable', 'in:tickets,conversations,customers,all'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'El término de búsqueda es obligatorio.',
            'q.min' => 'La búsqueda debe tener al menos 2 caracteres.',
            'q.max' => 'La búsqueda no puede superar los 100 caracteres.',
            'type.in' => 'El tipo debe ser: tickets, conversations, customers o all.',
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => 'búsqueda',
            'type' => 'tipo',
        ];
    }
}

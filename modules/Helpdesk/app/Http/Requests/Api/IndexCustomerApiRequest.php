<?php

namespace Modules\Helpdesk\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class IndexCustomerApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.customers.view');
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'min:2', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.min' => 'La búsqueda debe tener al menos 2 caracteres.',
            'per_page.integer' => 'El parámetro per_page debe ser un número entero.',
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => 'búsqueda',
            'per_page' => 'resultados por página',
        ];
    }
}

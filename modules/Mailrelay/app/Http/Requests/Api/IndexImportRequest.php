<?php

namespace Modules\Mailrelay\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class IndexImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public API endpoint — authentication handled at route middleware level
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:pending,processing,completed,failed,cancelled'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'sort_by' => ['nullable', 'string', 'in:filename,status,total_emails,created_at,started_at,completed_at'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'El estado seleccionado no es válido.',
            'search.max' => 'La búsqueda no puede superar los 255 caracteres.',
            'per_page.min' => 'El número de resultados por página debe ser al menos 1.',
            'per_page.max' => 'El número de resultados por página no puede superar 100.',
            'sort_by.in' => 'El campo de ordenación no es válido.',
            'sort_order.in' => 'El orden seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'estado',
            'search' => 'búsqueda',
            'per_page' => 'resultados por página',
            'page' => 'página',
            'sort_by' => 'ordenar por',
            'sort_order' => 'orden',
        ];
    }
}

<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportAttentionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attention.view-reports') ?? false;
    }

    public function rules(): array
    {
        return [
            'format' => ['required', 'string', 'in:excel,pdf,csv'],
            'filters' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'format.required' => 'El formato de exportación es obligatorio.',
            'format.in' => 'El formato debe ser excel, pdf o csv.',
            'filters.array' => 'Los filtros deben ser un arreglo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'format' => 'formato',
            'filters' => 'filtros',
        ];
    }
}

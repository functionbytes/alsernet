<?php

namespace Modules\Supplier\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RunWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.view.automation') ?? false;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'source_id' => 'nullable|integer|exists:supplier_sources,id',
            'payload' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.exists' => 'El proveedor seleccionado no existe.',
            'source_id.exists' => 'La fuente seleccionada no existe.',
        ];
    }
}

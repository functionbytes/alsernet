<?php

namespace Modules\Supplier\Http\Requests\Import;

use Illuminate\Foundation\Http\FormRequest;

class SyncSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.import');
    }

    public function rules(): array
    {
        return [
            'erp_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'erp_id.required' => 'El ID del ERP es obligatorio.',
            'erp_id.integer' => 'El ID del ERP debe ser un numero entero.',
            'erp_id.min' => 'El ID del ERP debe ser mayor a 0.',
        ];
    }

    public function attributes(): array
    {
        return [
            'erp_id' => 'ID del ERP',
        ];
    }
}

<?php

namespace Modules\Supplier\Http\Requests\Sync;

use Illuminate\Foundation\Http\FormRequest;

class SyncProviderProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.sync.status');
    }

    public function rules(): array
    {
        return [
            'erp_provider_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'erp_provider_id.integer' => 'El ID del proveedor ERP debe ser un numero entero.',
        ];
    }

    public function attributes(): array
    {
        return [
            'erp_provider_id' => 'ID del proveedor ERP',
        ];
    }
}

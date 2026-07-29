<?php

namespace Modules\Supplier\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class TriggerSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.sync.config') ?? false;
    }

    public function rules(): array
    {
        return [
            'sync_type' => 'required|string|in:product,model,provider,category,price,all',
            'suppliers' => 'nullable|array',
            'suppliers.*' => 'string|exists:suppliers,uid',
        ];
    }

    public function messages(): array
    {
        return [
            'sync_type.required' => 'El tipo de sincronización es obligatorio.',
            'sync_type.in' => 'El tipo de sincronización seleccionado no es válido.',
            'suppliers.*.exists' => 'Uno de los proveedores seleccionados no existe.',
        ];
    }
}

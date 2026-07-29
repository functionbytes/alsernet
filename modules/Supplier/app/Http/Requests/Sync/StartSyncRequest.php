<?php

namespace Modules\Supplier\Http\Requests\Sync;

use Illuminate\Foundation\Http\FormRequest;

class StartSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.sync.status');
    }

    public function rules(): array
    {
        return [
            'sync_type' => ['required', 'string', 'in:product,category,price,provider,all'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'triggered_by' => ['required', 'string', 'in:manual,scheduled,webhook,api'],
            'filter_criteria' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'sync_type.required' => 'El tipo de sincronizacion es obligatorio.',
            'sync_type.in' => 'El tipo de sincronizacion seleccionado no es valido.',
            'supplier_id.exists' => 'El proveedor seleccionado no existe.',
            'triggered_by.required' => 'El origen del disparo es obligatorio.',
            'triggered_by.in' => 'El origen del disparo no es valido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'sync_type' => 'tipo de sincronizacion',
            'supplier_id' => 'proveedor',
            'triggered_by' => 'origen del disparo',
            'filter_criteria' => 'criterios de filtro',
        ];
    }
}

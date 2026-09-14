<?php

namespace Modules\Supplier\Http\Requests\SyncFailure;

use Illuminate\Foundation\Http\FormRequest;

class BulkRetrySyncFailureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.sync.failures');
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:50'],
            'ids.*' => ['required', 'integer', 'exists:supplier_sync_failures,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Debe seleccionar al menos un fallo.',
            'ids.array' => 'Los identificadores deben ser un arreglo.',
            'ids.*.exists' => 'Uno de los fallos seleccionados no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ids' => 'identificadores',
            'ids.*' => 'identificador',
        ];
    }
}

<?php

namespace Modules\Remarketing\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.segments.create');
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', 'exists:remarketing_stores,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'string', 'in:static,dynamic'],
            'conditions' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_id.required' => 'La tienda es obligatoria.',
            'store_id.exists' => 'La tienda seleccionada no existe.',
            'name.required' => 'El nombre del segmento es obligatorio.',
            'type.required' => 'El tipo de segmento es obligatorio.',
            'type.in' => 'El tipo debe ser static o dynamic.',
        ];
    }

    public function attributes(): array
    {
        return [
            'store_id' => 'tienda',
            'name' => 'nombre',
            'description' => 'descripción',
            'type' => 'tipo',
            'conditions' => 'condiciones',
        ];
    }
}

<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;

class OrdereditReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permisos (reorder / reorder_link) y acceso al cliente
        // (CustomerPolicy) los comprueba el controlador.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $max = (int) config('helpdeskprestashop.ext.orderedit.max_lines', 60);

        return [
            'lines' => ['required', 'array', 'min:1', 'max:'.$max],
            'lines.*.order_detail_id' => ['required', 'integer', 'min:1', 'distinct'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'with_link' => ['sometimes', 'boolean'],
            'conversation_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'Marca al menos una línea del pedido.',
            'lines.min' => 'Marca al menos una línea del pedido.',
            'lines.*.quantity.min' => 'La cantidad mínima es 1.',
            'lines.*.quantity.max' => 'La cantidad máxima por línea es 999.',
        ];
    }
}

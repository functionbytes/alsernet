<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;

class CatalogStockAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permiso y acceso a ESTE cliente (CustomerPolicy) los comprueba el
        // controlador, igual que el resto de acciones de escritura.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_attribute_id' => ['nullable', 'integer', 'min:0'],
            'conversation_id' => ['nullable', 'integer'],
        ];
    }
}

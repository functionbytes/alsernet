<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;

class CartpayEmptyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El acceso a ESTE cliente (CustomerPolicy) lo comprueba el controlador.
        return $this->user()?->can('helpdeskprestashop.cartpay.empty') ?? false;
    }

    public function rules(): array
    {
        return [
            'conversation_id' => ['nullable', 'integer'],
        ];
    }
}

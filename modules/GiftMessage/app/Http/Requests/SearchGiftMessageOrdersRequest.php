<?php

namespace Modules\GiftMessage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchGiftMessageOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('giftmessage.view');
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Debes indicar al menos un numero de gestion o de pedido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ids' => 'numeros de gestion o de pedido',
        ];
    }
}

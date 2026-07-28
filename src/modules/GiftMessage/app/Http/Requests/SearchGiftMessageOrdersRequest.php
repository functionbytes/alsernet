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
            'gestion_ids' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'gestion_ids.required' => 'Debes indicar al menos un numero de gestion.',
        ];
    }

    public function attributes(): array
    {
        return [
            'gestion_ids' => 'numeros de gestion',
        ];
    }
}

<?php

namespace Modules\GiftMessage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveGiftMessagePositionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('giftmessage.update');
    }

    public function rules(): array
    {
        return [
            'scope' => ['required', 'string', Rule::in(['envelope', 'card'])],
            't1_x' => ['required', 'numeric', 'min:0', 'max:100'],
            't1_y' => ['required', 'numeric', 'min:0', 'max:100'],
            't2_x' => ['required', 'numeric', 'min:0', 'max:100'],
            't2_y' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'scope.required' => 'El ambito es obligatorio.',
            'scope.in' => 'El ambito debe ser sobre o tarjeta.',
            't1_x.required' => 'La posicion X del texto 1 es obligatoria.',
            't1_y.required' => 'La posicion Y del texto 1 es obligatoria.',
            't2_x.required' => 'La posicion X del texto 2 es obligatoria.',
            't2_y.required' => 'La posicion Y del texto 2 es obligatoria.',
        ];
    }

    public function attributes(): array
    {
        return [
            'scope' => 'ambito',
            't1_x' => 'posicion X texto 1',
            't1_y' => 'posicion Y texto 1',
            't2_x' => 'posicion X texto 2',
            't2_y' => 'posicion Y texto 2',
        ];
    }
}

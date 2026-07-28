<?php

namespace Modules\GiftMessage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveGiftMessageFontsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('giftmessage.update');
    }

    public function rules(): array
    {
        $fontRule = ['nullable', 'string', 'in:helvetica,times,courier,dejavusans,dejavuserif'];
        $sizeRule = ['nullable', 'integer', 'min:6', 'max:72'];

        return [
            'env_t1_font' => $fontRule,
            'env_t1_size' => $sizeRule,
            'env_t2_font' => $fontRule,
            'env_t2_size' => $sizeRule,
            'card_t1_font' => $fontRule,
            'card_t1_size' => $sizeRule,
            'card_t2_font' => $fontRule,
            'card_t2_size' => $sizeRule,
        ];
    }

    public function messages(): array
    {
        return [
            '*.in' => 'La fuente seleccionada no es valida.',
            '*.min' => 'El tamano minimo es 6.',
            '*.max' => 'El tamano maximo es 72.',
        ];
    }

    public function attributes(): array
    {
        return [
            'env_t1_font' => 'fuente T1 sobre',
            'env_t1_size' => 'tamano T1 sobre',
            'env_t2_font' => 'fuente T2 sobre',
            'env_t2_size' => 'tamano T2 sobre',
            'card_t1_font' => 'fuente T1 tarjeta',
            'card_t1_size' => 'tamano T1 tarjeta',
            'card_t2_font' => 'fuente T2 tarjeta',
            'card_t2_size' => 'tamano T2 tarjeta',
        ];
    }
}

<?php

namespace Modules\GiftMessage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\GiftMessage\Services\GiftMessageFontService;

class SaveGiftMessageFontsRequest extends FormRequest
{
    private const SLOTS = [
        'env_t1' => 'T1 sobre',
        'env_t2' => 'T2 sobre',
        'card_t1' => 'T1 tarjeta',
        'card_t2' => 'T2 tarjeta',
    ];

    public function authorize(): bool
    {
        return $this->user()->can('giftmessage.update');
    }

    public function rules(): array
    {
        $rules = [];

        foreach (array_keys(self::SLOTS) as $slot) {
            $rules[$slot.'_font'] = ['nullable', 'string', Rule::in(app(GiftMessageFontService::class)->allowedFamilies())];
            $rules[$slot.'_size'] = ['nullable', 'integer', 'min:6', 'max:72'];
            $rules[$slot.'_color'] = ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];
            $rules[$slot.'_opacity'] = ['nullable', 'integer', 'min:0', 'max:100'];
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            '*.in' => 'La fuente seleccionada no es valida.',
            '*.regex' => 'El color debe estar en formato hexadecimal (#RRGGBB).',
        ];

        foreach (array_keys(self::SLOTS) as $slot) {
            $messages[$slot.'_size.min'] = 'El tamano minimo es 6.';
            $messages[$slot.'_size.max'] = 'El tamano maximo es 72.';
            $messages[$slot.'_opacity.min'] = 'La opacidad minima es 0.';
            $messages[$slot.'_opacity.max'] = 'La opacidad maxima es 100.';
        }

        return $messages;
    }

    public function attributes(): array
    {
        $attributes = [];

        foreach (self::SLOTS as $slot => $label) {
            $attributes[$slot.'_font'] = 'fuente '.$label;
            $attributes[$slot.'_size'] = 'tamano '.$label;
            $attributes[$slot.'_color'] = 'color '.$label;
            $attributes[$slot.'_opacity'] = 'opacidad '.$label;
        }

        return $attributes;
    }
}

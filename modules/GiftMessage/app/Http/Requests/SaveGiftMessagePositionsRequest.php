<?php

namespace Modules\GiftMessage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveGiftMessagePositionsRequest extends FormRequest
{
    private const SLOTS = ['t1' => 'texto 1', 't2' => 'texto 2'];

    public function authorize(): bool
    {
        return $this->user()->can('giftmessage.update');
    }

    public function rules(): array
    {
        $rules = ['scope' => ['required', 'string', Rule::in(['envelope', 'card'])]];

        foreach (array_keys(self::SLOTS) as $slot) {
            $rules[$slot.'_x'] = ['required', 'numeric', 'min:0', 'max:100'];
            $rules[$slot.'_y'] = ['required', 'numeric', 'min:0', 'max:100'];
            // El minimo evita cajas de 0 en las que el texto no cabria nunca.
            $rules[$slot.'_w'] = ['required', 'numeric', 'min:1', 'max:100'];
            $rules[$slot.'_h'] = ['required', 'numeric', 'min:1', 'max:100'];
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'scope.required' => 'El ambito es obligatorio.',
            'scope.in' => 'El ambito debe ser sobre o tarjeta.',
        ];

        foreach (self::SLOTS as $slot => $label) {
            $messages[$slot.'_x.required'] = "La posicion X del {$label} es obligatoria.";
            $messages[$slot.'_y.required'] = "La posicion Y del {$label} es obligatoria.";
            $messages[$slot.'_w.required'] = "El ancho del {$label} es obligatorio.";
            $messages[$slot.'_h.required'] = "El alto del {$label} es obligatorio.";
            $messages[$slot.'_w.min'] = "El ancho del {$label} no puede ser cero.";
            $messages[$slot.'_h.min'] = "El alto del {$label} no puede ser cero.";
        }

        return $messages;
    }

    public function attributes(): array
    {
        $attributes = ['scope' => 'ambito'];

        foreach (self::SLOTS as $slot => $label) {
            $attributes[$slot.'_x'] = "posicion X {$label}";
            $attributes[$slot.'_y'] = "posicion Y {$label}";
            $attributes[$slot.'_w'] = "ancho {$label}";
            $attributes[$slot.'_h'] = "alto {$label}";
        }

        return $attributes;
    }
}

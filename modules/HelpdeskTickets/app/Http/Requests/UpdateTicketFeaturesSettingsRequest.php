<?php

namespace Modules\HelpdeskTickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HelpdeskTickets\Support\TicketFeatures;

class UpdateTicketFeaturesSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    /**
     * Reglas y atributos se generan desde TicketFeatures::SECTIONS en vez
     * de repetir las ~27 keys a mano: un slug nuevo en el catálogo entra
     * aquí solo, sin tocar este archivo (mismo criterio que ya usan las
     * DEFAULTS/BOOL_KEYS espejadas de FeaturesSettingsController, pero sin
     * el riesgo de que las tres listas — DEFAULTS, BOOL_KEYS, rules() —
     * se desincronicen entre sí).
     */
    public function rules(): array
    {
        return array_fill_keys(TicketFeatures::keys(), ['sometimes', 'boolean']);
    }

    public function messages(): array
    {
        return [
            '*.boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ];
    }

    public function attributes(): array
    {
        $attributes = [];

        foreach (TicketFeatures::SECTIONS as $section) {
            foreach ($section['items'] as $slug => $label) {
                $attributes["feature_{$slug}_enabled"] = mb_strtolower($label);
            }
        }

        return $attributes;
    }
}

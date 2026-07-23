<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HelpdeskTickets\Models\TicketCategoryField;

class UpdateTicketCategoryFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $field = $this->route('field');

        return [
            'type' => ['required', 'string', Rule::in(TicketCategoryField::TYPES)],
            'label' => ['required', 'string', 'max:255'],
            'key' => [
                'nullable', 'string', 'alpha_dash', 'max:100',
                Rule::unique('helpdesk.helpdesk_ticket_category_fields', 'key')
                    ->where('ticket_category_id', $category->id)
                    ->ignore($field->id),
            ],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:500'],
            'default_value' => ['nullable', 'string', 'max:255'],
            'is_required' => ['boolean'],
            'is_visible' => ['boolean'],
            'width' => ['required', 'string', Rule::in(['full', 'half', 'third'])],
            'options' => ['nullable', 'array'],
            'options.*.label' => ['required_with:options', 'string', 'max:255'],
            'options.*.value' => ['required_with:options', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'El tipo de campo es obligatorio.',
            'type.in' => 'El tipo de campo no es válido.',
            'label.required' => 'La etiqueta es obligatoria.',
            'label.max' => 'La etiqueta no puede superar los 255 caracteres.',
            'key.alpha_dash' => 'La clave solo puede contener letras, números, guiones y guiones bajos.',
            'key.max' => 'La clave no puede superar los 100 caracteres.',
            'key.unique' => 'Ya existe un campo con esta clave en la categoría.',
            'placeholder.max' => 'El marcador no puede superar los 255 caracteres.',
            'help_text.max' => 'El texto de ayuda no puede superar los 500 caracteres.',
            'width.required' => 'El ancho es obligatorio.',
            'width.in' => 'El ancho debe ser: completo, mitad o tercio.',
            'options.array' => 'Las opciones deben ser un arreglo.',
            'options.*.label.required_with' => 'Cada opción debe tener una etiqueta.',
            'options.*.value.required_with' => 'Cada opción debe tener un valor.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'tipo',
            'label' => 'etiqueta',
            'key' => 'clave',
            'placeholder' => 'marcador de posición',
            'help_text' => 'texto de ayuda',
            'default_value' => 'valor por defecto',
            'is_required' => 'obligatorio',
            'is_visible' => 'visible',
            'width' => 'ancho',
            'options' => 'opciones',
        ];
    }
}

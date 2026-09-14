<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Helpdesk\Models\Macro;

class UpdateMacroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.macros.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'language' => ['nullable', 'string', 'in:es,en,fr,de,pt,it'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.type' => ['required', 'string', 'in:'.implode(',', array_keys(Macro::ACTION_TYPES))],
            'actions.*.value' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['required', 'string', 'in:global,personal'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'language.in' => 'El idioma no es valido.',
            'actions.required' => 'Debes agregar al menos una accion.',
            'actions.min' => 'Debes agregar al menos una accion.',
            'actions.*.type.required' => 'Cada accion debe tener un tipo.',
            'actions.*.type.in' => 'El tipo de accion no es valido.',
            'visibility.required' => 'La visibilidad es obligatoria.',
            'visibility.in' => 'La visibilidad debe ser global o personal.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripcion',
            'language' => 'idioma',
            'actions' => 'acciones',
            'visibility' => 'visibilidad',
            'is_active' => 'estado',
        ];
    }
}

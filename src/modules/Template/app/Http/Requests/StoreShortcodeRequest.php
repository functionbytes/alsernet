<?php

namespace Modules\Template\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShortcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('shortcode.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:100', 'unique:shortcodes,key', 'regex:/^[a-z0-9\-]+$/'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'in:estructura,contenido,media,formularios,tema,otros'],
            'shortcode_template' => ['nullable', 'string', 'max:500'],
            'render_template' => ['nullable', 'string', 'max:50000'],
            'css_code' => ['nullable', 'string', 'max:50000'],
            'js_code' => ['nullable', 'string', 'max:50000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'config_fields' => ['nullable', 'json'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => 'La clave es obligatoria.',
            'key.unique' => 'Esta clave ya está en uso.',
            'key.regex' => 'La clave solo puede contener minúsculas, números y guiones.',
            'name.required' => 'El nombre es obligatorio.',
            'category.in' => 'La categoría seleccionada no es válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'key' => 'clave',
            'name' => 'nombre',
            'description' => 'descripción',
            'icon' => 'icono',
            'category' => 'categoría',
            'shortcode_template' => 'plantilla del shortcode',
            'render_template' => 'plantilla de render',
            'css_code' => 'código CSS',
            'js_code' => 'código JavaScript',
            'sort_order' => 'orden',
            'config_fields' => 'campos de configuración',
        ];
    }
}

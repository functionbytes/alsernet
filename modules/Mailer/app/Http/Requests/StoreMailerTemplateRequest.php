<?php

namespace Modules\Mailer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMailerTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.templates.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:100', 'regex:/^[A-Z][A-Z0-9_]+$/', 'unique:mailer_templates,key'],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'preheader' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'layout_id' => ['nullable', 'exists:mailer_layouts,id'],
            'module' => ['required', 'string', 'in:core,documents,orders,notifications,backup'],
            'lang_id' => ['required', 'exists:langs,id'],
            'is_protected' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'key.required' => 'La clave es obligatoria.',
            'key.regex' => 'La clave debe empezar por mayuscula y solo contener letras, numeros y guion bajo.',
            'key.unique' => 'Ya existe una plantilla con esta clave.',
            'key.max' => 'La clave no puede superar los 100 caracteres.',
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'preheader.max' => 'El preheader no puede superar los 255 caracteres.',
            'content.required' => 'El contenido es obligatorio.',
            'layout_id.exists' => 'El layout seleccionado no existe.',
            'module.required' => 'El modulo es obligatorio.',
            'module.in' => 'El modulo seleccionado no es valido.',
            'lang_id.required' => 'El idioma es obligatorio.',
            'lang_id.exists' => 'El idioma seleccionado no existe.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'key' => 'clave',
            'name' => 'nombre',
            'subject' => 'asunto',
            'preheader' => 'preheader',
            'content' => 'contenido',
            'layout_id' => 'layout',
            'module' => 'modulo',
            'lang_id' => 'idioma',
            'is_protected' => 'protegida',
            'description' => 'descripcion',
        ];
    }
}

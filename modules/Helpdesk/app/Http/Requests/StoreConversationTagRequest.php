<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tags.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:helpdesk.helpdesk_conversation_tags,slug', 'regex:/^[a-z0-9_-]+$/'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números, guiones y guiones bajos.',
            'slug.unique' => 'Ya existe un tag con este slug.',
            'color.regex' => 'El color debe ser un código hexadecimal válido (#RRGGBB).',
            'description.max' => 'La descripción no puede superar los 1000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'slug' => 'slug',
            'color' => 'color',
            'description' => 'descripción',
            'is_active' => 'activo',
        ];
    }
}

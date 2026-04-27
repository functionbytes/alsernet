<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConversationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.statuses.update') ?? false;
    }

    public function rules(): array
    {
        $status = $this->route('status');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('helpdesk_conversation_statuses', 'slug')->ignore($status?->id),
            ],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'slug.required' => 'El slug es obligatorio.',
            'slug.unique' => 'Ya existe un estado con este slug.',
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números, guiones y guiones bajos.',
            'color.required' => 'El color es obligatorio.',
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
            'is_default' => 'estado predeterminado',
            'active' => 'activo',
        ];
    }
}

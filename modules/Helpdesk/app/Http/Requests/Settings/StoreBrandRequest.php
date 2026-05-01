<?php

namespace Modules\Helpdesk\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.brands.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo_url' => ['nullable', 'url', 'max:500'],
            'email_from_name' => ['nullable', 'string', 'max:255'],
            'email_from_address' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la marca es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'domain.max' => 'El dominio no puede superar los 255 caracteres.',
            'primary_color.regex' => 'El color debe ser un valor hexadecimal valido (ej: #b10100).',
            'logo_url.url' => 'La URL del logo debe ser una URL valida.',
            'logo_url.max' => 'La URL del logo no puede superar los 500 caracteres.',
            'email_from_name.max' => 'El nombre del remitente no puede superar los 255 caracteres.',
            'email_from_address.email' => 'El correo del remitente debe ser un email valido.',
            'email_from_address.max' => 'El correo del remitente no puede superar los 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'domain' => 'dominio',
            'primary_color' => 'color primario',
            'logo_url' => 'URL del logo',
            'email_from_name' => 'nombre del remitente',
            'email_from_address' => 'correo del remitente',
            'is_active' => 'estado',
        ];
    }
}

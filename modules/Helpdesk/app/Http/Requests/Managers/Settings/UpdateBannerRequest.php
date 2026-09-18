<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.banners.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'type' => ['required', 'in:info,success,warning,danger'],
            'cta_text' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'url', 'max:500'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
            'body.required' => 'El contenido es obligatorio.',
            'type.required' => 'El tipo es obligatorio.',
            'type.in' => 'El tipo seleccionado no es válido.',
            'cta_url.url' => 'La URL del botón debe ser una URL válida.',
            'cta_url.max' => 'La URL no puede superar los 500 caracteres.',
            'ends_at.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la de inicio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'body' => 'contenido',
            'type' => 'tipo',
            'cta_text' => 'texto del botón',
            'cta_url' => 'URL del botón',
            'starts_at' => 'fecha de inicio',
            'ends_at' => 'fecha de fin',
        ];
    }
}

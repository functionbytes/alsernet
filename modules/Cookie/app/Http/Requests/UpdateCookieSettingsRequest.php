<?php

namespace Modules\Cookie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCookieSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('Cookie.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'enabled' => 'nullable|in:1',
            'style' => 'nullable|in:full-width,minimal',
            'message' => 'nullable|string|max:1000',
            'accept_btn_text' => 'nullable|string|max:100',
            'reject_btn_text' => 'nullable|string|max:100',
            'customize_btn_text' => 'nullable|string|max:100',
            'save_btn_text' => 'nullable|string|max:100',
            'more_info_text' => 'nullable|string|max:100',
            'more_info_url' => 'nullable|url|max:500',
            'bg_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'btn_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'max_width' => 'nullable|integer|min:200|max:1920',
            'position' => 'nullable|in:bottom,top,center',
            'google_analytics_enabled' => 'nullable|in:1',
            'google_analytics_id' => ['nullable', 'string', 'max:30', 'regex:/^(G|UA|AW|DC)-[A-Za-z0-9-]+$/i'],
            'facebook_pixel_enabled' => 'nullable|in:1',
            'facebook_pixel_id' => 'nullable|digits_between:1,20',
        ];
    }

    public function messages(): array
    {
        return [
            'message.max' => 'El mensaje no puede superar los 1000 caracteres.',
            'accept_btn_text.max' => 'El texto del botón no puede superar los 100 caracteres.',
            'reject_btn_text.max' => 'El texto del botón no puede superar los 100 caracteres.',
            'customize_btn_text.max' => 'El texto del botón no puede superar los 100 caracteres.',
            'save_btn_text.max' => 'El texto del botón no puede superar los 100 caracteres.',
            'more_info_text.max' => 'El texto del enlace no puede superar los 100 caracteres.',
            'more_info_url.url' => 'La URL debe ser una dirección válida (ej: https://ejemplo.com).',
            'more_info_url.max' => 'La URL no puede superar los 500 caracteres.',
            'bg_color.regex' => 'El color de fondo debe ser un código hexadecimal válido (ej: #ffffff).',
            'text_color.regex' => 'El color de texto debe ser un código hexadecimal válido (ej: #000000).',
            'btn_color.regex' => 'El color de botones debe ser un código hexadecimal válido (ej: #90bb13).',
            'max_width.integer' => 'El ancho máximo debe ser un número entero.',
            'max_width.min' => 'El ancho mínimo permitido es 200px.',
            'max_width.max' => 'El ancho máximo permitido es 1920px.',
            'position.in' => 'La posición debe ser inferior, superior o centro.',
            'google_analytics_id.regex' => 'El ID de Google Analytics debe tener el formato G-XXXXXXXXXX o UA-XXXXXXXX.',
            'google_analytics_id.max' => 'El ID de Google Analytics no puede superar los 30 caracteres.',
            'facebook_pixel_id.digits_between' => 'El Facebook Pixel ID debe contener solo números.',
        ];
    }
}

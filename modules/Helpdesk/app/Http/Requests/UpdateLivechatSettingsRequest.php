<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLivechatSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'show_avatars' => ['boolean'],
            'show_help_center' => ['boolean'],
            'hide_suggested_articles' => ['boolean'],
            'show_tickets_section' => ['boolean'],
            'enable_send_message' => ['boolean'],
            'enable_create_ticket' => ['boolean'],
            'enable_search_help' => ['boolean'],

            'welcome_message' => ['required', 'string', 'max:200'],
            'input_placeholder' => ['nullable', 'string', 'max:100'],
            'no_agents_message' => ['nullable', 'string', 'max:500'],
            'queue_message' => ['nullable', 'string', 'max:500'],

            'position' => ['required', 'in:bottom-right,bottom-left,top-right,top-left'],
            'side_spacing' => ['nullable', 'integer', 'min:0', 'max:100'],
            'bottom_spacing' => ['nullable', 'integer', 'min:0', 'max:100'],
            'hide_launcher' => ['boolean'],

            'primary_color' => ['required', 'regex:/^#[0-9a-f]{6}$/i'],
            'secondary_color' => ['required', 'regex:/^#[0-9a-f]{6}$/i'],
            'header_title' => ['required', 'string', 'max:100'],
            'show_dark_mode_preview' => ['boolean'],

            'show_timestamps' => ['boolean'],
            'typing_indicator' => ['boolean'],
            'sound_notifications' => ['boolean'],
            'enable_email_transcripts' => ['boolean'],

            'enable_auto_transfer' => ['boolean'],
            'auto_transfer_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
            'enable_auto_inactive' => ['boolean'],
            'auto_inactive_minutes' => ['nullable', 'integer', 'min:1', 'max:120'],
            'enable_auto_close' => ['boolean'],
            'auto_close_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],

            'trusted_domains' => ['nullable', 'string'],
            'enforce_identity_verification' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'welcome_message.required' => 'El mensaje de bienvenida es obligatorio.',
            'welcome_message.max' => 'El mensaje de bienvenida no puede superar los 200 caracteres.',
            'position.required' => 'La posición del widget es obligatoria.',
            'position.in' => 'La posición seleccionada no es válida.',
            'primary_color.required' => 'El color primario es obligatorio.',
            'primary_color.regex' => 'El color primario debe ser un código hexadecimal válido (#RRGGBB).',
            'secondary_color.required' => 'El color secundario es obligatorio.',
            'secondary_color.regex' => 'El color secundario debe ser un código hexadecimal válido (#RRGGBB).',
            'header_title.required' => 'El título del encabezado es obligatorio.',
            'header_title.max' => 'El título no puede superar los 100 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'welcome_message' => 'mensaje de bienvenida',
            'input_placeholder' => 'texto de entrada',
            'no_agents_message' => 'mensaje sin agentes',
            'queue_message' => 'mensaje de cola',
            'position' => 'posición',
            'side_spacing' => 'espaciado lateral',
            'bottom_spacing' => 'espaciado inferior',
            'primary_color' => 'color primario',
            'secondary_color' => 'color secundario',
            'header_title' => 'título del encabezado',
            'auto_transfer_minutes' => 'minutos para transferencia',
            'auto_inactive_minutes' => 'minutos de inactividad',
            'auto_close_minutes' => 'minutos para cierre',
        ];
    }
}

<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.forms.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['nullable', 'exists:form_categories,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'success_message' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'submit_button_text' => ['nullable', 'string', 'max:100'],
            'button_position' => ['nullable', 'in:left,center,right,full'],
            'button_size' => ['nullable', 'in:sm,md,lg'],
            'button_variant' => ['nullable', 'string', 'max:50'],
            'button_icon' => ['nullable', 'string', 'max:50'],
            'success_animation' => ['nullable', 'in:none,fade,checkmark,confetti'],
            'progress_bar_style' => ['nullable', 'in:bar,dots,steps,percentage'],
            'theme' => ['nullable', 'string', 'max:50'],
            'custom_css' => ['nullable', 'string'],
            'custom_js' => ['nullable', 'string'],
            'floating_label' => ['boolean'],
            'send_confirmation' => ['boolean'],
            'email_field_key' => ['nullable', 'string', 'max:100'],
            'confirmation_subject' => ['nullable', 'string', 'max:255'],
            'confirmation_message' => ['nullable', 'string'],
            'admin_notification_email' => ['nullable', 'string', 'max:500'],
            'honeypot_enabled' => ['boolean'],
            'captcha_enabled' => ['boolean'],
            'webhook_url' => ['nullable', 'url', 'max:500'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'redirect_url' => ['nullable', 'url', 'max:500'],
            'max_submissions' => ['nullable', 'integer', 'min:1'],
            'retention_days' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 150 caracteres.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'description.max' => 'La descripción no puede superar los 1000 caracteres.',
            'submit_button_text.max' => 'El texto del botón no puede superar los 100 caracteres.',
            'button_position.in' => 'La posición del botón no es válida.',
            'button_size.in' => 'El tamaño del botón no es válido.',
            'success_animation.in' => 'La animación de éxito no es válida.',
            'progress_bar_style.in' => 'El estilo de la barra de progreso no es válido.',
            'webhook_url.url' => 'La URL del webhook no es válida.',
            'redirect_url.url' => 'La URL de redirección no es válida.',
            'max_submissions.min' => 'El máximo de envíos debe ser al menos 1.',
            'retention_days.min' => 'Los días de retención deben ser al menos 1.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'category_id' => 'categoría',
            'description' => 'descripción',
            'success_message' => 'mensaje de éxito',
            'is_active' => 'estado',
            'submit_button_text' => 'texto del botón',
            'button_position' => 'posición del botón',
            'button_size' => 'tamaño del botón',
            'button_variant' => 'variante del botón',
            'button_icon' => 'icono del botón',
            'success_animation' => 'animación de éxito',
            'progress_bar_style' => 'estilo barra de progreso',
            'theme' => 'tema',
            'custom_css' => 'CSS personalizado',
            'custom_js' => 'JavaScript personalizado',
            'floating_label' => 'etiqueta flotante',
            'send_confirmation' => 'enviar confirmación',
            'email_field_key' => 'campo de email',
            'confirmation_subject' => 'asunto de confirmación',
            'confirmation_message' => 'mensaje de confirmación',
            'admin_notification_email' => 'correos de notificación',
            'honeypot_enabled' => 'honeypot',
            'captcha_enabled' => 'captcha',
            'webhook_url' => 'URL del webhook',
            'webhook_secret' => 'secreto del webhook',
            'redirect_url' => 'URL de redirección',
            'max_submissions' => 'máximo de envíos',
            'retention_days' => 'días de retención',
        ];
    }
}

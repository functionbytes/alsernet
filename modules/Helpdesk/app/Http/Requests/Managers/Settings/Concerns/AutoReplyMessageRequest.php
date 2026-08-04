<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings\Concerns;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Support\AutoReplyOptions;

/**
 * Reglas/mensajes compartidos por los 3 formularios de mensajes automáticos
 * (fuera de horario, bienvenida, despedida) — mismas columnas en los 3
 * modelos. Cada subclase solo declara su $errorBag.
 */
abstract class AutoReplyMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.settings.update');
    }

    /**
     * El <select> de "Todos los canales" / "Automático" manda value="" (string
     * vacío), no un campo ausente — `nullable` solo cubre null real, así que
     * sin esto la regla `Rule::in()` rechazaba la opción "genérica".
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'channel' => $this->input('channel') ?: null,
            'language' => $this->input('language') ?: null,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'channel' => ['nullable', Rule::in(AutoReplyOptions::channelSlugs())],
            'language' => ['nullable', Rule::in(AutoReplyOptions::languageSlugs())],
            'message' => ['required', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'El mensaje es obligatorio.',
            'message.max' => 'El mensaje no puede superar los 1000 caracteres.',
        ];
    }
}

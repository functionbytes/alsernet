<?php

namespace Modules\HelpdeskEmailActivity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResendEmailLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskemailactivity.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'to' => ['nullable', 'email:rfc', 'max:255'],
            // Marca el reenvío como "copia de prueba" (sidebar del detalle,
            // ver EmailLogController::resend()) — antepone [TEST] al asunto
            // para que nunca se confunda con el envío real. No afecta al
            // reenvío normal "a otra dirección", que sigue mandando el
            // asunto tal cual.
            'test' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'to.email' => 'Introduce una dirección de correo válida.',
            'to.max' => 'La dirección no puede superar los 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'to' => 'dirección de correo',
        ];
    }
}

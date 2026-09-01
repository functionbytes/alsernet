<?php

namespace Modules\HelpdeskEmailLog\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Triaje de rebotes en un solo paso (ver EmailLogController::resolveBounce()):
 * corrige el destinatario y reenvía, con la opción de suprimir la dirección
 * vieja. Mismo permiso que ResendEmailLogRequest — este flujo ES un reenvío,
 * solo que orquestado junto a la supresión.
 */
class ResolveBounceEmailLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskemaillog.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'to' => ['required', 'email:rfc', 'max:255'],
            'suppress_old' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'to.required' => 'Introduce la dirección corregida.',
            'to.email' => 'Introduce una dirección de correo válida.',
            'to.max' => 'La dirección no puede superar los 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'to' => 'dirección corregida',
        ];
    }

    /**
     * La dirección "corregida" debe ser distinta de la que rebotó: si coincide,
     * el flujo no está corrigiendo nada y el reenvío repetiría el mismo rebote.
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $toAddresses = $this->route('emailLog')?->to_addresses ?? [];
            $original = mb_strtolower((string) ($toAddresses[0] ?? ''));
            $corrected = mb_strtolower((string) $this->input('to'));

            if ($original !== '' && $original === $corrected) {
                $validator->errors()->add('to', __('helpdeskemaillog::emaillog.bounce_triage.same_address'));
            }
        });
    }
}

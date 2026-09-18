<?php

namespace Modules\HelpdeskEmailActivity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\HelpdeskEmailActivity\Models\EmailLog;

/**
 * Validación de la anonimización de un destinatario (acción IRREVERSIBLE).
 *
 * Además de validar el correo, exige una confirmación escrita: quien lanza la
 * acción tiene que teclear otra vez la misma dirección. Es la salvaguarda
 * habitual para un borrado sin vuelta atrás — evita el clic accidental sobre
 * una dirección que quedó escrita en el formulario.
 */
class AnonymizeRecipientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', EmailLog::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'confirmation' => ['required', 'string'],
            // Al menos una acción tiene que estar marcada; lo comprueba
            // withValidator(), porque tres booleanos sueltos no lo expresan.
            'purge_body' => ['boolean'],
            'replace_address' => ['boolean'],
            'suppress' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $email = mb_strtolower(trim((string) $this->input('email')));
            $confirmation = mb_strtolower(trim((string) $this->input('confirmation')));

            if ($email !== '' && $email !== $confirmation) {
                $validator->errors()->add('confirmation', __('helpdeskemailactivity::emaillog.gdpr.confirmation_mismatch'));
            }

            if (! $this->boolean('purge_body') && ! $this->boolean('replace_address') && ! $this->boolean('suppress')) {
                $validator->errors()->add('purge_body', __('helpdeskemailactivity::emaillog.gdpr.nothing_selected'));
            }
        });
    }
}

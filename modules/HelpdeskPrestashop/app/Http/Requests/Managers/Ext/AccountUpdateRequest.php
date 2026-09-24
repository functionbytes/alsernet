<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Edición de la ficha del cliente en PrestaShop. Solo viajan los campos que
 * el agente cambió, más la versión (date_upd) que tenía en pantalla. El email
 * no se acepta: es la clave de vinculación con el contacto del helpdesk.
 */
class AccountUpdateRequest extends FormRequest
{
    private const FIELDS = ['firstname', 'lastname', 'phone', 'id_lang', 'newsletter', 'optin'];

    public function authorize(): bool
    {
        // Permiso y acceso a ESTE cliente los comprueba el controlador.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'string', 'date_format:Y-m-d H:i:s'],
            'firstname' => ['sometimes', 'string', 'min:1', 'max:255', 'not_regex:/[0-9!<>,;?=+()@#"°{}_$%:¤|]/u'],
            'lastname' => ['sometimes', 'string', 'min:1', 'max:255', 'not_regex:/[0-9!<>,;?=+()@#"°{}_$%:¤|]/u'],
            'phone' => ['sometimes', 'string', 'min:6', 'max:32', 'regex:/^[+0-9. ()\/-]+$/'],
            'address_id' => ['required_with:phone', 'integer', 'min:1'],
            'address_version' => ['required_with:phone', 'string', 'date_format:Y-m-d H:i:s'],
            'id_lang' => ['sometimes', 'integer', 'min:1'],
            'newsletter' => ['sometimes', 'boolean'],
            'optin' => ['sometimes', 'boolean'],
            'conversation_id' => ['nullable', 'integer'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! collect(self::FIELDS)->contains(fn ($f) => $this->has($f))) {
                    $validator->errors()->add('version', 'No hay ningún cambio que guardar.');
                }
            },
        ];
    }

    /**
     * Cambios tal y como los espera el puente (booleans normalizados).
     *
     * @return array<string, mixed>
     */
    public function changes(): array
    {
        $data = $this->validated();
        $out = ['version' => $data['version']];

        foreach (['firstname', 'lastname'] as $f) {
            if (array_key_exists($f, $data)) {
                $out[$f] = trim((string) $data[$f]);
            }
        }
        if (array_key_exists('phone', $data)) {
            $out['phone'] = trim((string) $data['phone']);
            $out['address_id'] = (int) $data['address_id'];
            $out['address_version'] = $data['address_version'];
        }
        if (array_key_exists('id_lang', $data)) {
            $out['id_lang'] = (int) $data['id_lang'];
        }
        foreach (['newsletter', 'optin'] as $f) {
            if (array_key_exists($f, $data)) {
                $out[$f] = (bool) $data[$f];
            }
        }

        return $out;
    }

    public function messages(): array
    {
        return [
            'firstname.not_regex' => 'El nombre no puede llevar números ni símbolos.',
            'lastname.not_regex' => 'Los apellidos no pueden llevar números ni símbolos.',
            'phone.regex' => 'El teléfono solo admite números, espacios, +, guiones y paréntesis.',
            'phone.min' => 'El teléfono es demasiado corto.',
            'version.required' => 'Falta la versión de la ficha: recárgala.',
        ];
    }
}

<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\HelpdeskPrestashop\Services\Ext\SettingsService;

/**
 * Guardado de «Ajustes del chat». Validación estricta: límites positivos y
 * coherentes (supervisor ≥ agente), tope duro de 500 € en los vales (el
 * puente no crea vales mayores), claves de motivo como slug, y en las
 * respuestas rápidas solo las variables que el panel sabe rellenar.
 */
class SettingsUpdateRequest extends FormRequest
{
    public const VOUCHER_HARD_CAP = 500;

    public const REFUND_HARD_CAP = 10000;

    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskprestashop.settings.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Filas vacías del repetidor (añadidas y dejadas en blanco) fuera.
        $reasons = array_values(array_filter((array) $this->input('vouchers.reasons', []), function ($row) {
            return is_array($row) && (trim((string) ($row['key'] ?? '')) !== '' || trim((string) ($row['label'] ?? '')) !== '');
        }));
        $replies = array_values(array_filter((array) $this->input('replies', []), function ($row) {
            return is_array($row) && (trim((string) ($row['t'] ?? '')) !== '' || trim((string) ($row['text'] ?? '')) !== '');
        }));

        $vouchers = (array) $this->input('vouchers', []);
        $vouchers['reasons'] = $reasons;

        $this->merge(['vouchers' => $vouchers, 'replies' => $replies]);
    }

    public function rules(): array
    {
        return [
            'vouchers' => ['required', 'array'],
            'vouchers.agent_limit' => ['required', 'numeric', 'min:1', 'max:'.self::VOUCHER_HARD_CAP],
            'vouchers.approver_limit' => ['required', 'numeric', 'min:1', 'max:'.self::VOUCHER_HARD_CAP, 'gte:vouchers.agent_limit'],
            'vouchers.validity_days' => ['required', 'string', 'max:60', 'regex:/^\s*\d{1,3}(\s*,\s*\d{1,3})*\s*$/'],
            'vouchers.reasons' => ['required', 'array', 'min:1', 'max:20'],
            'vouchers.reasons.*.key' => ['required', 'string', 'regex:/^[a-z][a-z0-9_-]{1,39}$/', 'distinct'],
            'vouchers.reasons.*.label' => ['required', 'string', 'max:120'],

            'refunds' => ['required', 'array'],
            'refunds.agent_limit' => ['required', 'numeric', 'min:1', 'max:'.self::REFUND_HARD_CAP],
            'refunds.approver_limit' => ['required', 'numeric', 'min:1', 'max:'.self::REFUND_HARD_CAP, 'gte:refunds.agent_limit'],
            'refunds.carrier' => ['nullable', 'string', 'max:150'],
            'refunds.address' => ['nullable', 'string', 'max:1000'],
            'refunds.validity_days' => ['required', 'integer', 'min:1', 'max:365'],
            'refunds.steps' => ['nullable', 'string', 'max:3000'],

            'replies' => ['present', 'array', 'max:20'],
            'replies.*.t' => ['required', 'string', 'max:80'],
            'replies.*.s' => ['nullable', 'string', 'max:160'],
            'replies.*.text' => ['required', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $days = $this->parseDays((string) $this->input('vouchers.validity_days'));
                if ($days === [] || count($days) > 8 || max($days) > 365 || min($days) < 1) {
                    $validator->errors()->add('vouchers.validity_days', 'La validez de los vales tiene que ser de 1 a 8 plazos distintos, entre 1 y 365 días.');
                }

                $address = $this->lines((string) $this->input('refunds.address'));
                if (count($address) > 8) {
                    $validator->errors()->add('refunds.address', 'La dirección de devoluciones admite como mucho 8 líneas.');
                }
                foreach ($address as $line) {
                    if (mb_strlen($line) > 150 || str_contains($line, '|')) {
                        $validator->errors()->add('refunds.address', 'Cada línea de la dirección puede tener hasta 150 caracteres y no puede llevar «|».');
                        break;
                    }
                }

                $steps = $this->lines((string) $this->input('refunds.steps'));
                if ($steps === [] || count($steps) > 10) {
                    $validator->errors()->add('refunds.steps', 'Escribe entre 1 y 10 pasos de devolución, uno por línea.');
                }
                foreach ($steps as $step) {
                    if (mb_strlen($step) > 300) {
                        $validator->errors()->add('refunds.steps', 'Cada paso de devolución puede tener hasta 300 caracteres.');
                        break;
                    }
                }

                foreach ((array) $this->input('replies', []) as $i => $row) {
                    $unknown = array_merge(
                        SettingsService::unknownVariables((string) ($row['t'] ?? '')),
                        SettingsService::unknownVariables((string) ($row['s'] ?? '')),
                        SettingsService::unknownVariables((string) ($row['text'] ?? '')),
                    );
                    if ($unknown !== []) {
                        $validator->errors()->add(
                            'replies.'.$i.'.text',
                            'La respuesta «'.mb_strimwidth((string) ($row['t'] ?? ''), 0, 40, '…').'» usa variables que no existen: {'.implode('}, {', array_unique($unknown)).'}.'
                        );
                    }
                }
            },
        ];
    }

    /**
     * Valores validados con la forma de config (claves lógicas de
     * SettingsOverrides::MAP).
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $data = $this->validated();

        $reasons = [];
        foreach ($data['vouchers']['reasons'] as $row) {
            $reasons[(string) $row['key']] = trim((string) $row['label']);
        }

        return [
            'vouchers.agent_limit' => round((float) $data['vouchers']['agent_limit'], 2),
            'vouchers.approver_limit' => round((float) $data['vouchers']['approver_limit'], 2),
            'vouchers.validity_days' => $this->parseDays((string) $data['vouchers']['validity_days']),
            'vouchers.reasons' => $reasons,
            'refunds.agent_limit' => round((float) $data['refunds']['agent_limit'], 2),
            'refunds.approver_limit' => round((float) $data['refunds']['approver_limit'], 2),
            // Mismo formato que config/ext/refunds.php: la dirección en una
            // cadena con las líneas separadas por «|».
            'refunds.return_instructions' => [
                'carrier' => trim((string) ($data['refunds']['carrier'] ?? '')),
                'address' => implode('|', $this->lines((string) ($data['refunds']['address'] ?? ''))),
                'validity_days' => (int) $data['refunds']['validity_days'],
                'steps' => $this->lines((string) ($data['refunds']['steps'] ?? '')),
            ],
            'quick_replies' => array_map(fn ($row) => [
                't' => trim((string) $row['t']),
                's' => trim((string) ($row['s'] ?? '')),
                'text' => trim((string) $row['text']),
            ], array_values($data['replies'] ?? [])),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function parseDays(string $raw): array
    {
        $days = array_values(array_unique(array_map('intval', array_filter(array_map('trim', explode(',', $raw)), 'strlen'))));
        sort($days);

        return $days;
    }

    /**
     * @return array<int, string>
     */
    private function lines(string $raw): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', $raw) ?: []), 'strlen'));
    }

    public function messages(): array
    {
        return [
            'vouchers.approver_limit.gte' => 'El límite de supervisor de los vales no puede ser menor que el de agente.',
            'refunds.approver_limit.gte' => 'El límite de supervisor de los reembolsos no puede ser menor que el de agente.',
            'vouchers.agent_limit.max' => 'El puente no crea vales de más de :max €.',
            'vouchers.approver_limit.max' => 'El puente no crea vales de más de :max €.',
            'vouchers.validity_days.regex' => 'Escribe la validez de los vales como días separados por comas, por ejemplo: 30, 60, 90.',
            'vouchers.reasons.required' => 'Tiene que haber al menos un motivo de vale.',
            'vouchers.reasons.min' => 'Tiene que haber al menos un motivo de vale.',
            'vouchers.reasons.*.key.regex' => 'La clave de un motivo solo admite minúsculas, números, «-» y «_», y empieza por letra (2 a 40 caracteres).',
            'vouchers.reasons.*.key.distinct' => 'Hay dos motivos con la misma clave.',
            'vouchers.reasons.*.key.required' => 'Cada motivo necesita una clave.',
            'vouchers.reasons.*.label.required' => 'Cada motivo necesita un texto.',
            'replies.max' => 'Como mucho 20 respuestas rápidas.',
            'replies.*.t.required' => 'Cada respuesta rápida necesita un título.',
            'replies.*.text.required' => 'Cada respuesta rápida necesita un texto.',
        ];
    }

    public function attributes(): array
    {
        return [
            'vouchers.agent_limit' => 'límite de agente de los vales',
            'vouchers.approver_limit' => 'límite de supervisor de los vales',
            'vouchers.validity_days' => 'validez de los vales',
            'vouchers.reasons.*.label' => 'texto del motivo',
            'refunds.agent_limit' => 'límite de agente de los reembolsos',
            'refunds.approver_limit' => 'límite de supervisor de los reembolsos',
            'refunds.carrier' => 'transportista',
            'refunds.address' => 'dirección de devoluciones',
            'refunds.validity_days' => 'plazo de devolución',
            'refunds.steps' => 'pasos de devolución',
            'replies.*.t' => 'título',
            'replies.*.s' => 'subtítulo',
            'replies.*.text' => 'texto',
        ];
    }
}

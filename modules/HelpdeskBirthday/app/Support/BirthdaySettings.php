<?php

namespace Modules\HelpdeskBirthday\Support;

use Modules\Core\Models\Setting;
use Throwable;

/**
 * Ajustes del módulo, editables desde el panel y con la config del módulo como
 * valor por defecto.
 *
 * Se apoya en Modules\Core\Models\Setting (mismo mecanismo que el resto del
 * Helpdesk). Ojo en tests: Setting::set() escribe en la caché real, fuera de la
 * transacción de DatabaseTransactions, así que conviene mockear la config en
 * vez de escribir ajustes.
 */
class BirthdaySettings
{
    private const PREFIX = 'helpdesk_birthday.';

    /**
     * Todos los ajustes resueltos, listos para el formulario y para el comando
     * de preparación.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return [
            'window_start' => $this->string('window_start', (string) config('helpdeskbirthday.window_start', '09:00')),
            'window_end' => $this->string('window_end', (string) config('helpdeskbirthday.window_end', '14:00')),
            'throttle_per_hour' => $this->int('throttle_per_hour', (int) config('helpdeskbirthday.throttle_per_hour', 600)),
            'max_recipients' => $this->int('max_recipients', (int) config('helpdeskbirthday.max_recipients', 2000)),
            'leap_day_policy' => $this->string('leap_day_policy', (string) config('helpdeskbirthday.leap_day_policy', 'feb28')),
            'template_key' => $this->string('template_key', (string) config('helpdeskbirthday.template_key', 'birthday-coupon')),

            // Tipo de bono en Gestión (IDTBONO_PROMOCION): dice QUÉ bono se
            // emite —importe, validez y compra mínima salen de él— y sin este
            // valor no se puede generar uno por cliente.
            'bono_type_id' => (int) $this->string('bono_type_id', (string) config('helpdeskbirthday.coupon.bono_type_id', 0)),

            'coupon_code' => $this->string('coupon_code', (string) config('helpdeskbirthday.coupon.code', '')),
            'coupon_verification_code' => $this->string('coupon_verification_code', (string) config('helpdeskbirthday.coupon.verification_code', '')),
            'coupon_valid_from' => $this->string('coupon_valid_from', ''),
            'coupon_valid_to' => $this->string('coupon_valid_to', ''),
            'coupon_amount' => $this->string('coupon_amount', ''),
            'coupon_min_purchase' => $this->string('coupon_min_purchase', ''),
            'validate_against_erp' => $this->bool('validate_against_erp', (bool) config('helpdeskbirthday.coupon.validate_against_erp', true)),

            'commercial_optin' => $this->bool('commercial_optin', (bool) config('helpdeskbirthday.exclusions.commercial_optin', true)),
            'lopd_accepted' => $this->bool('lopd_accepted', (bool) config('helpdeskbirthday.exclusions.lopd_accepted', false)),
            'has_email' => $this->bool('has_email', (bool) config('helpdeskbirthday.exclusions.has_email', true)),
            'check_suppressions' => $this->bool('check_suppressions', (bool) config('helpdeskbirthday.exclusions.check_suppressions', true)),
        ];
    }

    /**
     * Solo las cuatro exclusiones, tal como las espera BirthdayAudienceService.
     *
     * @return array<string, bool>
     */
    public function exclusions(): array
    {
        $all = $this->all();

        return [
            'commercial_optin' => (bool) $all['commercial_optin'],
            'lopd_accepted' => (bool) $all['lopd_accepted'],
            'has_email' => (bool) $all['has_email'],
            'check_suppressions' => (bool) $all['check_suppressions'],
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): void
    {
        foreach ($values as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            Setting::set(self::PREFIX.$key, (string) ($value ?? ''));
        }
    }

    private function string(string $key, string $default): string
    {
        try {
            $value = Setting::get(self::PREFIX.$key, $default);
        } catch (Throwable) {
            return $default;
        }

        $value = (string) ($value ?? '');

        return $value !== '' ? $value : $default;
    }

    private function int(string $key, int $default): int
    {
        $value = $this->string($key, (string) $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    private function bool(string $key, bool $default): bool
    {
        try {
            $value = Setting::get(self::PREFIX.$key, $default ? '1' : '0');
        } catch (Throwable) {
            return $default;
        }

        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

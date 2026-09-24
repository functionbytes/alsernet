<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Illuminate\Support\Facades\DB;

/**
 * Lectura y guardado de «Ajustes del chat» (extensión "settings").
 *
 * Solo se guardan en helpdesk_ps_settings las claves cuyo valor difiere del
 * de config/.env: si coincide, la fila se borra y el ajuste vuelve a seguir
 * al .env. Tras guardar se llama a SettingsOverrides::refresh(), que
 * invalida la caché y aplica ya en esta misma petición.
 */
class SettingsService
{
    /**
     * Variables de las respuestas rápidas: nombre → qué dato pone el panel.
     */
    public const REPLY_VARIABLES = [
        'pedido' => 'Referencia del último pedido',
        'estado' => 'Estado del último pedido',
        'transportista' => 'Transportista del último pedido',
        'seguimiento' => 'Número de seguimiento del último pedido',
        'enlace_seguimiento' => 'Enlace de seguimiento del último pedido',
        'total' => 'Total del último pedido',
        'cliente' => 'Nombre del cliente',
        'rma' => 'Devolución abierta más reciente (RMA-123)',
        'importe_reembolso' => 'Importe del último reembolso',
    ];

    /** Secciones de la pantalla → claves lógicas que agrupan. */
    public const SECTIONS = [
        'vouchers' => ['vouchers.agent_limit', 'vouchers.approver_limit', 'vouchers.validity_days', 'vouchers.reasons'],
        'refunds' => ['refunds.agent_limit', 'refunds.approver_limit', 'refunds.return_instructions'],
        'replies' => ['quick_replies'],
    ];

    public const SECTION_LABELS = [
        'vouchers' => 'Vales de compensación',
        'refunds' => 'Reembolsos e instrucciones de retorno',
        'replies' => 'Respuestas rápidas',
    ];

    public function ready(): bool
    {
        return SettingsOverrides::ready();
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return SettingsOverrides::defaults();
    }

    /**
     * Valores en vigor: defaults de config/.env con lo guardado encima.
     *
     * @return array<string, mixed>
     */
    public function current(): array
    {
        $values = $this->defaults();

        foreach ($this->storedRows() as $key => $row) {
            $normalized = SettingsOverrides::normalize($key, $row['value']);
            if ($normalized !== null) {
                $values[$key] = $normalized;
            }
        }

        return $values;
    }

    /**
     * Claves que ahora mismo pisan el valor por defecto.
     *
     * @return array<int, string>
     */
    public function overriddenKeys(): array
    {
        return array_keys($this->storedRows());
    }

    /**
     * @return array{at: ?string, by: ?int}
     */
    public function lastUpdate(): array
    {
        $last = ['at' => null, 'by' => null];

        foreach ($this->storedRows() as $row) {
            if ($last['at'] === null || (string) $row['updated_at'] > $last['at']) {
                $last = ['at' => (string) $row['updated_at'], 'by' => $row['updated_by']];
            }
        }

        return $last;
    }

    /**
     * Guarda los valores ya validados y normalizados (claves lógicas).
     * Devuelve el diff para el log de actividad.
     *
     * @param  array<string, mixed>  $values
     * @return array{changed: array<string, array{from: mixed, to: mixed}>}
     */
    public function save(array $values, ?int $userId): array
    {
        $defaults = $this->defaults();
        $before = $this->current();
        $changed = [];
        $now = now();

        DB::connection(SettingsOverrides::CONNECTION)->transaction(function () use ($values, $defaults, $before, $userId, $now, &$changed) {
            $table = DB::connection(SettingsOverrides::CONNECTION)->table(SettingsOverrides::TABLE);

            foreach ($values as $key => $value) {
                if (! isset(SettingsOverrides::MAP[$key])) {
                    continue;
                }

                if ($this->same($key, $value, $before[$key] ?? null)) {
                    continue;
                }

                $changed[$key] = ['from' => $before[$key] ?? null, 'to' => $value];

                if ($this->same($key, $value, $defaults[$key] ?? null)) {
                    (clone $table)->where('key', $key)->delete();

                    continue;
                }

                $exists = (clone $table)->where('key', $key)->exists();
                $payload = [
                    'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_by' => $userId,
                    'updated_at' => $now,
                ];

                if ($exists) {
                    (clone $table)->where('key', $key)->update($payload);
                } else {
                    (clone $table)->insert($payload + ['key' => $key, 'created_at' => $now]);
                }
            }
        });

        SettingsOverrides::refresh();

        return ['changed' => $changed];
    }

    /**
     * Vuelve una sección a los valores de config/.env (borra sus filas).
     *
     * @return array{changed: array<string, array{from: mixed, to: mixed}>}
     */
    public function reset(string $section, ?int $userId): array
    {
        $keys = self::SECTIONS[$section] ?? [];
        $defaults = $this->defaults();

        return $this->save(array_intersect_key($defaults, array_flip($keys)), $userId);
    }

    /**
     * Variables {nombre} usadas en un texto que no están admitidas.
     *
     * @return array<int, string>
     */
    public static function unknownVariables(string $text): array
    {
        preg_match_all('/\{([^{}]*)\}/u', $text, $m);

        return array_values(array_unique(array_filter($m[1], fn ($v) => ! isset(self::REPLY_VARIABLES[$v]))));
    }

    /**
     * @return array<string, array{value: mixed, updated_at: ?string, updated_by: ?int}>
     */
    private function storedRows(): array
    {
        if (! $this->ready()) {
            return [];
        }

        $rows = [];
        foreach (DB::connection(SettingsOverrides::CONNECTION)->table(SettingsOverrides::TABLE)->get() as $row) {
            if (! isset(SettingsOverrides::MAP[$row->key])) {
                continue;
            }
            $rows[$row->key] = [
                'value' => json_decode((string) $row->value, true),
                'updated_at' => $row->updated_at !== null ? (string) $row->updated_at : null,
                'updated_by' => $row->updated_by !== null ? (int) $row->updated_by : null,
            ];
        }

        return $rows;
    }

    /**
     * Compara valores de config sin tropezar con 25 vs 25.0. El orden sí
     * cuenta (motivos, días, respuestas se muestran en ese orden), salvo en
     * las instrucciones de retorno, que son un registro de campos.
     */
    private function same(string $key, mixed $a, mixed $b): bool
    {
        if ($key === 'refunds.return_instructions' && is_array($a) && is_array($b)) {
            ksort($a);
            ksort($b);
        }

        return $this->canon($a) === $this->canon($b);
    }

    private function canon(mixed $v): mixed
    {
        if (is_int($v) || is_float($v)) {
            return round((float) $v, 4);
        }
        if (is_array($v)) {
            return array_map(fn ($x) => $this->canon($x), $v);
        }

        return $v;
    }
}

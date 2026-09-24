<?php

namespace Modules\HelpdeskErp\Services\ErpAdmin;

use Illuminate\Support\Facades\DB;
use Modules\HelpdeskErp\Models\ErpAdminSetting;

/**
 * Lectura y guardado de «Ajustes de Gestión».
 *
 * Solo se guardan en helpdesk_erp_settings las claves cuyo valor difiere del
 * de config/.env: si coincide, la fila se borra y el ajuste vuelve a seguir
 * al .env. Tras guardar, ErpAdminSettingsOverrides::refresh() invalida la
 * caché y aplica ya en esta misma petición.
 */
class ErpAdminSettingsService
{
    /** Secciones de la pantalla → claves lógicas que agrupan. */
    public const SECTIONS = [
        'cache' => ['chat_ttl.ok', 'chat_ttl.detail', 'chat_ttl.blocked', 'chat_ttl.unavailable', 'chat_ttl.down'],
        'overview' => ['overview.orders_limit', 'alerts.expiry_days', 'alerts.enabled'],
        'linking' => ['linking.auto'],
        'tracking' => ['tracking.urls'],
        'metrics' => ['metrics.enabled', 'metrics.retention_days'],
    ];

    public const SECTION_LABELS = [
        'cache' => 'Caché por estado',
        'overview' => 'Resumen y avisos',
        'linking' => 'Vinculación automática',
        'tracking' => 'Seguimiento de envíos',
        'metrics' => 'Métricas de uso',
    ];

    public const TTL_LABELS = [
        'ok' => ['Datos correctos', 'Listas y fichas del cliente.'],
        'detail' => ['Detalle', 'Pedido, albarán o factura abiertos (cambian poco).'],
        'blocked' => ['Pendiente de permiso', 'Sección sin GRANT en Oracle: no cambia hasta que actúe el DBA.'],
        'unavailable' => ['No disponible', 'Endpoint inexistente en esta versión del manager.'],
        'down' => ['Sin conexión', 'Manager caído o sin respuesta: corto para reintentar pronto.'],
    ];

    public const ALERT_LABELS = [
        'risk' => ['Riesgo superado', 'El riesgo actual supera el límite de crédito.'],
        'debt' => ['Deuda pendiente', 'Importe pendiente de cobro en albaranes.'],
        'inactive' => ['Cliente de baja', 'Dado de baja o inactivo en Gestión.'],
        'lopd' => ['LOPD', 'No acepta información comercial o no consta la aceptación.'],
        'expiry' => ['Caducidad de vales y bonos', 'Vales o bonos que caducan en los próximos días.'],
        'served' => ['Servido hoy', 'Pedido servido hoy o ayer.'],
    ];

    public function ready(): bool
    {
        return ErpAdminSettingsOverrides::ready();
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return ErpAdminSettingsOverrides::defaults();
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
            $normalized = ErpAdminSettingsOverrides::normalize($key, $row['value']);
            if ($normalized !== null) {
                $values[$key] = $normalized;
            }
        }

        return $values;
    }

    /**
     * @return array<int, string>
     */
    public function overriddenKeys(): array
    {
        return array_keys($this->storedRows());
    }

    /**
     * @return array<string, bool>
     */
    public function overriddenSections(): array
    {
        $overridden = $this->overriddenKeys();
        $out = [];
        foreach (self::SECTIONS as $section => $keys) {
            $out[$section] = array_intersect($keys, $overridden) !== [];
        }

        return $out;
    }

    /**
     * @return array{at: ?string, by: ?int}
     */
    public function lastUpdate(): array
    {
        $last = ['at' => null, 'by' => null];

        foreach ($this->storedRows() as $row) {
            if ($row['updated_at'] !== null && ($last['at'] === null || $row['updated_at'] > $last['at'])) {
                $last = ['at' => $row['updated_at'], 'by' => $row['updated_by']];
            }
        }

        return $last;
    }

    /**
     * Guarda valores ya validados (claves lógicas). Devuelve el diff para el
     * log de actividad.
     *
     * @param  array<string, mixed>  $values
     * @return array{changed: array<string, array{from: mixed, to: mixed}>}
     */
    public function save(array $values, ?int $userId): array
    {
        $defaults = $this->defaults();
        $before = $this->current();
        $changed = [];

        DB::connection(ErpAdminSettingsOverrides::CONNECTION)->transaction(function () use ($values, $defaults, $before, $userId, &$changed) {
            foreach ($values as $key => $value) {
                if (! isset(ErpAdminSettingsOverrides::MAP[$key])) {
                    continue;
                }

                $value = ErpAdminSettingsOverrides::normalize($key, $value);
                if ($value === null) {
                    continue;
                }

                if ($this->same($value, $before[$key] ?? null)) {
                    continue;
                }

                $changed[$key] = ['from' => $before[$key] ?? null, 'to' => $value];

                if ($this->same($value, $defaults[$key] ?? null)) {
                    ErpAdminSetting::query()->where('key', $key)->delete();

                    continue;
                }

                ErpAdminSetting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $value, 'updated_by' => $userId],
                );
            }
        });

        ErpAdminSettingsOverrides::refresh();

        return ['changed' => $changed];
    }

    /**
     * Vuelve una sección a los valores de config/.env.
     *
     * @return array{changed: array<string, array{from: mixed, to: mixed}>}
     */
    public function reset(string $section, ?int $userId): array
    {
        $keys = self::SECTIONS[$section] ?? [];

        return $this->save(array_intersect_key($this->defaults(), array_flip($keys)), $userId);
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
        foreach (ErpAdminSetting::query()->get() as $row) {
            if (! isset(ErpAdminSettingsOverrides::MAP[$row->key])) {
                continue;
            }
            $rows[$row->key] = [
                'value' => $row->value,
                'updated_at' => $row->updated_at?->toDateTimeString(),
                'updated_by' => $row->updated_by,
            ];
        }

        return $rows;
    }

    private function same(mixed $a, mixed $b): bool
    {
        return $this->canon($a) === $this->canon($b);
    }

    private function canon(mixed $v): mixed
    {
        if (is_int($v) || is_float($v)) {
            return round((float) $v, 4);
        }
        if (is_array($v)) {
            // Los avisos son un registro de campos: el orden no cuenta.
            if (array_keys($v) !== range(0, count($v) - 1)) {
                ksort($v);
            }

            return array_map(fn ($x) => $this->canon($x), $v);
        }

        return $v;
    }
}

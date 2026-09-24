<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

/**
 * Métricas del chat · PrestaShop: lo que los agentes han hecho contra la
 * tienda desde el panel, leído SOLO del log de actividad 'helpdeskprestashop'
 * (el mismo que pinta «Auditoría de acciones»). No se consulta la tienda ni
 * se estima nada: cada número sale de una fila de activity_log.
 *
 * Qué guarda cada acción (y por tanto de dónde sale el importe):
 *  - ps.voucher.created   (PsVoucherActionsController)  properties.amount (€)
 *  - ps.voucher.duplicated (PromosVoucherController)    properties.amount (€) o
 *                          properties.percent si el vale es de porcentaje (sin importe)
 *  - ps.refund.issued     (RefundsOrderController)      properties.amount (€, IVA incl.)
 *  - ps.orders.status     (auditoría genérica)          properties.input.state_name;
 *                          se cuenta como anulación si el estado es de cancelación
 *  - ps.rma.state_changed (RefundsRmaController)        properties.state_id; resuelta =
 *                          estado «completada» o «denegada» (config refunds.rma_states)
 *  - ps.cart.converted    (CartpayController)           properties.total (€, pedido creado)
 *  - ps.cart.emptied      (CartpayController)           sin importe
 *  - direcciones          (genérica + PsAddressActionsController) sin importe
 *  - ps.catalog.stock_alert (CatalogController)         sin importe
 *
 * Todo se agrega en SQL en una única consulta (métrica × agente × semana)
 * sobre log_name + created_at, que tienen índice.
 */
class MetricsChatService
{
    public const LOG_NAME = 'helpdeskprestashop';

    /** Métricas en el orden en que se pintan. */
    public const METRICS = [
        'vouchers' => ['label' => 'Vales emitidos', 'short' => 'Vales', 'money' => true],
        'refunds' => ['label' => 'Reembolsos emitidos', 'short' => 'Reembolsos', 'money' => true],
        'cancelled' => ['label' => 'Pedidos anulados', 'short' => 'Anulados', 'money' => false],
        'returns' => ['label' => 'Devoluciones resueltas', 'short' => 'Devoluciones', 'money' => false],
        'carts_converted' => ['label' => 'Carritos convertidos', 'short' => 'Convertidos', 'money' => true],
        'carts_emptied' => ['label' => 'Carritos vaciados', 'short' => 'Vaciados', 'money' => false],
        'addresses' => ['label' => 'Cambios de dirección', 'short' => 'Direcciones', 'money' => false],
        'stock_alerts' => ['label' => 'Avisos de reposición', 'short' => 'Reposición', 'money' => false],
    ];

    /** Resto de acciones contra la tienda (notas, seguimiento, ficha…). */
    public const OTHER = 'other';

    public const VOUCHER_ACTIONS = ['ps.voucher.created', 'ps.voucher.duplicated'];

    /**
     * Cambios de dirección: del pedido, del carrito y de la ficha del cliente
     * (la descripción antigua de la auditoría genérica y la actual del
     * controlador). Crear una dirección nueva no es un cambio y no cuenta.
     */
    public const ADDRESS_ACTIONS = ['ps.orders.address', 'ps.cart.address', 'ps.address.updated', 'ps.addresses.update'];

    /** Fragmentos (en minúsculas, sin tildes) de un estado de pedido de anulación. */
    private const CANCEL_STATE_FRAGMENTS = ['cancel', 'anulad'];

    /**
     * @return array<int, int>
     */
    public function periods(): array
    {
        $periods = array_values(array_filter(array_map('intval', (array) config('helpdeskprestashop.ext.metrics.periods', [7, 30, 90]))));

        return $periods ?: [7, 30, 90];
    }

    public function since(int $days): Carbon
    {
        return now()->subDays($days)->startOfDay();
    }

    /**
     * Log 'helpdeskprestashop' del periodo, sin lo que no es acción contra
     * la tienda (mismo filtro que la auditoría: operación de opslog y
     * descargas de documentos).
     */
    public function periodQuery(int $days, ?int $agentId = null): Builder
    {
        $query = Activity::query()
            ->where('log_name', self::LOG_NAME)
            ->where('created_at', '>=', $this->since($days));

        foreach ((array) config('helpdeskprestashop.ext.opsmap.audit_hidden_descriptions', ['ps.ops.*', 'ps.order_document_download']) as $pattern) {
            $pattern = (string) $pattern;
            if (str_ends_with($pattern, '*')) {
                $prefix = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], substr($pattern, 0, -1));
                $query->where('description', 'not like', $prefix.'%');
            } elseif ($pattern !== '') {
                $query->where('description', '!=', $pattern);
            }
        }

        if ($agentId) {
            $query->where('causer_type', (new User)->getMorphClass())->where('causer_id', $agentId);
        }

        return $query;
    }

    /**
     * Expresión SQL que clasifica cada fila en una métrica (o 'other') y
     * sus bindings.
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    public function metricCaseSql(): array
    {
        $bindings = [];
        $in = function (array $values) use (&$bindings): string {
            array_push($bindings, ...$values);

            return implode(', ', array_fill(0, count($values), '?'));
        };

        $stateName = "LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.input.state_name')), JSON_UNQUOTE(JSON_EXTRACT(properties, '$.state_name')), ''))";
        $cancelLikes = implode(' OR ', array_map(fn () => "{$stateName} LIKE ?", self::CANCEL_STATE_FRAGMENTS));
        $resolvedStates = $this->resolvedReturnStates();

        $sql = 'CASE'
            .' WHEN description IN ('.$in(self::VOUCHER_ACTIONS).") THEN 'vouchers'"
            ." WHEN description = ? THEN 'refunds'";
        $bindings[] = 'ps.refund.issued';

        $sql .= " WHEN description = ? AND ({$cancelLikes}) THEN 'cancelled'";
        $bindings[] = 'ps.orders.status';
        foreach (self::CANCEL_STATE_FRAGMENTS as $fragment) {
            $bindings[] = '%'.$fragment.'%';
        }

        if ($resolvedStates !== []) {
            // El placeholder de la descripción va antes que los de los estados.
            $bindings[] = 'ps.rma.state_changed';
            $sql .= " WHEN description = ? AND CAST(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.state_id')) AS UNSIGNED) IN (".$in($resolvedStates).") THEN 'returns'";
        }

        $sql .= " WHEN description = ? THEN 'carts_converted'";
        $bindings[] = 'ps.cart.converted';
        $sql .= " WHEN description = ? THEN 'carts_emptied'";
        $bindings[] = 'ps.cart.emptied';
        $sql .= ' WHEN description IN ('.$in(self::ADDRESS_ACTIONS).") THEN 'addresses'";
        $sql .= " WHEN description = ? THEN 'stock_alerts'";
        $bindings[] = 'ps.catalog.stock_alert';
        $sql .= " ELSE '".self::OTHER."' END";

        return [$sql, $bindings];
    }

    /**
     * Importe (€) que guardó la acción, o NULL si no guarda ninguno:
     * vales y reembolsos en properties.amount; carrito convertido en
     * properties.total (total del pedido creado).
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    public function amountSql(): array
    {
        $amount = "JSON_UNQUOTE(JSON_EXTRACT(properties, '$.amount'))";
        $total = "JSON_UNQUOTE(JSON_EXTRACT(properties, '$.total'))";
        $placeholders = implode(', ', array_fill(0, count(self::VOUCHER_ACTIONS) + 1, '?'));

        $sql = 'CASE'
            ." WHEN description IN ({$placeholders}) AND {$amount} IS NOT NULL AND {$amount} <> 'null' AND {$amount} <> '' THEN CAST({$amount} AS DECIMAL(14,2))"
            ." WHEN description = ? AND {$total} IS NOT NULL AND {$total} <> 'null' AND {$total} <> '' THEN CAST({$total} AS DECIMAL(14,2))"
            .' ELSE NULL END';

        return [$sql, [...self::VOUCHER_ACTIONS, 'ps.refund.issued', 'ps.cart.converted']];
    }

    /**
     * Agregado único: métrica × agente × semana (lunes), con número de
     * acciones, suma de importes y cuántas guardaron importe.
     *
     * @return Collection<int, object{metric: string, causer_type: ?string, causer_id: ?int, week: string, n: int, amount: ?string, with_amount: int}>
     */
    public function aggregate(int $days, ?int $agentId = null): Collection
    {
        [$metricSql, $metricBindings] = $this->metricCaseSql();
        [$amountSql, $amountBindings] = $this->amountSql();
        $weekSql = 'DATE(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY))';

        return $this->periodQuery($days, $agentId)->toBase()
            ->selectRaw("{$metricSql} AS metric", $metricBindings)
            ->addSelect(['causer_type', 'causer_id'])
            ->selectRaw("{$weekSql} AS week")
            ->selectRaw('COUNT(*) AS n')
            ->selectRaw("SUM({$amountSql}) AS amount", $amountBindings)
            ->selectRaw("SUM(CASE WHEN ({$amountSql}) IS NULL THEN 0 ELSE 1 END) AS with_amount", $amountBindings)
            ->groupBy(DB::raw('metric'), 'causer_type', 'causer_id', DB::raw('week'))
            ->get()
            ->map(function ($row) {
                $row->n = (int) $row->n;
                $row->with_amount = (int) $row->with_amount;
                $row->causer_id = $row->causer_id !== null ? (int) $row->causer_id : null;
                $row->week = (string) $row->week;

                return $row;
            });
    }

    /**
     * Todo lo que pinta la pantalla.
     *
     * @return array{kpis: array<string, array<string, mixed>>, total: int, other: int, weeks: array<int, array<string, mixed>>, series: array<string, array<string, mixed>>, ranking: array<int, array<string, mixed>>, details: array<string, int>}
     */
    public function summary(int $days, ?int $agentId = null): array
    {
        $rows = $this->aggregate($days, $agentId);

        $kpis = [];
        foreach (self::METRICS as $key => $meta) {
            $of = $rows->where('metric', $key);
            $kpis[$key] = $meta + [
                'key' => $key,
                'count' => (int) $of->sum('n'),
                'amount' => $meta['money'] ? round((float) $of->sum(fn ($r) => (float) $r->amount), 2) : null,
                'with_amount' => (int) $of->sum('with_amount'),
            ];
        }

        return [
            'kpis' => $kpis,
            'total' => (int) $rows->sum('n'),
            'other' => (int) $rows->where('metric', self::OTHER)->sum('n'),
            'weeks' => $this->weeks($days),
            'series' => $this->series($rows, $days),
            'ranking' => $this->ranking($rows),
            'details' => $this->details($days, $agentId),
        ];
    }

    /**
     * Desgloses que no caben en el agregado principal: vales de porcentaje
     * (sin importe), devoluciones completadas/denegadas y de dónde vienen
     * los cambios de dirección.
     *
     * @return array<string, int>
     */
    private function details(int $days, ?int $agentId): array
    {
        $states = (array) config('helpdeskprestashop.ext.refunds.rma_states', []);
        $completed = (int) ($states['completed'] ?? 5);
        $denied = (int) ($states['denied'] ?? 4);
        $percent = "JSON_UNQUOTE(JSON_EXTRACT(properties, '$.percent'))";
        $stateId = "CAST(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.state_id')) AS UNSIGNED)";

        $row = $this->periodQuery($days, $agentId)
            ->toBase()
            ->selectRaw("SUM(CASE WHEN description = 'ps.voucher.duplicated' AND {$percent} IS NOT NULL AND {$percent} <> 'null' AND {$percent} <> '' THEN 1 ELSE 0 END) AS vouchers_percent")
            ->selectRaw("SUM(CASE WHEN description = 'ps.voucher.duplicated' THEN 1 ELSE 0 END) AS vouchers_duplicated")
            ->selectRaw("SUM(CASE WHEN description = 'ps.rma.state_changed' AND {$stateId} = ? THEN 1 ELSE 0 END) AS returns_completed", [$completed])
            ->selectRaw("SUM(CASE WHEN description = 'ps.rma.state_changed' AND {$stateId} = ? THEN 1 ELSE 0 END) AS returns_denied", [$denied])
            ->selectRaw("SUM(CASE WHEN description = 'ps.rma.state_changed' THEN 1 ELSE 0 END) AS returns_changes")
            ->selectRaw("SUM(CASE WHEN description = 'ps.orders.address' THEN 1 ELSE 0 END) AS addresses_order")
            ->selectRaw("SUM(CASE WHEN description = 'ps.cart.address' THEN 1 ELSE 0 END) AS addresses_cart")
            ->selectRaw("SUM(CASE WHEN description IN ('ps.address.updated', 'ps.addresses.update') THEN 1 ELSE 0 END) AS addresses_book")
            ->first();

        return array_map('intval', (array) $row);
    }

    /**
     * Lunes de cada semana del periodo, del más antiguo al actual.
     *
     * @return array<int, array{key: string, label: string, range: string}>
     */
    public function weeks(int $days): array
    {
        $cursor = $this->since($days)->startOfWeek(Carbon::MONDAY);
        $last = now()->startOfWeek(Carbon::MONDAY);
        $weeks = [];

        while ($cursor->lte($last)) {
            $end = $cursor->copy()->addDays(6);
            $weeks[] = [
                'key' => $cursor->toDateString(),
                'label' => $cursor->format('d/m'),
                'range' => 'Semana del '.$cursor->format('d/m').' al '.$end->format('d/m/Y'),
            ];
            $cursor->addWeek();
        }

        return $weeks;
    }

    /**
     * Una serie por métrica (más «Todas las acciones») con la altura de cada
     * barra ya resuelta a una clase .psc-h-N (sin style="").
     *
     * @return array<string, array{label: string, max: int, bars: array<int, array<string, mixed>>}>
     */
    private function series(Collection $rows, int $days): array
    {
        $weeks = $this->weeks($days);
        $series = ['all' => ['label' => 'Todas las acciones', 'rows' => $rows]];
        foreach (self::METRICS as $key => $meta) {
            $series[$key] = ['label' => $meta['label'], 'rows' => $rows->where('metric', $key)];
        }

        $out = [];
        foreach ($series as $key => $def) {
            $byWeek = $def['rows']->groupBy('week')->map(fn ($g) => (int) $g->sum('n'));
            $max = (int) ($byWeek->max() ?? 0);
            $bars = [];
            foreach ($weeks as $week) {
                $n = (int) ($byWeek[$week['key']] ?? 0);
                $bars[] = [
                    'label' => $week['label'],
                    'range' => $week['range'],
                    'n' => $n,
                    'height' => $this->heightClass($n, $max),
                    'tone' => $n > 0 && $n === $max ? 'is-top' : ($max > 0 && $n * 2 >= $max ? 'is-mid' : ''),
                ];
            }
            $out[$key] = ['label' => $def['label'], 'max' => $max, 'total' => (int) $def['rows']->sum('n'), 'bars' => $bars];
        }

        return $out;
    }

    /**
     * Altura de la barra en tramos de 5 % (clases .psc-h-N de
     * prestashop-chat.css); una semana con alguna acción nunca queda a 0.
     */
    public function heightClass(int $n, int $max): string
    {
        if ($n <= 0 || $max <= 0) {
            return 'psc-metrics-h0';
        }

        $pct = (int) (round($n / $max * 20) * 5);

        return 'psc-h-'.max(5, min(100, $pct));
    }

    /**
     * Ranking por agente: acciones por métrica e importes, ordenado por
     * total de acciones contra la tienda.
     *
     * @return array<int, array<string, mixed>>
     */
    private function ranking(Collection $rows): array
    {
        $userType = (new User)->getMorphClass();
        $byAgent = $rows->groupBy(fn ($r) => $r->causer_type === $userType && $r->causer_id ? (string) $r->causer_id : 'system');
        $names = $this->userNames($byAgent->keys()->reject(fn ($k) => $k === 'system')->map(fn ($k) => (int) $k));

        return $byAgent->map(function (Collection $group, string $key) use ($names) {
            $metrics = [];
            foreach (self::METRICS as $metric => $meta) {
                $of = $group->where('metric', $metric);
                $metrics[$metric] = [
                    'count' => (int) $of->sum('n'),
                    'amount' => $meta['money'] ? round((float) $of->sum(fn ($r) => (float) $r->amount), 2) : null,
                ];
            }

            return [
                'id' => $key === 'system' ? null : (int) $key,
                'name' => $key === 'system' ? 'Sistema' : ($names[(int) $key] ?? 'Usuario #'.$key),
                'metrics' => $metrics,
                'other' => (int) $group->where('metric', self::OTHER)->sum('n'),
                'total' => (int) $group->sum('n'),
            ];
        })
            ->sortBy([['total', 'desc'], ['name', 'asc']])
            ->values()
            ->all();
    }

    /**
     * Agentes con acciones en el periodo (para el filtro).
     *
     * @return array<int, string>
     */
    public function agentOptions(int $days): array
    {
        $ids = $this->periodQuery($days)
            ->where('causer_type', (new User)->getMorphClass())
            ->distinct()
            ->pluck('causer_id')
            ->filter()
            ->map(fn ($id) => (int) $id);

        return $this->userNames($ids)->sort(fn ($a, $b) => strcasecmp($a, $b))->all();
    }

    /**
     * Acciones contadas en alguna métrica, una a una, para el CSV de detalle.
     * lazyById: no carga el log entero en memoria.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    public function detailRows(int $days, ?int $agentId, int $limit): \Generator
    {
        [$metricSql, $metricBindings] = $this->metricCaseSql();
        [$amountSql, $amountBindings] = $this->amountSql();

        $query = $this->periodQuery($days, $agentId)->toBase()
            ->select(['id', 'description', 'causer_type', 'causer_id', 'subject_id', 'properties', 'created_at'])
            ->selectRaw("{$metricSql} AS metric", $metricBindings)
            ->selectRaw("{$amountSql} AS amount", $amountBindings)
            ->orderByDesc('id')
            ->limit($limit);

        $userType = (new User)->getMorphClass();
        $names = collect();

        foreach ($query->cursor() as $row) {
            if ($row->metric === self::OTHER) {
                continue;
            }
            $agentName = 'Sistema';
            if ($row->causer_type === $userType && $row->causer_id) {
                $id = (int) $row->causer_id;
                if (! $names->has($id)) {
                    $names = $names->union($this->userNames(collect([$id])));
                }
                $agentName = $names[$id] ?? 'Usuario #'.$id;
            }

            $props = json_decode((string) $row->properties, true) ?: [];
            $params = (array) ($props['params'] ?? []);

            yield [
                'at' => Carbon::parse($row->created_at)->format('Y-m-d H:i:s'),
                'agent' => $agentName,
                'metric' => self::METRICS[$row->metric]['label'] ?? $row->metric,
                'action' => (string) $row->description,
                'amount' => $row->amount !== null ? (float) $row->amount : null,
                'order' => $params['order'] ?? $props['order_id'] ?? null,
                'cart' => $params['cart'] ?? $props['cart_id'] ?? null,
                'code' => $props['code'] ?? $props['voucher_code'] ?? null,
                'customer_id' => $row->subject_id,
                'conversation_id' => $props['conversation_id'] ?? null,
            ];
        }
    }

    /**
     * @param  Collection<int, int>  $ids
     * @return Collection<int, string>
     */
    public function userNames(Collection $ids): Collection
    {
        $ids = $ids->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $ids->all())
            ->get(['id', 'firstname', 'lastname', 'email'])
            ->mapWithKeys(fn (User $u) => [$u->id => $u->fullName() ?: (string) $u->email]);
    }

    /**
     * Estados de RMA que cierran la devolución: completada y denegada.
     *
     * @return array<int, int>
     */
    public function resolvedReturnStates(): array
    {
        $states = (array) config('helpdeskprestashop.ext.refunds.rma_states', []);

        return array_values(array_unique(array_filter([
            (int) ($states['completed'] ?? 5),
            (int) ($states['denied'] ?? 4),
        ])));
    }

    public static function money(?float $amount): string
    {
        return number_format((float) $amount, 2, ',', '.').' €';
    }
}

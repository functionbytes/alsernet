<?php

namespace Modules\HelpdeskErp\Services\ErpAdmin;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;
use Modules\HelpdeskErp\Models\ErpAdminMetricEvent;

/**
 * «Métricas de Gestión»: uso del panel de Gestión (ERP) en el chat, leído de
 * helpdesk_erp_metrics_events. Cada número sale de una fila; nada se estima.
 */
class ErpAdminMetricsService
{
    /** Kinds que son peticiones del panel (no llamadas al manager). */
    public const PANEL_KINDS = ['overview', 'section', 'order', 'delivery_note', 'invoice'];

    public const KIND_LABELS = [
        'overview' => 'Resumen abierto',
        'section' => 'Sección consultada',
        'order' => 'Pedido abierto',
        'delivery_note' => 'Albarán abierto',
        'invoice' => 'Factura abierta',
        'manager_call' => 'Llamada al manager',
    ];

    public const SECTION_LABELS = [
        'summary' => 'Resumen', 'personal' => 'Datos personales', 'contact' => 'Contacto', 'catalogs' => 'Catálogos',
        'addresses' => 'Direcciones', 'quotas' => 'Cuotas', 'cards' => 'Tarjetas', 'accounts' => 'Cuentas',
        'orders' => 'Pedidos', 'delivery-notes' => 'Albaranes', 'returns' => 'Devoluciones', 'invoices' => 'Facturas',
        'payments' => 'Cobros', 'debts' => 'Deudas', 'balance' => 'Saldo', 'vouchers' => 'Vales',
        'bonuses' => 'Bonos', 'loyalty-points' => 'Puntos',
    ];

    public function ready(): bool
    {
        try {
            return Schema::connection('helpdesk')->hasTable('helpdesk_erp_metrics_events');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<int>
     */
    public function periods(): array
    {
        $periods = array_values(array_filter(array_map('intval', (array) config('helpdeskErp.ext.admin.metrics.periods', [7, 30, 90])), fn ($d) => $d > 0));

        return $periods ?: [7, 30, 90];
    }

    public function defaultPeriod(): int
    {
        $periods = $this->periods();

        return $periods[1] ?? $periods[0];
    }

    public function since(int $days): Carbon
    {
        return now()->subDays($days - 1)->startOfDay();
    }

    public function query(int $days, ?int $agentId = null): Builder
    {
        $query = ErpAdminMetricEvent::query()->where('occurred_at', '>=', $this->since($days));

        if ($agentId) {
            $query->where('user_id', $agentId);
        }

        return $query;
    }

    /**
     * @return array<string, int|float|null>
     */
    public function kpis(int $days, ?int $agentId = null): array
    {
        $row = $this->query($days, $agentId)->toBase()
            ->selectRaw("SUM(kind = 'overview') AS overview")
            ->selectRaw("SUM(kind = 'order') AS orders")
            ->selectRaw("SUM(kind IN ('delivery_note', 'invoice')) AS documents")
            ->selectRaw("SUM(kind = 'section') AS sections")
            ->selectRaw("SUM(CASE WHEN kind <> 'manager_call' THEN blocked_count ELSE 0 END) AS blocked")
            ->selectRaw("SUM(kind <> 'manager_call' AND state = 'down') AS panel_down")
            ->selectRaw("SUM(kind = 'manager_call' AND state = 'down') AS manager_down")
            ->selectRaw("SUM(kind = 'manager_call' AND state = 'error') AS manager_errors")
            ->selectRaw("SUM(kind = 'manager_call') AS manager_calls")
            ->selectRaw("SUM(kind = 'overview' AND state = 'unlinked') AS unlinked_opens")
            ->selectRaw("COUNT(DISTINCT CASE WHEN kind = 'overview' AND state = 'unlinked' THEN customer_id END) AS unlinked_customers")
            ->selectRaw("COUNT(DISTINCT CASE WHEN kind <> 'manager_call' THEN user_id END) AS agents")
            ->selectRaw("COUNT(DISTINCT CASE WHEN kind <> 'manager_call' THEN customer_id END) AS customers")
            ->first();

        $manager = $this->percentiles($this->query($days, $agentId)->where('kind', 'manager_call')->where('state', 'ok'));
        $panel = $this->percentiles($this->query($days, $agentId)->whereIn('kind', self::PANEL_KINDS));

        return [
            'overview' => (int) ($row->overview ?? 0),
            'orders' => (int) ($row->orders ?? 0),
            'documents' => (int) ($row->documents ?? 0),
            'sections' => (int) ($row->sections ?? 0),
            'blocked' => (int) ($row->blocked ?? 0),
            'down' => (int) ($row->panel_down ?? 0) + (int) ($row->manager_down ?? 0),
            'panel_down' => (int) ($row->panel_down ?? 0),
            'manager_down' => (int) ($row->manager_down ?? 0),
            'manager_errors' => (int) ($row->manager_errors ?? 0),
            'manager_calls' => (int) ($row->manager_calls ?? 0),
            'unlinked_opens' => (int) ($row->unlinked_opens ?? 0),
            'unlinked_customers' => (int) ($row->unlinked_customers ?? 0),
            'agents' => (int) ($row->agents ?? 0),
            'customers' => (int) ($row->customers ?? 0),
            'manager_p50' => $manager['p50'],
            'manager_p95' => $manager['p95'],
            'panel_p50' => $panel['p50'],
            'panel_p95' => $panel['p95'],
        ];
    }

    /**
     * Serie diaria (todos los días del periodo, también los vacíos).
     *
     * @return list<array{date: string, label: string, overview: int, orders: int, sections: int, down: int, total: int}>
     */
    public function daily(int $days, ?int $agentId = null): array
    {
        $rows = $this->query($days, $agentId)->toBase()
            ->selectRaw('DATE(occurred_at) AS d')
            ->selectRaw("SUM(kind = 'overview') AS overview")
            ->selectRaw("SUM(kind IN ('order', 'delivery_note', 'invoice')) AS orders")
            ->selectRaw("SUM(kind = 'section') AS sections")
            ->selectRaw("SUM(state = 'down') AS down")
            ->groupBy(DB::raw('DATE(occurred_at)'))
            ->get()
            ->keyBy(fn ($r) => (string) $r->d);

        $out = [];
        $day = $this->since($days)->copy();
        $today = now()->startOfDay();
        while ($day->lte($today)) {
            $key = $day->toDateString();
            $r = $rows->get($key);
            $item = [
                'date' => $key,
                'label' => $day->format('d/m'),
                'overview' => (int) ($r->overview ?? 0),
                'orders' => (int) ($r->orders ?? 0),
                'sections' => (int) ($r->sections ?? 0),
                'down' => (int) ($r->down ?? 0),
            ];
            $item['total'] = $item['overview'] + $item['orders'] + $item['sections'];
            $out[] = $item;
            $day->addDay();
        }

        return $out;
    }

    /**
     * Secciones consultadas (peticiones de sección) con su reparto de estados.
     *
     * @return list<array{section: string, label: string, total: int, ok: int, blocked: int, down: int, other: int, avg_ms: ?int}>
     */
    public function sections(int $days, ?int $agentId = null): array
    {
        return $this->query($days, $agentId)->toBase()
            ->where('kind', 'section')
            ->selectRaw('section')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(state = 'ok') AS ok")
            ->selectRaw("SUM(state = 'blocked') AS blocked")
            ->selectRaw("SUM(state = 'down') AS down")
            ->selectRaw('AVG(duration_ms) AS avg_ms')
            ->groupBy('section')
            ->orderByDesc('total')
            ->get()
            ->map(function ($r) {
                $total = (int) $r->total;
                $ok = (int) $r->ok;
                $blocked = (int) $r->blocked;
                $down = (int) $r->down;

                return [
                    'section' => (string) $r->section,
                    'label' => self::SECTION_LABELS[(string) $r->section] ?? (string) $r->section,
                    'total' => $total,
                    'ok' => $ok,
                    'blocked' => $blocked,
                    'down' => $down,
                    'other' => max(0, $total - $ok - $blocked - $down),
                    'avg_ms' => $r->avg_ms !== null ? (int) round((float) $r->avg_ms) : null,
                ];
            })
            ->all();
    }

    /**
     * Tiempos del manager por recurso llamado.
     *
     * @return list<array{section: string, label: string, calls: int, down: int, errors: int, p50: ?int, p95: ?int}>
     */
    public function managerResources(int $days, ?int $agentId = null): array
    {
        $rows = $this->query($days, $agentId)->toBase()
            ->where('kind', 'manager_call')
            ->selectRaw('section')
            ->selectRaw('COUNT(*) AS calls')
            ->selectRaw("SUM(state = 'down') AS down")
            ->selectRaw("SUM(state = 'error') AS errors")
            ->groupBy('section')
            ->orderByDesc('calls')
            ->limit(30)
            ->get();

        return $rows->map(function ($r) use ($days, $agentId) {
            $p = $this->percentiles($this->query($days, $agentId)->where('kind', 'manager_call')->where('state', 'ok')->where('section', $r->section));
            $section = (string) ($r->section ?? '');

            return [
                'section' => $section,
                'label' => self::SECTION_LABELS[$section] ?? ($section !== '' ? $section : 'Otros'),
                'calls' => (int) $r->calls,
                'down' => (int) $r->down,
                'errors' => (int) $r->errors,
                'p50' => $p['p50'],
                'p95' => $p['p95'],
            ];
        })->all();
    }

    /**
     * Uso por agente.
     *
     * @return list<array{user_id: int, name: string, overview: int, orders: int, sections: int, blocked: int, down: int, last_at: ?string}>
     */
    public function agents(int $days, ?int $agentId = null): array
    {
        $rows = $this->query($days, $agentId)->toBase()
            ->whereIn('kind', self::PANEL_KINDS)
            ->whereNotNull('user_id')
            ->selectRaw('user_id')
            ->selectRaw("SUM(kind = 'overview') AS overview")
            ->selectRaw("SUM(kind IN ('order', 'delivery_note', 'invoice')) AS orders")
            ->selectRaw("SUM(kind = 'section') AS sections")
            ->selectRaw('SUM(blocked_count) AS blocked')
            ->selectRaw("SUM(state = 'down') AS down")
            ->selectRaw('MAX(occurred_at) AS last_at')
            ->groupBy('user_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(200)
            ->get();

        $names = $this->userNames($rows->pluck('user_id')->map(fn ($id) => (int) $id)->all());

        return $rows->map(fn ($r) => [
            'user_id' => (int) $r->user_id,
            'name' => $names[(int) $r->user_id] ?? 'Agente #'.$r->user_id,
            'overview' => (int) $r->overview,
            'orders' => (int) $r->orders,
            'sections' => (int) $r->sections,
            'blocked' => (int) $r->blocked,
            'down' => (int) $r->down,
            'last_at' => $r->last_at !== null ? (string) $r->last_at : null,
        ])->all();
    }

    /**
     * Agentes con actividad en el periodo, para el filtro.
     *
     * @return array<int, string>
     */
    public function agentOptions(int $days): array
    {
        $ids = $this->query($days)->whereNotNull('user_id')->distinct()->limit(500)->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        $names = $this->userNames($ids);
        asort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }

    /**
     * Foto actual de la vinculación de contactos del helpdesk con Gestión
     * (helpdesk_customers.erp_lookup_status). En caché 10 minutos.
     *
     * @return array{linked: int, not_found: int, error: int, pending: int}|null
     */
    public function linkSnapshot(): ?array
    {
        try {
            return Cache::remember('helpdeskerp:admin_metrics:link_snapshot', 600, function () {
                if (! Schema::connection('helpdesk')->hasColumn('helpdesk_customers', 'erp_lookup_status')) {
                    return null;
                }

                $counts = DB::connection('helpdesk')->table('helpdesk_customers')
                    ->selectRaw('erp_lookup_status AS s, COUNT(*) AS n')
                    ->groupBy('erp_lookup_status')
                    ->pluck('n', 's');

                return [
                    'linked' => (int) ($counts['linked'] ?? 0),
                    'not_found' => (int) ($counts['not_found'] ?? 0),
                    'error' => (int) ($counts['error'] ?? 0),
                    'pending' => (int) ($counts[''] ?? 0),
                ];
            });
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Filas agregadas para el CSV: día × agente × tipo × sección × estado.
     *
     * @return LazyCollection<int, object>
     */
    public function exportRows(int $days, ?int $agentId = null)
    {
        return $this->query($days, $agentId)->toBase()
            ->selectRaw('DATE(occurred_at) AS d, user_id, kind, section, state')
            ->selectRaw('COUNT(*) AS n, SUM(blocked_count) AS blocked, AVG(duration_ms) AS avg_ms, MAX(duration_ms) AS max_ms')
            ->groupBy(DB::raw('DATE(occurred_at)'), 'user_id', 'kind', 'section', 'state')
            ->orderBy('d')
            ->orderBy('user_id')
            ->cursor();
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    public function userNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return [];
        }

        $names = [];
        foreach (User::query()->whereIn('id', $ids)->get() as $user) {
            $name = method_exists($user, 'fullName') ? trim((string) $user->fullName()) : '';
            $names[(int) $user->id] = $name !== '' ? $name : (string) $user->email;
        }

        return $names;
    }

    /**
     * p50 y p95 (ms) de duration_ms, por el método del rango más cercano.
     *
     * @return array{p50: ?int, p95: ?int}
     */
    public function percentiles(Builder $query): array
    {
        $sample = max(100, (int) config('helpdeskErp.ext.admin.metrics.percentile_sample', 50000));

        $values = (clone $query)->whereNotNull('duration_ms')
            ->orderByDesc('id')
            ->limit($sample)
            ->pluck('duration_ms')
            ->map(fn ($v) => (int) $v)
            ->sort()
            ->values()
            ->all();

        return ['p50' => self::percentile($values, 50), 'p95' => self::percentile($values, 95)];
    }

    /**
     * @param  list<int>  $sorted
     */
    public static function percentile(array $sorted, int $p): ?int
    {
        $n = count($sorted);
        if ($n === 0) {
            return null;
        }

        $rank = (int) ceil($p / 100 * $n);

        return $sorted[max(0, min($n - 1, $rank - 1))];
    }

    /**
     * Clase de anchura (.era-w-0 … .era-w-100, en pasos de 5) para barras
     * sin estilos en línea.
     */
    public static function widthClass(int|float $value, int|float $max): string
    {
        if ($max <= 0 || $value <= 0) {
            return 'era-w-0';
        }

        $pct = (int) (ceil(($value / $max) * 20) * 5);

        return 'era-w-'.max(5, min(100, $pct));
    }

    public static function heightClass(int|float $value, int|float $max): string
    {
        return str_replace('era-w-', 'era-h-', self::widthClass($value, $max));
    }

    public static function ms(?int $ms): string
    {
        if ($ms === null) {
            return '—';
        }

        return $ms >= 1000
            ? number_format($ms / 1000, $ms >= 10000 ? 0 : 1, ',', '.').' s'
            : $ms.' ms';
    }
}

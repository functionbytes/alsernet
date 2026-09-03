<?php

namespace Modules\HelpdeskEmailActivity\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskEmailActivity\Models\EmailLog;

/**
 * Tres agregados de analítica para emails/analytics.blade.php, todos
 * calculados en SQL (GROUP BY / SUM condicional / percentiles nativos de
 * MariaDB) para que sigan siendo baratos según crece email_logs — nunca se
 * cargan filas completas a PHP para agregarlas aquí.
 *
 * Cada bloque se cachea por rango de fechas (TTL corto, sin invalidación
 * proactiva — mismo criterio de bajo riesgo que
 * EmailLogController::recipientStats(), que tampoco invalida activamente):
 * tolerable un desfase de unos minutos en un dashboard de analítica, y el
 * rango por defecto (últimos 30 días) es, con diferencia, el más pedido, así
 * que acota el espacio de claves en la práctica.
 */
class EmailLogAnalyticsService
{
    /** Ventana por defecto cuando la request no fija un rango explícito. */
    public const DEFAULT_WINDOW_DAYS = 30;

    /** Dominios individuales a mostrar antes de agregar el resto como "otros". */
    public const DOMAIN_LIMIT = 20;

    private const CACHE_TTL_SECONDS = 120;

    /**
     * Misma expresión JSON que genera Eloquent para
     * `where('metadata->open_tracking_enabled', true)` (ver
     * EmailLogController::computeStats()) — aquí no puede usarse el scope
     * openTracked() porque el flag va dentro de un SUM condicional agrupado
     * por mailable_class, no como WHERE de la query completa.
     *
     * Apunta a la columna generada e indexada, no a la expresión JSON: son
     * equivalentes por construcción (ver la migración
     * ..._add_tracking_flag_columns_to_email_logs) pero la primera no obliga
     * a deserializar el JSON de cada fila del grupo.
     */
    private const OPEN_TRACKING_FLAG = 'email_logs.open_tracking_enabled = 1';

    /**
     * Envíos/entrega/rebote/apertura por mailable_class, ordenado por
     * volumen.
     *
     * - delivery_rate y bounce_rate: numerador/total (mismo criterio que el
     *   KPI global de EmailLogController::rateFrom() — nunca sobre el
     *   subconjunto "terminal" que usa EmailLog::reputationStats()).
     * - open_rate: numerador/open_tracked, NUNCA numerador/total — la
     *   inmensa mayoría de envíos no llevan píxel de seguimiento
     *   (EmailLog::hasOpenTracking()), así que dividir por el total
     *   infravaloraría la tasa real para los que sí lo tenían.
     *
     * @return Collection<int, array{mailable_class: ?string, sends: int, delivery_rate: ?float, bounce_rate: ?float, open_tracked: int, opened: int, open_rate: ?float}>
     */
    public function mailablePerformance(Carbon $from, Carbon $to): Collection
    {
        return Cache::remember(
            $this->cacheKey('mailables', $from, $to),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            fn (): Collection => $this->computeMailablePerformance($from, $to),
        );
    }

    /**
     * @return Collection<int, array{mailable_class: ?string, sends: int, delivery_rate: ?float, bounce_rate: ?float, open_tracked: int, opened: int, open_rate: ?float}>
     */
    private function computeMailablePerformance(Carbon $from, Carbon $to): Collection
    {
        // DISTINCT en subquery en vez de whereHas('opens') dentro del SUM
        // condicional: un LEFT JOIN a esta lista permite comprobar "¿esta
        // fila tiene aperturas?" con una comparación IS NOT NULL barata, sin
        // una subconsulta correlacionada por cada fila de email_logs.
        $openedLogIds = DB::table('email_log_opens')->select('email_log_id')->distinct();

        $rows = EmailLog::query()
            ->leftJoinSub($openedLogIds, 'eo', 'eo.email_log_id', '=', 'email_logs.id')
            ->whereBetween('email_logs.created_at', [$from, $to])
            ->selectRaw('email_logs.mailable_class as mailable_class')
            ->selectRaw('COUNT(*) as sends')
            ->selectRaw("SUM(email_logs.status = 'sent') as sent")
            ->selectRaw("SUM(email_logs.status = 'bounced') as bounced")
            ->selectRaw('SUM('.self::OPEN_TRACKING_FLAG.') as open_tracked')
            ->selectRaw('SUM('.self::OPEN_TRACKING_FLAG.' AND eo.email_log_id IS NOT NULL) as opened')
            ->groupBy('email_logs.mailable_class')
            ->orderByDesc('sends')
            ->get();

        return $rows->map(function (EmailLog $row): array {
            $sends = (int) $row->getAttribute('sends');
            $openTracked = (int) $row->getAttribute('open_tracked');
            $opened = (int) $row->getAttribute('opened');

            return [
                'mailable_class' => $row->mailable_class,
                'sends' => $sends,
                'delivery_rate' => $this->rate((int) $row->getAttribute('sent'), $sends),
                'bounce_rate' => $this->rate((int) $row->getAttribute('bounced'), $sends),
                'open_tracked' => $openTracked,
                'opened' => $opened,
                'open_rate' => $this->rate($opened, $openTracked),
            ];
        })->values();
    }

    /**
     * p50/p95/máximo (en segundos) del tiempo entre created_at y
     * delivered_at, solo sobre envíos con delivered_at no nulo — la mayoría
     * de envíos hoy nunca lo tienen (delivered_at solo lo confirma un
     * webhook de proveedor, ver EmailDeliveryEventCorrelatorService), así
     * que sample_size puede ser 0 sin que eso sea un error.
     *
     * Percentiles calculados en SQL (PERCENTILE_CONT, disponible en MariaDB
     * 10.3+) sobre una subquery que SOLO trae la diferencia en segundos, no
     * las filas completas.
     *
     * @return array{sample_size: int, p50_seconds: ?float, p95_seconds: ?float, max_seconds: ?int}
     */
    public function deliveryLatency(Carbon $from, Carbon $to): array
    {
        return Cache::remember(
            $this->cacheKey('latency', $from, $to),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            fn (): array => $this->computeDeliveryLatency($from, $to),
        );
    }

    /**
     * @return array{sample_size: int, p50_seconds: ?float, p95_seconds: ?float, max_seconds: ?int}
     */
    private function computeDeliveryLatency(Carbon $from, Carbon $to): array
    {
        $diffs = EmailLog::query()
            ->whereNotNull('delivered_at')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('TIMESTAMPDIFF(SECOND, created_at, delivered_at) as diff_seconds');

        // Query builder plano (no EmailLog::query()) para la consulta
        // externa: envolver la subquery con el propio Eloquent Builder del
        // modelo repetiría el global scope de SoftDeletes calificado como
        // `email_logs`.`deleted_at`, columna que no existe en el alias `t`
        // de la subquery.
        $row = EmailLog::query()->getConnection()->query()
            ->fromSub($diffs, 't')
            ->selectRaw('COUNT(*) OVER () as sample_size')
            ->selectRaw('PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY diff_seconds) OVER () as p50')
            ->selectRaw('PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY diff_seconds) OVER () as p95')
            ->selectRaw('MAX(diff_seconds) OVER () as max_seconds')
            ->limit(1)
            ->first();

        // Sin filas en absoluto (sample_size 0): las funciones ventana no
        // producen ninguna fila de salida sobre un conjunto vacío, así que
        // $row es null en vez de una fila con columnas en 0 — se traduce
        // aquí a un estado vacío honesto, nunca a ceros inventados.
        if ($row === null) {
            return ['sample_size' => 0, 'p50_seconds' => null, 'p95_seconds' => null, 'max_seconds' => null];
        }

        return [
            'sample_size' => (int) $row->sample_size,
            'p50_seconds' => $row->p50 !== null ? round((float) $row->p50, 1) : null,
            'p95_seconds' => $row->p95 !== null ? round((float) $row->p95, 1) : null,
            'max_seconds' => $row->max_seconds !== null ? (int) $row->max_seconds : null,
        ];
    }

    /**
     * Volumen/entrega/rebote/queja agrupado por el dominio del destinatario
     * PRIMARIO de cada envío (mismo criterio que
     * EmailLogController::recipientStats()/relatedEmails(), que ya tratan
     * to_addresses[0] como "el" destinatario — un email_log no distingue
     * qué destinatario concreto rebotó/entregó cuando hay varios).
     *
     * El dominio se extrae de recipients_index en vez de explotar el JSON
     * de to_addresses en SQL: recipients_index concatena
     * to+cc+bcc en ESE orden separados por espacio (ver EmailLog::booting()),
     * así que su primer token es siempre el mismo valor que to_addresses[0].
     *
     * Los `$limit` dominios de mayor volumen se listan aparte; el resto se
     * agrega en una única fila "otros" (mismo criterio de no sepultar la
     * pantalla que ya aplicó EmailReputationController::suggestedDomains(),
     * pero aquí como un total combinado en vez de una lista colapsable).
     *
     * @return array{domains: Collection<int, array{domain: string, volume: int, delivery_rate: ?float, bounce_rate: ?float, complaint_rate: ?float}>, others: ?array{domain: null, volume: int, delivery_rate: ?float, bounce_rate: ?float, complaint_rate: ?float}, total_domains: int}
     */
    public function domainDeliverability(Carbon $from, Carbon $to, int $limit = self::DOMAIN_LIMIT): array
    {
        return Cache::remember(
            $this->cacheKey("domains:{$limit}", $from, $to),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            fn (): array => $this->computeDomainDeliverability($from, $to, $limit),
        );
    }

    /**
     * @return array{domains: Collection<int, array{domain: string, volume: int, delivery_rate: ?float, bounce_rate: ?float, complaint_rate: ?float}>, others: ?array{domain: null, volume: int, delivery_rate: ?float, bounce_rate: ?float, complaint_rate: ?float}, total_domains: int}
     */
    private function computeDomainDeliverability(Carbon $from, Carbon $to, int $limit): array
    {
        $domainExpr = "LOWER(SUBSTRING_INDEX(SUBSTRING_INDEX(recipients_index, ' ', 1), '@', -1))";

        $rows = EmailLog::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('recipients_index')
            ->where('recipients_index', '!=', '')
            // Descarta filas sin ningún destinatario real (recipients_index
            // vacío tras el filter() de booting()) — sin un '@' presente,
            // SUBSTRING_INDEX(..., '@', -1) devolvería la cadena entera tal
            // cual en vez de un dominio.
            ->where('recipients_index', 'like', '%@%')
            ->selectRaw("{$domainExpr} as domain")
            ->selectRaw('COUNT(*) as volume')
            ->selectRaw("SUM(status = 'sent') as sent")
            ->selectRaw("SUM(status = 'bounced') as bounced")
            ->selectRaw("SUM(status = 'complained') as complained")
            ->groupBy('domain')
            ->orderByDesc('volume')
            ->get();

        $top = $rows->take($limit);
        $rest = $rows->slice($limit);

        return [
            'domains' => $top->map(fn (EmailLog $row): array => $this->formatDomainRow($row))->values(),
            'others' => $rest->isEmpty() ? null : $this->formatDomainRow((object) [
                'domain' => null,
                'volume' => $rest->sum('volume'),
                'sent' => $rest->sum('sent'),
                'bounced' => $rest->sum('bounced'),
                'complained' => $rest->sum('complained'),
            ]),
            'total_domains' => $rows->count(),
        ];
    }

    /**
     * @return array{domain: ?string, volume: int, delivery_rate: ?float, bounce_rate: ?float, complaint_rate: ?float}
     */
    private function formatDomainRow(object $row): array
    {
        $volume = (int) $row->volume;

        return [
            'domain' => $row->domain,
            'volume' => $volume,
            'delivery_rate' => $this->rate((int) $row->sent, $volume),
            'bounce_rate' => $this->rate((int) $row->bounced, $volume),
            'complaint_rate' => $this->rate((int) $row->complained, $volume),
        ];
    }

    /**
     * Tasa porcentual redondeada a 1 decimal, o null si no hay denominador —
     * mismo criterio de honestidad que EmailLogController::rateFrom(): "sin
     * datos" en vez de un 0% que insinuaría un intento fallido/una tasa real
     * de cero.
     */
    private function rate(int $numerator, int $denominator): ?float
    {
        return $denominator > 0 ? round($numerator / $denominator * 100, 1) : null;
    }

    private function cacheKey(string $suffix, Carbon $from, Carbon $to): string
    {
        return "helpdeskemailactivity:analytics:{$suffix}:{$from->toDateString()}:{$to->toDateString()}";
    }
}

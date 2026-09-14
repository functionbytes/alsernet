<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Modules\HelpdeskTickets\Models\TicketMail;

/**
 * La bandeja de correo del helpdesk: qué filas se ven y los KPIs de cabecera.
 *
 * Vivía dentro de TicketMailsController, que había llegado a 1.034 líneas para
 * catorce endpoints. Filtrar el listado y calcular las tasas de apertura son
 * dos cosas que no dependen de la petición HTTP más allá de sus parámetros, y
 * sacarlas deja el controlador en lo suyo: validar, llamar y responder.
 *
 * Sin cambios de comportamiento: mismas consultas, misma caché y misma clave.
 */
class TicketMailInboxService
{
    /**
     * KPIs de cabecera (index()/refetch tras enviar) — no son datos
     * transaccionales, así que se cachean unos segundos. Las acciones que los
     * mueven (enviar, reenviar, borrar, acciones masivas) invalidan esta clave
     * explícitamente: el refetch por AJAX que hace tickets-app.js justo después
     * ya tuvo un bug real (QA manual) por quedarse con los valores del primer
     * GET, y un TTL corto sin invalidar reintroduciría el mismo síntoma
     * durante la ventana de caché.
     */
    public const STATS_CACHE_KEY = 'helpdeskticketmails:stats';

    private const STATS_CACHE_TTL_SECONDS = 45;

    /**
     * Ventana considerada para opened_rate/avg_latency — evita escanear el
     * histórico completo de envíos solo para un KPI de cabecera.
     */
    private const OPEN_STATS_WINDOW_DAYS = 90;

    /**
     * El listado con sus filtros: pestaña, búsqueda, origen, etiqueta,
     * categoría, agente y rango de fechas.
     */
    public function filteredQuery(Request $request): Builder
    {
        $query = TicketMail::query()
            ->with([
                'ticket:id,ticket_number,subject,customer_id,source,category_id,assignee_id',
                'ticket.customer:id,name,email',
                'user:id,firstname,lastname',
                'category:id,name',
            ])
            ->latest();

        match ($request->string('view', 'outbound')->toString()) {
            'scheduled' => $query->scheduled(),
            'bounced' => $query->bounced(),
            'failed' => $query->failed(),
            'inbound' => $query->inbound(),
            // Internos: avisos que nunca salen a un cliente (p.ej. escalados
            // a soporte-n2@alvarez.mx) — tab propio, no mezclado con "Enviados".
            'internal' => $query->outbound()->internal(),
            default => $query->outbound()->where('status', '!=', 'scheduled')->where('is_internal', false),
        };

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($q) => $q
                ->where('subject', 'like', "%{$search}%")
                ->orWhere('to', 'like', "%{$search}%")
                ->orWhere('message_id', 'like', "%{$search}%")
                ->orWhereHas('ticket', fn ($t) => $t->where('ticket_number', 'like', "%{$search}%"))
            );
        }

        if ($request->filled('origin')) {
            $origin = $request->string('origin')->toString();
            $query->whereHas('ticket', fn ($t) => $t->where('source', $origin));
        }

        if ($request->filled('tag')) {
            $tag = $request->string('tag')->toString();
            $query->whereJsonContains('tags', $tag);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }

        if ($request->filled('agent')) {
            $query->where('user_id', $request->integer('agent'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from')->startOfDay());
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to')->endOfDay());
        }

        return $query;
    }

    /**
     * @return array{total: int, bounced: int, bounce_rate: float, scheduled: int, internal: int, opened_rate: float, clicked_rate: float, avg_latency: ?float, queue_waiting: int}
     */
    public function stats(): array
    {
        return Cache::remember(self::STATS_CACHE_KEY, self::STATS_CACHE_TTL_SECONDS, function () {
            $total = TicketMail::outbound()->where('is_internal', false)->count();
            $bounced = TicketMail::outbound()->where('is_internal', false)->bounced()->count();
            $scheduled = TicketMail::scheduled()->count();
            $internal = TicketMail::outbound()->internal()->count();

            $openTracking = $this->openTrackingStats();

            return [
                'total' => $total,
                'bounced' => $bounced,
                'bounce_rate' => $total > 0 ? round($bounced / $total * 100, 1) : 0.0,
                'scheduled' => $scheduled,
                'internal' => $internal,
                'opened_rate' => $openTracking['opened_rate'],
                'clicked_rate' => $openTracking['clicked_rate'],
                'avg_latency' => $openTracking['avg_latency'],
                'queue_waiting' => $this->queueWaiting(),
            ];
        });
    }

    public static function forgetStatsCache(): void
    {
        Cache::forget(self::STATS_CACHE_KEY);
    }

    /**
     * Tasa de apertura/clic y latencia media de envío de los últimos
     * self::OPEN_STATS_WINDOW_DAYS días, con una sola agregación en SQL
     * (JOIN + SUM/AVG) en vez de traer un EmailLog por cada TicketMail y
     * promediar en PHP — antes era un pluck('message_id') SIN ventana
     * temporal seguido de EmailLog::withCount('opens')->whereIn(...)
     * hidratando un modelo por id.
     *
     * Aperturas/clics y latencia salen de EmailLog (módulo HelpdeskEmailActivity),
     * cruzado por message_id — TicketMail no trackea nada de eso, sería
     * duplicar una fuente de verdad que ya existe. TicketMail.message_id
     * se guarda con los ángulos <...> (formato de cabecera RFC 5322);
     * EmailLog.message_id se guarda SIN ellos (ver LogEmailQueued::
     * ensureMessageId()) — sin el TRIM() de abajo el JOIN nunca encuentra
     * nada (bug real encontrado al probar el pixel en vivo). MySQL
     * TRIM(char FROM str) solo quita UN carácter por llamada (a diferencia
     * de PHP trim($id, '<>')), así que hacen falta dos TRIM anidados, uno
     * por cada símbolo del delimitador.
     *
     * Los clics se cuentan por EMAIL (COUNT DISTINCT email_log_link_id vía
     * el JOIN con email_log_links), no por hit — un mismo destinatario
     * clicando el mismo enlace 3 veces cuenta como "1 correo con clic", no
     * infla el numerador de clicked_rate.
     *
     * La conexión 'helpdesk' (TicketMail) y la conexión por defecto
     * (EmailLog) apuntan a la misma base de datos física en este entorno,
     * así que el JOIN entre ambas tablas es válido en una sola query.
     *
     * @return array{opened_rate: float, clicked_rate: float, avg_latency: ?float}
     */
    private function openTrackingStats(): array
    {
        $normalizedTicketMails = TicketMail::outbound()
            ->where('is_internal', false)
            ->whereNotNull('message_id')
            ->where('created_at', '>=', now()->subDays(self::OPEN_STATS_WINDOW_DAYS))
            ->selectRaw("id, TRIM(BOTH '>' FROM TRIM(BOTH '<' FROM message_id)) AS normalized_message_id");

        $openCounts = DB::table('email_log_opens')
            ->selectRaw('email_log_id, COUNT(*) AS opens_count')
            ->groupBy('email_log_id');

        $clickCounts = DB::table('email_log_clicks')
            ->join('email_log_links', 'email_log_links.id', '=', 'email_log_clicks.email_log_link_id')
            ->selectRaw('email_log_links.email_log_id, COUNT(*) AS clicks_count')
            ->groupBy('email_log_links.email_log_id');

        $row = DB::connection('helpdesk')
            ->query()
            ->fromSub($normalizedTicketMails, 'tm')
            ->join('email_logs', 'email_logs.message_id', '=', 'tm.normalized_message_id')
            ->leftJoinSub($openCounts, 'oc', 'oc.email_log_id', '=', 'email_logs.id')
            ->leftJoinSub($clickCounts, 'cc', 'cc.email_log_id', '=', 'email_logs.id')
            ->selectRaw(implode(', ', [
                'COUNT(*) AS matched',
                'SUM(CASE WHEN oc.opens_count > 0 THEN 1 ELSE 0 END) AS opened',
                'SUM(CASE WHEN cc.clicks_count > 0 THEN 1 ELSE 0 END) AS clicked',
                'AVG(ABS(TIMESTAMPDIFF(SECOND, email_logs.created_at, email_logs.sent_at))) AS avg_latency_seconds',
            ]))
            ->first();

        $matched = (int) ($row->matched ?? 0);

        return [
            'opened_rate' => $matched > 0 ? round(((int) $row->opened) / $matched * 100, 1) : 0.0,
            'clicked_rate' => $matched > 0 ? round(((int) $row->clicked) / $matched * 100, 1) : 0.0,
            'avg_latency' => $row->avg_latency_seconds !== null ? round((float) $row->avg_latency_seconds, 1) : null,
        ];
    }

    private function queueWaiting(): int
    {
        try {
            return Queue::connection()->size('emails');
        } catch (\Throwable) {
            // El tamaño de cola es un "nice to have" del header — un driver
            // que no lo soporte (p.ej. sync en tests) no debe tumbar la
            // pantalla completa.
            return 0;
        }
    }
}

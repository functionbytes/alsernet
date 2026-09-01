<?php

namespace Modules\HelpdeskEmailLog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Http\Requests\BulkDeleteEmailLogsRequest;
use Modules\HelpdeskEmailLog\Http\Requests\BulkResendEmailLogsRequest;
use Modules\HelpdeskEmailLog\Http\Requests\ResendEmailLogRequest;
use Modules\HelpdeskEmailLog\Jobs\ResendEmailLogJob;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Models\EmailLogClick;
use Modules\HelpdeskEmailLog\Models\EmailLogOpen;
use Modules\HelpdeskEmailLog\Services\EntityPanelRegistry;
use Modules\HelpdeskEmailLog\Support\EngagementBotHeuristics;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class EmailLogController extends Controller
{
    /** @var array<string, string> */
    private const SORTABLE = [
        'date' => 'created_at',
        'subject' => 'subject',
        'status' => 'status',
        'module' => 'module',
    ];

    private const EXPORT_HARD_LIMIT = 50000;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmailLog::class);

        abort_if(! helpdesk_emaillog_enabled(), 404);

        $perPage = $this->resolvePerPage($request);
        [$sortCol, $sortDir] = $this->resolveSort($request);

        // withCount en vez de N+1 por fila: genera una única subquery
        // correlacionada por columna (Laravel la resuelve también para
        // hasManyThrough), sin más peso que una columna extra en el SELECT.
        $logs = $this->applyFilters(EmailLog::query()->select(EmailLog::LIST_COLUMNS), $request)
            ->withCount(['opens', 'clicks'])
            ->orderBy($sortCol, $sortDir)
            ->orderBy('id', $sortDir)
            ->paginate($perPage)
            ->withQueryString();

        $stats = Cache::remember(
            'helpdeskemaillog:stats',
            now()->addSeconds(60),
            fn () => $this->computeStats(),
        );

        $modules = Cache::remember(
            'helpdeskemaillog:modules',
            now()->addMinutes(10),
            fn () => EmailLog::query()->whereNotNull('module')->distinct()->orderBy('module')->pluck('module')->all(),
        );

        $trend = Cache::remember(
            'helpdeskemaillog:trend',
            now()->addSeconds(300),
            fn () => $this->computeTrend(),
        );

        $staleHours = (int) Setting::get('helpdeskemaillog.stale_queued_hours', config('helpdeskemaillog.stale_queued_hours', 24));
        $staleCount = $staleHours > 0
            ? (int) Cache::remember('helpdeskemaillog:stale', now()->addSeconds(120), fn () => EmailLog::query()->staleQueued($staleHours)->count())
            : 0;

        return view('helpdeskemaillog::emails.index', [
            'logs' => $logs,
            'stats' => $stats,
            'trend' => $trend,
            'staleCount' => $staleCount,
            'staleHours' => $staleHours,
            'modules' => $modules,
            'statuses' => EmailStatus::options(),
            'perPage' => $perPage,
            'perPageOptions' => config('helpdeskemaillog.per_page_options', [25]),
            'sortBy' => $request->input('sort_by', 'date'),
            'sortDir' => $sortDir,
        ]);
    }

    public function show(EmailLog $emailLog): View
    {
        $this->authorize('view', $emailLog);

        $emailLog->loadMissing('causer');

        $this->logActivity('viewed', $emailLog);

        return view('helpdeskemaillog::emails.preview', [
            'log' => $emailLog,
            'related' => $this->relatedEmails($emailLog),
            'opensSummary' => $this->opensSummary($emailLog),
            'clicksSummary' => $this->clicksSummary($emailLog),
            // Panel HTML inyectado por el módulo dueño de la entidad (p. ej.
            // HelpdeskTickets pintando el hilo de la conversación) — null si
            // no hay entidad vinculada o ningún módulo satélite se registró
            // para ese entity_type. Ver EntityPanelRegistry.
            'entityPanel' => app(EntityPanelRegistry::class)->renderFor($emailLog),
        ]);
    }

    /**
     * Resumen de aperturas para el detalle — null si este envío nunca tuvo
     * píxel de seguimiento (no confundir con "0 aperturas", que sí es un
     * dato real). Ver EmailLog::hasOpenTracking().
     *
     * @return array{count: int, likely_bot_count: int, first: ?Carbon, last: ?Carbon, recent: Collection<int, EmailLogOpen>}|null
     */
    private function opensSummary(EmailLog $emailLog): ?array
    {
        if (! $emailLog->hasOpenTracking()) {
            return null;
        }

        $agg = EmailLogOpen::query()
            ->where('email_log_id', $emailLog->id)
            ->selectRaw('COUNT(*) as total, MIN(opened_at) as first_opened_at, MAX(opened_at) as last_opened_at')
            ->first();

        $recent = $emailLog->opens()->latest('opened_at')->limit(50)->get();

        return [
            'count' => (int) ($agg->total ?? 0),
            // Sobre las cargadas (hasta 50) por rendimiento — ver
            // annotateLikelyBot(). Casi siempre coincide con el total real:
            // pocos envíos superan 50 aperturas.
            'likely_bot_count' => $this->annotateLikelyBot($recent, 'opened_at', $emailLog->sent_at),
            'first' => $agg->first_opened_at ? Carbon::parse($agg->first_opened_at) : null,
            'last' => $agg->last_opened_at ? Carbon::parse($agg->last_opened_at) : null,
            'recent' => $recent,
        ];
    }

    /**
     * Resumen de clics para el detalle — null si este envío nunca tuvo sus
     * enlaces reescritos para seguimiento (no confundir con "0 clics", que sí
     * es un dato real). Ver EmailLog::hasClickTracking().
     *
     * @return array{count: int, unique_links: int, likely_bot_count: int, first: ?Carbon, last: ?Carbon, recent: Collection<int, EmailLogClick>}|null
     */
    private function clicksSummary(EmailLog $emailLog): ?array
    {
        if (! $emailLog->hasClickTracking()) {
            return null;
        }

        $agg = EmailLogClick::query()
            ->join('email_log_links', 'email_log_links.id', '=', 'email_log_clicks.email_log_link_id')
            ->where('email_log_links.email_log_id', $emailLog->id)
            ->selectRaw('COUNT(*) as total, COUNT(DISTINCT email_log_clicks.email_log_link_id) as unique_links, MIN(clicked_at) as first_clicked_at, MAX(clicked_at) as last_clicked_at')
            ->first();

        // select() explícito sobre la relación hasManyThrough para traer
        // también la URL del enlace (columna de la tabla intermedia,
        // "link_url" en vez de "url" para no chocar con ninguna otra
        // columna propia de email_log_clicks).
        $recent = $emailLog->clicks()
            ->select('email_log_clicks.*', 'email_log_links.url as link_url')
            ->latest('clicked_at')
            ->limit(50)
            ->get();

        return [
            'count' => (int) ($agg->total ?? 0),
            'unique_links' => (int) ($agg->unique_links ?? 0),
            'likely_bot_count' => $this->annotateLikelyBot($recent, 'clicked_at', $emailLog->sent_at),
            'first' => $agg->first_clicked_at ? Carbon::parse($agg->first_clicked_at) : null,
            'last' => $agg->last_clicked_at ? Carbon::parse($agg->last_clicked_at) : null,
            'recent' => $recent,
        ];
    }

    /**
     * Marca (atributo transitorio `likely_bot`, nunca persistido) cada
     * evento que EngagementBotHeuristics considera un probable bot/proxy —
     * nunca se descarta el dato, solo se etiqueta, mismo criterio de
     * honestidad que el resto del módulo (ver notas de Apple MPP/proxy de
     * Gmail ya existentes en la vista).
     *
     * @param  Collection<int, EmailLogOpen|EmailLogClick>  $events
     * @return int cuántos de $events se marcaron como probable bot
     */
    private function annotateLikelyBot(Collection $events, string $dateField, ?Carbon $sentAt): int
    {
        $botCount = 0;

        foreach ($events as $event) {
            $isBot = EngagementBotHeuristics::isLikelyBot($event->user_agent, $sentAt, $event->{$dateField});
            $event->setAttribute('likely_bot', $isBot);

            if ($isBot) {
                $botCount++;
            }
        }

        return $botCount;
    }

    /**
     * Other emails linked to the same entity or sent to the same primary
     * recipient, most recent first (excludes the current record).
     *
     * @return Collection<int, EmailLog>
     */
    private function relatedEmails(EmailLog $emailLog): Collection
    {
        $primary = $emailLog->to_addresses[0] ?? null;
        $hasEntity = $emailLog->entity_type && $emailLog->entity_id;

        if (! $primary && ! $hasEntity) {
            return collect();
        }

        return EmailLog::query()
            ->select(['uid', 'subject', 'status', 'sent_at', 'failed_at', 'created_at', 'to_addresses'])
            ->where('id', '!=', $emailLog->id)
            ->where(function (Builder $q) use ($emailLog, $primary, $hasEntity) {
                if ($hasEntity) {
                    $q->orWhere(fn (Builder $e) => $e
                        ->where('entity_type', $emailLog->entity_type)
                        ->where('entity_id', $emailLog->entity_id));
                }

                if ($primary) {
                    // Coincidencia exacta del destinatario (evita el over-match por
                    // substring de un LIKE '%...%', p.ej. ana@x.com en diana@x.com).
                    $q->orWhereJsonContains('to_addresses', $primary);
                }
            })
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();
    }

    public function purgeBody(EmailLog $emailLog): RedirectResponse
    {
        $this->authorize('delete', $emailLog);

        $metadata = $emailLog->metadata ?? [];
        $metadata['redacted'] = true;
        $metadata['redacted_at'] = now()->toIso8601String();

        $emailLog->update([
            'body_html' => null,
            'body_text' => null,
            'metadata' => $metadata,
        ]);

        $this->logActivity('body_purged', $emailLog);

        return back()->with('success', __('helpdeskemaillog::emaillog.purge.done'));
    }

    public function resend(ResendEmailLogRequest $request, EmailLog $emailLog): RedirectResponse
    {
        $override = $request->validated()['to'] ?? null;

        if (empty($override) && empty($emailLog->to_addresses)) {
            return back()->with('error', __('helpdeskemaillog::emaillog.resend.no_recipients'));
        }

        // El reenvío reproduce el cuerpo almacenado tal cual: si fue redactado
        // o truncado, se enviaría una copia vacía/incompleta al destinatario.
        if (! $emailLog->isResendable()) {
            return back()->with('error', __('helpdeskemaillog::emaillog.resend.blocked'));
        }

        ResendEmailLogJob::dispatch($emailLog->id, $override);

        $this->logActivity('resent', $emailLog, $override ? ['to' => $override] : []);

        return back()->with('success', $override
            ? __('helpdeskemaillog::emaillog.resend.queued_to', ['email' => $override])
            : __('helpdeskemaillog::emaillog.resend.queued'));
    }

    public function bulkResend(BulkResendEmailLogsRequest $request): RedirectResponse
    {
        // No se cargan las columnas de cuerpo (pesadas) para hasta 200 filas:
        // aquí se filtra por los flags de metadata y el job vuelve a comprobar
        // isResendable() sobre la fila completa (cubre filas antiguas cuyo
        // truncado solo es detectable por el marcador en el cuerpo).
        $logs = EmailLog::query()
            ->select(['id', 'uid', 'to_addresses', 'metadata'])
            ->whereIn('uid', $request->validated('uids'))
            ->get();

        $queued = 0;
        $skipped = 0;

        foreach ($logs as $log) {
            // Se omiten registros sin destinatarios y aquellos cuyo cuerpo
            // almacenado está redactado o truncado (véase resend()).
            if (empty($log->to_addresses) || ! $log->isResendable()) {
                $skipped++;

                continue;
            }

            ResendEmailLogJob::dispatch($log->id);
            $queued++;
        }

        $this->logActivity('bulk_resent', null, ['count' => $queued, 'skipped' => $skipped]);

        $message = __('helpdeskemaillog::emaillog.resend.bulk_queued', ['count' => $queued]);

        if ($skipped > 0) {
            $message .= ' '.__('helpdeskemaillog::emaillog.resend.bulk_skipped', ['count' => $skipped]);
        }

        return back()->with('success', $message);
    }

    public function download(EmailLog $emailLog): Response
    {
        $this->authorize('view', $emailLog);

        $body = $emailLog->body_html
            ?: ($emailLog->body_text ? '<pre>'.e($emailLog->body_text).'</pre>' : null);

        abort_if($body === null, 404);

        $this->logActivity('downloaded', $emailLog);

        $filename = 'email-'.substr($emailLog->uid, 0, 8).'.html';

        return response($body, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Descarga un .eml reconstruido desde raw_headers + body_html/body_text
     * — no se guarda un raw_source completo aparte (duplicaría el cuerpo,
     * que ya vive en su propia columna sujeta a truncado/redacción). Filas
     * sin raw_headers (creadas antes de esta columna, o con el cuerpo
     * purgado) reciben cabeceras mínimas sintetizadas desde las columnas
     * estructuradas, marcadas explícitamente como reconstrucción.
     */
    public function downloadRaw(EmailLog $emailLog): Response
    {
        $this->authorize('view', $emailLog);

        $hasContent = $emailLog->raw_headers !== null || $emailLog->body_html || $emailLog->body_text;

        abort_if(! $hasContent, 404);

        $this->logActivity('downloaded_raw', $emailLog);

        $filename = 'email-'.substr($emailLog->uid, 0, 8).'.eml';

        return response($this->buildEmlContent($emailLog), 200, [
            'Content-Type' => 'message/rfc822',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function buildEmlContent(EmailLog $emailLog): string
    {
        $headers = $emailLog->raw_headers;

        if ($headers === null) {
            $lines = [
                'X-HelpdeskEmailLog-Note: Cabeceras reconstruidas — no es una captura verbatim del envío original.',
                'From: '.($emailLog->from_name ? "{$emailLog->from_name} <{$emailLog->from_address}>" : $emailLog->from_address),
                'To: '.implode(', ', $emailLog->to_addresses ?? []),
            ];

            if ($emailLog->message_id) {
                $lines[] = "Message-ID: <{$emailLog->message_id}>";
            }

            $lines[] = 'Subject: '.$emailLog->subject;
            $lines[] = 'Date: '.($emailLog->created_at?->toRfc2822String() ?? '');
            $headers = implode("\r\n", $lines);
        }

        $body = $emailLog->body_html ?: ($emailLog->body_text ?? '');

        if (! empty($emailLog->attachments)) {
            // Los adjuntos solo guardan metadatos (nombre/tamaño/tipo), nunca
            // el binario — un .eml reconstruido no puede incluirlos de verdad.
            $body .= "\r\n\r\n[Nota: este .eml no incluye los adjuntos originales — solo se conservaron sus metadatos.]";
        }

        return $headers."\r\n\r\n".$body;
    }

    public function destroy(EmailLog $emailLog): RedirectResponse
    {
        $this->authorize('delete', $emailLog);

        $this->logActivity('deleted', $emailLog);
        $emailLog->delete();

        return redirect()
            ->route('helpdeskemaillog.index')
            ->with('success', __('helpdeskemaillog::emaillog.deleted.one'));
    }

    public function bulkDestroy(BulkDeleteEmailLogsRequest $request): RedirectResponse
    {
        // Consistencia con destroy() (que sí llama authorize('delete')): la
        // policy deleteAny() ya existía pero no se invocaba desde el controller.
        $this->authorize('deleteAny', EmailLog::class);

        $deleted = EmailLog::query()->whereIn('uid', $request->validated('uids'))->delete();

        if ($deleted > 0) {
            EmailLog::forgetDashboardCaches();
        }

        $this->logActivity('bulk_deleted', null, ['count' => $deleted]);

        return back()->with('success', __('helpdeskemaillog::emaillog.deleted.many', ['count' => $deleted]));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', EmailLog::class);

        $columns = [
            'id', 'uid', 'created_at', 'sent_at', 'status', 'subject', 'from_address',
            'to_addresses', 'cc_addresses', 'module', 'entity_type', 'entity_id',
            'mailable_class', 'error_message', 'metadata',
        ];

        $rows = $this->applyFilters(EmailLog::query()->select($columns), $request)
            ->withCount(['opens', 'clicks'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::EXPORT_HARD_LIMIT)
            ->cursor();

        $filename = 'email-logs-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_map(
                fn (string $key): string => __("helpdeskemaillog::emaillog.csv.{$key}"),
                ['uid', 'date', 'sent_at', 'status', 'subject', 'from', 'to', 'cc', 'module', 'entity', 'mailable', 'opens', 'clicks', 'error']
            ));

            foreach ($rows as $log) {
                fputcsv($out, [
                    $log->uid,
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $log->sent_at?->format('Y-m-d H:i:s'),
                    $log->status?->value,
                    $log->subject,
                    $log->from_address,
                    implode(', ', $log->to_addresses ?? []),
                    implode(', ', $log->cc_addresses ?? []),
                    $log->module,
                    $log->entity_type ? $log->entity_type.' #'.$log->entity_id : null,
                    $log->mailable_class ? class_basename($log->mailable_class) : null,
                    // "" (sin seguimiento) distinto de "0" (con seguimiento,
                    // cero interacciones) — mismo criterio de honestidad que
                    // la columna "Interacción" del listado.
                    $log->hasOpenTracking() ? (string) $log->opens_count : '',
                    $log->hasClickTracking() ? (string) $log->clicks_count : '',
                    $log->error_message,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{total: int, sent: int, failed: int, queued: int, today: int, bounced: int, complained: int, open_tracked: int, opened: int, click_tracked: int, clicked: int}
     */
    private function computeStats(): array
    {
        $aggregate = EmailLog::query()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(status = 'sent') AS sent")
            ->selectRaw("SUM(status = 'failed') AS failed")
            ->selectRaw("SUM(status = 'queued') AS queued")
            ->selectRaw("SUM(status = 'bounced') AS bounced")
            ->selectRaw("SUM(status = 'complained') AS complained")
            ->selectRaw('SUM(created_at >= ?) AS today', [today()->toDateTimeString()])
            ->first();

        // Denominador = envíos que SÍ tuvieron seguimiento, no el total —
        // una tasa sobre el total mezclaría "nadie lo abrió" con "nunca se
        // pudo saber" (mismo criterio de honestidad que hasOpenTracking()).
        $openTracked = EmailLog::query()->where('metadata->open_tracking_enabled', true);
        $openTrackedTotal = (clone $openTracked)->count();
        $openedTotal = (clone $openTracked)->whereHas('opens')->count();

        $clickTracked = EmailLog::query()->where('metadata->click_tracking_enabled', true);
        $clickTrackedTotal = (clone $clickTracked)->count();
        $clickedTotal = (clone $clickTracked)->whereHas('clicks')->count();

        return [
            'total' => (int) ($aggregate->total ?? 0),
            'sent' => (int) ($aggregate->sent ?? 0),
            'failed' => (int) ($aggregate->failed ?? 0),
            'queued' => (int) ($aggregate->queued ?? 0),
            'bounced' => (int) ($aggregate->bounced ?? 0),
            'complained' => (int) ($aggregate->complained ?? 0),
            'today' => (int) ($aggregate->today ?? 0),
            'open_tracked' => $openTrackedTotal,
            'opened' => $openedTotal,
            'click_tracked' => $clickTrackedTotal,
            'clicked' => $clickedTotal,
        ];
    }

    /**
     * Daily counts per status for the last N days (for the trend chart).
     *
     * @return array{labels: list<string>, sent: list<int>, failed: list<int>, queued: list<int>, bounced: list<int>, complained: list<int>}
     */
    private function computeTrend(int $days = 14): array
    {
        $since = today()->subDays($days - 1);

        $rows = EmailLog::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) AS d')
            ->selectRaw("SUM(status = 'sent') AS sent")
            ->selectRaw("SUM(status = 'failed') AS failed")
            ->selectRaw("SUM(status = 'queued') AS queued")
            ->selectRaw("SUM(status = 'bounced') AS bounced")
            ->selectRaw("SUM(status = 'complained') AS complained")
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('d');

        $labels = $sent = $failed = $queued = $bounced = $complained = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $since->copy()->addDays($i);
            $row = $rows->get($date->toDateString());
            $labels[] = $date->format('d/m');
            $sent[] = (int) ($row->sent ?? 0);
            $failed[] = (int) ($row->failed ?? 0);
            $queued[] = (int) ($row->queued ?? 0);
            $bounced[] = (int) ($row->bounced ?? 0);
            $complained[] = (int) ($row->complained ?? 0);
        }

        return compact('labels', 'sent', 'failed', 'queued', 'bounced', 'complained');
    }

    private function applyFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('module')) {
            $query->forModule((string) $request->input('module'));
        }

        if ($request->filled('status')) {
            $query->status((string) $request->input('status'));
        }

        // Filtro por entidad relacionada (p.ej. un ticket concreto) — el
        // enlace "ver todos los emails de este ticket" lo arma el módulo
        // dueño de la entidad (HelpdeskTickets), nunca al revés: este
        // controlador no conoce ningún FQCN de módulo concreto, solo el par
        // genérico entity_type/entity_id que ya usa EmailLog::scopeForEntity().
        if ($request->filled('entity_type') && $request->filled('entity_id')) {
            $query->forEntity((string) $request->input('entity_type'), (string) $request->input('entity_id'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $like = '%'.addcslashes($search, '%_\\').'%';

            // Nota (revisión de rendimiento, ago-2026): se evaluó quitar el
            // `orWhere('recipients_index', 'like', $like)` por parecer
            // redundante con el MATCH...AGAINST de la misma columna — pero se
            // revirtió tras comprobar en vivo que MATCH en modo lenguaje
            // natural es mucho MENOS preciso que el LIKE (coincide con
            // cualquier fila que comparta un token suelto, p.ej. "example"/
            // "test" de un dominio de prueba, devolviendo decenas de
            // resultados no relacionados) — el LIKE es el que de verdad
            // garantiza la coincidencia exacta de subcadena que el buscador
            // de destinatarios necesita. La query sigue sin poder usar
            // índice en esta rama del OR (LIKE con comodín inicial); una
            // solución real requeriría MATCH en modo booleano con todos los
            // tokens como obligatorios, un cambio de comportamiento mayor
            // fuera del alcance de este arreglo puntual.
            $query->where(function (Builder $q) use ($search, $like) {
                $q->where('subject', 'like', $like)
                    ->orWhere('from_address', 'like', $like)
                    ->orWhere('from_name', 'like', $like)
                    ->orWhereRaw('MATCH(recipients_index) AGAINST (?)', [$search])
                    ->orWhere('recipients_index', 'like', $like);
            });
        }

        if ($from = $this->parseDateFilter($request->input('date_from'))) {
            $query->where('created_at', '>=', $from->startOfDay());
        }

        if ($to = $this->parseDateFilter($request->input('date_to'))) {
            $query->where('created_at', '<=', $to->endOfDay());
        }

        // "sin abrir"/"sin clic" solo tienen sentido sobre envíos que SÍ
        // tuvieron seguimiento (metadata->*_tracking_enabled) — de lo
        // contrario "sin abrir" incluiría también todo lo que nunca tuvo
        // píxel, mezclando "no lo vio" con "no se pudo saber".
        if ($request->filled('engagement')) {
            match ($request->input('engagement')) {
                'opened' => $query->whereHas('opens'),
                'not_opened' => $query->where('metadata->open_tracking_enabled', true)->doesntHave('opens'),
                'clicked' => $query->whereHas('clicks'),
                'not_clicked' => $query->where('metadata->click_tracking_enabled', true)->doesntHave('clicks'),
                default => null,
            };
        }

        return $query;
    }

    /**
     * Parse a user-supplied date filter defensively: a malformed value (or a
     * non-string, e.g. ?date_from[]=x) must not turn into a 500 — the filter
     * is simply ignored.
     */
    private function parseDateFilter(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse(trim($value));
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array{string, string} [column, direction] */
    private function resolveSort(Request $request): array
    {
        $key = (string) $request->input('sort_by', 'date');
        $col = self::SORTABLE[$key] ?? 'created_at';
        $dir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        return [$col, $dir];
    }

    private function resolvePerPage(Request $request): int
    {
        $default = (int) Setting::get('helpdeskemaillog.per_page', config('helpdeskemaillog.per_page', 25));
        $options = array_map('intval', (array) config('helpdeskemaillog.per_page_options', [$default]));
        $requested = (int) $request->input('per_page');

        return in_array($requested, $options, true) ? $requested : $default;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function logActivity(string $event, ?EmailLog $emailLog = null, array $properties = []): void
    {
        rescue(function () use ($event, $emailLog, $properties) {
            $logger = activity('email-log')->event($event)->withProperties($properties + ['ip' => request()->ip()]);

            if ($emailLog) {
                $logger->performedOn($emailLog);
            }

            $logger->log('email-log.'.$event);
        }, report: false);
    }
}

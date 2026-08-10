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

        $logs = $this->applyFilters(EmailLog::query()->select(EmailLog::LIST_COLUMNS), $request)
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
        ]);
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
            Cache::forget('helpdeskemaillog:stats');
            Cache::forget('helpdeskemaillog:modules');
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
            'mailable_class', 'error_message',
        ];

        $rows = $this->applyFilters(EmailLog::query()->select($columns), $request)
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
                ['uid', 'date', 'sent_at', 'status', 'subject', 'from', 'to', 'cc', 'module', 'entity', 'mailable', 'error']
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
                    $log->error_message,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{total: int, sent: int, failed: int, queued: int, today: int}
     */
    private function computeStats(): array
    {
        $aggregate = EmailLog::query()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(status = 'sent') AS sent")
            ->selectRaw("SUM(status = 'failed') AS failed")
            ->selectRaw("SUM(status = 'queued') AS queued")
            ->selectRaw('SUM(created_at >= ?) AS today', [today()->toDateTimeString()])
            ->first();

        return [
            'total' => (int) ($aggregate->total ?? 0),
            'sent' => (int) ($aggregate->sent ?? 0),
            'failed' => (int) ($aggregate->failed ?? 0),
            'queued' => (int) ($aggregate->queued ?? 0),
            'today' => (int) ($aggregate->today ?? 0),
        ];
    }

    /**
     * Daily counts per status for the last N days (for the trend chart).
     *
     * @return array{labels: list<string>, sent: list<int>, failed: list<int>, queued: list<int>}
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
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('d');

        $labels = $sent = $failed = $queued = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $since->copy()->addDays($i);
            $row = $rows->get($date->toDateString());
            $labels[] = $date->format('d/m');
            $sent[] = (int) ($row->sent ?? 0);
            $failed[] = (int) ($row->failed ?? 0);
            $queued[] = (int) ($row->queued ?? 0);
        }

        return compact('labels', 'sent', 'failed', 'queued');
    }

    private function applyFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('module')) {
            $query->forModule((string) $request->input('module'));
        }

        if ($request->filled('status')) {
            $query->status((string) $request->input('status'));
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

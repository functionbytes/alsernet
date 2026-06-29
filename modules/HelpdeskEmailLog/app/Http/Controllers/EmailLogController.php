<?php

namespace Modules\HelpdeskEmailLog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Http\Requests\BulkDeleteEmailLogsRequest;
use Modules\HelpdeskEmailLog\Jobs\ResendEmailLogJob;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        return view('helpdeskemaillog::emails.index', [
            'logs' => $logs,
            'stats' => $stats,
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

        return view('helpdeskemaillog::emails.preview', ['log' => $emailLog]);
    }

    public function resend(EmailLog $emailLog): RedirectResponse
    {
        $this->authorize('resend', $emailLog);

        if (empty($emailLog->to_addresses)) {
            return back()->with('error', __('helpdeskemaillog::emaillog.resend.no_recipients'));
        }

        ResendEmailLogJob::dispatch($emailLog->id);

        $this->logActivity('resent', $emailLog);

        return back()->with('success', __('helpdeskemaillog::emaillog.resend.queued'));
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
            fputcsv($out, ['UID', 'Fecha', 'Enviado', 'Estado', 'Asunto', 'De', 'Para', 'CC', 'Modulo', 'Entidad', 'Mailable', 'Error']);

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

            $query->where(function (Builder $q) use ($search, $like) {
                $q->where('subject', 'like', $like)
                    ->orWhere('from_address', 'like', $like)
                    ->orWhere('from_name', 'like', $like)
                    ->orWhereRaw('MATCH(recipients_index) AGAINST (?)', [$search])
                    ->orWhere('recipients_index', 'like', $like);
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse((string) $request->input('date_from'))->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse((string) $request->input('date_to'))->endOfDay());
        }

        return $query;
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

<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Attention\Models\Attention;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;
use Modules\Reviews\Models\Review;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    /**
     * Display the generic dashboard.
     * The dashboard adapts its content based on user role.
     */
    public function index(): View
    {
        $user = Auth::user();

        return view('core::dashboard.index', [
            'user' => $user,
            'userRole' => $user->roles->first()?->name,
        ]);
    }

    /**
     * Return real-time KPI data for dashboard widgets.
     * Each module query is wrapped in try/catch so a missing module returns 0.
     */
    public function kpis(): JsonResponse
    {
        $this->authorize('settings.view');

        $data = Cache::remember('dashboard:kpis:'.today()->toDateString(), now()->addMinutes(2), function () {
            $data = [
                'reviews' => ['total' => 0, 'avg_rating' => 0, 'new_today' => 0, 'pending' => 0],
                'attention' => ['pending' => 0, 'in_process' => 0, 'resolved_today' => 0, 'total_week' => 0],
                'forms' => ['submissions_today' => 0, 'unread' => 0, 'active_forms' => 0],
            ];

            try {
                if (class_exists(Review::class)) {
                    $row = Review::selectRaw("
                        COUNT(*) as total,
                        SUM(CASE WHEN DATE(review_time) = CURDATE() THEN 1 ELSE 0 END) as new_today,
                        SUM(CASE WHEN google_reply_text IS NULL AND comment IS NOT NULL THEN 1 ELSE 0 END) as pending,
                        AVG(CASE star_rating
                            WHEN 'ONE' THEN 1 WHEN 'TWO' THEN 2 WHEN 'THREE' THEN 3
                            WHEN 'FOUR' THEN 4 WHEN 'FIVE' THEN 5 ELSE 0
                        END) as avg_rating
                    ")->first();

                    $data['reviews']['total'] = (int) $row->total;
                    $data['reviews']['new_today'] = (int) $row->new_today;
                    $data['reviews']['pending'] = (int) $row->pending;
                    $data['reviews']['avg_rating'] = round((float) ($row->avg_rating ?? 0), 1);
                }
            } catch (\Throwable $e) {
                Log::warning('Dashboard KPI query failed', [
                    'kpi' => 'reviews',
                    'code' => $e->getCode(),
                    'file' => class_basename($e->getFile()),
                    'line' => $e->getLine(),
                ]);
            }

            try {
                if (class_exists(Attention::class)) {
                    $row = Attention::selectRaw("
                        SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'in_process' THEN 1 ELSE 0 END) as in_process,
                        SUM(CASE WHEN status = 'resolved' AND DATE(resolved_at) = CURDATE() THEN 1 ELSE 0 END) as resolved_today,
                        SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as total_week
                    ", [now()->startOfWeek()])->first();

                    $data['attention']['pending'] = (int) $row->pending;
                    $data['attention']['in_process'] = (int) $row->in_process;
                    $data['attention']['resolved_today'] = (int) $row->resolved_today;
                    $data['attention']['total_week'] = (int) $row->total_week;
                }
            } catch (\Throwable $e) {
                Log::warning('Dashboard KPI query failed', [
                    'kpi' => 'attention',
                    'code' => $e->getCode(),
                    'file' => class_basename($e->getFile()),
                    'line' => $e->getLine(),
                ]);
            }

            try {
                if (class_exists(FormSubmission::class)) {
                    $row = FormSubmission::selectRaw('
                        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as submissions_today,
                        SUM(CASE WHEN is_read = 0 AND is_spam = 0 THEN 1 ELSE 0 END) as unread
                    ')->first();

                    $data['forms']['submissions_today'] = (int) $row->submissions_today;
                    $data['forms']['unread'] = (int) $row->unread;
                }

                if (class_exists(Form::class)) {
                    $data['forms']['active_forms'] = Form::where('is_active', true)->count();
                }
            } catch (\Throwable $e) {
                Log::warning('Dashboard KPI query failed', [
                    'kpi' => 'forms',
                    'code' => $e->getCode(),
                    'file' => class_basename($e->getFile()),
                    'line' => $e->getLine(),
                ]);
            }

            return $data;
        });

        return response()->json($data);
    }

    /**
     * Return the last 20 activity log entries for the dashboard feed.
     */
    public function recentActivity(): JsonResponse
    {
        $this->authorize('settings.view');

        $activities = Cache::remember('dashboard:recent_activity', now()->addMinute(), function () {
            try {
                if (! class_exists(Activity::class)) {
                    return [];
                }

                return Activity::with('causer:id,name,firstname,avatar')
                    ->latest()
                    ->limit(20)
                    ->get()
                    ->map(fn ($a) => [
                        'id' => $a->id,
                        'description' => $a->description,
                        'subject_type' => class_basename($a->subject_type ?? ''),
                        'causer' => $a->causer?->name ?? $a->causer?->firstname ?? 'Sistema',
                        'causer_avatar' => $a->causer?->avatar ?? null,
                        'created_at' => $a->created_at->diffForHumans(),
                        'icon' => $this->iconForActivity($a->description),
                        'color' => $this->colorForActivity($a->description),
                    ])
                    ->toArray();
            } catch (\Throwable $e) {
                Log::warning('Dashboard recent activity query failed', [
                    'code' => $e->getCode(),
                    'file' => class_basename($e->getFile()),
                    'line' => $e->getLine(),
                ]);

                return [];
            }
        });

        return response()->json(['activities' => $activities]);
    }

    /**
     * Return queue and Horizon status for the dashboard monitoring widget.
     */
    public function queueStats(): JsonResponse
    {
        $this->authorize('settings.system');

        $failedJobs = 0;
        $pendingByQueue = [];
        $totalPending = 0;

        try {
            $failedJobs = DB::table('failed_jobs')->count();
        } catch (\Throwable $e) {
            Log::warning('Dashboard queue stats: failed_jobs query failed', ['error' => $e->getMessage()]);
        }

        try {
            $rows = DB::table('jobs')
                ->selectRaw('queue, COUNT(*) as count')
                ->groupBy('queue')
                ->get();

            foreach ($rows as $row) {
                $pendingByQueue[$row->queue] = (int) $row->count;
                $totalPending += (int) $row->count;
            }
        } catch (\Throwable $e) {
            Log::warning('Dashboard queue stats: jobs query failed', ['error' => $e->getMessage()]);
        }

        $horizonStatus = $this->resolveHorizonStatus();

        return response()->json([
            'failed_jobs' => $failedJobs,
            'pending_by_queue' => $pendingByQueue,
            'total_pending' => $totalPending,
            'horizon_status' => $horizonStatus,
        ]);
    }

    /**
     * Determine Horizon status: running, paused, or inactive.
     */
    private function resolveHorizonStatus(): string
    {
        $status = Cache::get('horizon:status');

        if ($status === 'running') {
            return 'running';
        }

        if ($status === 'paused') {
            return 'paused';
        }

        return 'inactive';
    }

    private function iconForActivity(string $description): string
    {
        return match (true) {
            str_contains($description, 'created') => 'fas fa-plus-circle',
            str_contains($description, 'updated') => 'fas fa-pencil-alt',
            str_contains($description, 'deleted') => 'fas fa-trash-alt',
            str_contains($description, 'login') => 'fas fa-sign-in-alt',
            default => 'fas fa-history',
        };
    }

    private function colorForActivity(string $description): string
    {
        return match (true) {
            str_contains($description, 'created') => 'success',
            str_contains($description, 'updated') => 'warning',
            str_contains($description, 'deleted') => 'danger',
            default => 'secondary',
        };
    }
}

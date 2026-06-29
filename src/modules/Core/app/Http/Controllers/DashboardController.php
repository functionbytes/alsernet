<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Attention\Models\Attention;
use Modules\Auth\Models\LoginAttempt;
use Modules\Auth\Models\Session as UserSession;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;
use Modules\Reviews\Models\Review;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user()->load('roles');

        return view('core::dashboard.index', [
            'user' => $user,
            'userRole' => $user->roles->first()?->name,
        ]);
    }

    public function kpis(): JsonResponse
    {
        $this->authorize('settings.view');

        $data = Cache::remember('dashboard:kpis:'.today()->toDateString(), now()->addMinutes(2), function () {
            $data = [
                'reviews' => [
                    'total' => 0, 'avg_rating' => 0, 'new_today' => 0, 'pending' => 0,
                    'total_yesterday' => 0, 'one_star_unreplied' => 0, 'two_star_unreplied' => 0,
                ],
                'attention' => [
                    'pending' => 0, 'in_process' => 0, 'resolved_today' => 0, 'total_week' => 0,
                    'pending_yesterday' => 0, 'overdue' => 0,
                    'avg_resolution_hours' => 0, 'sla_compliance_pct' => 100,
                ],
                'forms' => [
                    'submissions_today' => 0, 'unread' => 0, 'active_forms' => 0,
                    'submissions_yesterday' => 0, 'projected_today' => 0,
                ],
            ];

            // Reviews
            try {
                if (class_exists(Review::class)) {
                    $row = Review::selectRaw("
                        COUNT(*) as total,
                        SUM(CASE WHEN DATE(review_time) = CURDATE() THEN 1 ELSE 0 END) as new_today,
                        SUM(CASE WHEN DATE(review_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) as total_yesterday,
                        SUM(CASE WHEN google_reply_text IS NULL AND comment IS NOT NULL THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN google_reply_text IS NULL AND comment IS NOT NULL AND star_rating = 'ONE' THEN 1 ELSE 0 END) as one_star_unreplied,
                        SUM(CASE WHEN google_reply_text IS NULL AND comment IS NOT NULL AND star_rating = 'TWO' THEN 1 ELSE 0 END) as two_star_unreplied,
                        AVG(CASE star_rating WHEN 'ONE' THEN 1 WHEN 'TWO' THEN 2 WHEN 'THREE' THEN 3 WHEN 'FOUR' THEN 4 WHEN 'FIVE' THEN 5 ELSE 0 END) as avg_rating
                    ")->first();

                    $data['reviews']['total'] = (int) $row->total;
                    $data['reviews']['new_today'] = (int) $row->new_today;
                    $data['reviews']['total_yesterday'] = (int) $row->total_yesterday;
                    $data['reviews']['pending'] = (int) $row->pending;
                    $data['reviews']['one_star_unreplied'] = (int) $row->one_star_unreplied;
                    $data['reviews']['two_star_unreplied'] = (int) $row->two_star_unreplied;
                    $data['reviews']['avg_rating'] = round((float) ($row->avg_rating ?? 0), 1);
                }
            } catch (\Throwable $e) {
                Log::warning('Dashboard KPI query failed', ['kpi' => 'reviews']);
            }

            // Attention + SLA
            try {
                if (class_exists(Attention::class)) {
                    $row = Attention::selectRaw("
                        SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'received' AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) as pending_yesterday,
                        SUM(CASE WHEN status = 'in_process' THEN 1 ELSE 0 END) as in_process,
                        SUM(CASE WHEN status = 'resolved' AND DATE(resolved_at) = CURDATE() THEN 1 ELSE 0 END) as resolved_today,
                        SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as total_week,
                        SUM(CASE WHEN status = 'received' AND DATE_ADD(created_at, INTERVAL 5 DAY) < NOW() THEN 1 ELSE 0 END) as overdue
                    ", [now()->startOfWeek()])->first();

                    $data['attention']['pending'] = (int) $row->pending;
                    $data['attention']['pending_yesterday'] = (int) $row->pending_yesterday;
                    $data['attention']['in_process'] = (int) $row->in_process;
                    $data['attention']['resolved_today'] = (int) $row->resolved_today;
                    $data['attention']['total_week'] = (int) $row->total_week;
                    $data['attention']['overdue'] = (int) $row->overdue;

                    // SLA: avg resolution time (hours) for resolved last 30 days
                    $slaRow = Attention::selectRaw('
                        AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours,
                        SUM(CASE WHEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) <= 120 THEN 1 ELSE 0 END) as compliant,
                        COUNT(*) as total_resolved
                    ')
                        ->where('status', 'resolved')
                        ->whereDate('resolved_at', '>=', now()->subDays(30))
                        ->first();

                    $data['attention']['avg_resolution_hours'] = round((float) ($slaRow->avg_hours ?? 0), 1);
                    $totalResolved = (int) ($slaRow->total_resolved ?? 0);
                    $data['attention']['sla_compliance_pct'] = $totalResolved > 0
                        ? round(((int) ($slaRow->compliant ?? 0) / $totalResolved) * 100, 1)
                        : 100;
                }
            } catch (\Throwable $e) {
                Log::warning('Dashboard KPI query failed', ['kpi' => 'attention']);
            }

            // Forms + projection
            try {
                if (class_exists(FormSubmission::class)) {
                    $row = FormSubmission::selectRaw('
                        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as submissions_today,
                        SUM(CASE WHEN DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) as submissions_yesterday,
                        SUM(CASE WHEN is_read = 0 AND is_spam = 0 THEN 1 ELSE 0 END) as unread
                    ')->first();

                    $data['forms']['submissions_today'] = (int) $row->submissions_today;
                    $data['forms']['submissions_yesterday'] = (int) $row->submissions_yesterday;
                    $data['forms']['unread'] = (int) $row->unread;

                    // Projection based on last 7 days average (excluding today)
                    $weekAvg = FormSubmission::selectRaw('AVG(daily_count) as avg_count')
                        ->fromSub(function ($q) {
                            $q->selectRaw('DATE(created_at) as day, COUNT(*) as daily_count')
                                ->from('form_submissions')
                                ->whereDate('created_at', '>=', now()->subDays(7)->toDateString())
                                ->whereDate('created_at', '<', now()->toDateString())
                                ->groupBy('day');
                        }, 'daily')
                        ->first();

                    $data['forms']['projected_today'] = round((float) ($weekAvg->avg_count ?? 0), 0);
                }

                if (class_exists(Form::class)) {
                    $data['forms']['active_forms'] = Form::where('is_active', true)->count();
                }
            } catch (\Throwable $e) {
                Log::warning('Dashboard KPI query failed', ['kpi' => 'forms']);
            }

            return $data;
        });

        return response()->json($data);
    }

    public function alerts(): JsonResponse
    {
        $this->authorize('settings.view');

        $alerts = Cache::remember('dashboard:alerts', 30, function () {
            $items = [];

            try {
                if (class_exists(Review::class)) {
                    $critical = Review::whereNull('google_reply_text')
                        ->whereNotNull('comment')
                        ->whereIn('star_rating', ['ONE', 'TWO'])
                        ->count();
                    if ($critical > 0) {
                        $items[] = ['type' => 'reviews_critical', 'severity' => 'danger', 'icon' => 'fas fa-star', 'message' => $critical.' reseña'.($critical > 1 ? 's' : '').' de 1-2 estrellas sin responder', 'link' => safe_route('reviews.index')];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Dashboard alerts: reviews failed');
            }

            try {
                if (class_exists(Attention::class)) {
                    $overdue = Attention::where('status', 'received')->whereRaw('DATE_ADD(created_at, INTERVAL 5 DAY) < NOW()')->count();
                    if ($overdue > 0) {
                        $items[] = ['type' => 'attention_overdue', 'severity' => 'danger', 'icon' => 'fas fa-clipboard-list', 'message' => $overdue.' PQRSF vencida'.($overdue > 1 ? 's' : '').' sin atender', 'link' => route('attention.index')];
                    }
                    $pending = Attention::where('status', 'received')->count();
                    if ($pending > 5) {
                        $items[] = ['type' => 'attention_backlog', 'severity' => 'warning', 'icon' => 'fas fa-exclamation-circle', 'message' => $pending.' PQRSF pendientes de asignación', 'link' => route('attention.index')];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Dashboard alerts: attention failed');
            }

            try {
                if (class_exists(FormSubmission::class)) {
                    $unread = FormSubmission::where('is_read', 0)->where('is_spam', 0)->count();
                    if ($unread > 0) {
                        $items[] = ['type' => 'forms_unread', 'severity' => 'warning', 'icon' => 'fas fa-envelope', 'message' => $unread.' formulario'.($unread > 1 ? 's' : '').' sin leer', 'link' => route('forms.index')];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Dashboard alerts: forms failed');
            }

            try {
                $failedJobs = DB::table('failed_jobs')->count();
                if ($failedJobs > 0) {
                    $items[] = ['type' => 'queue_failed', 'severity' => 'danger', 'icon' => 'fas fa-exclamation-triangle', 'message' => $failedJobs.' trabajo'.($failedJobs > 1 ? 's' : '').' fallado'.($failedJobs > 1 ? 's' : '').' en cola', 'link' => '/horizon'];
                }
            } catch (\Throwable $e) {
                Log::warning('Dashboard alerts: failed_jobs failed');
            }

            return $items;
        });

        return response()->json(['alerts' => $alerts]);
    }

    public function recentActivity(): JsonResponse
    {
        $this->authorize('settings.view');

        $activities = Cache::remember('dashboard:recent_activity', now()->addMinute(), function () {
            try {
                if (! class_exists(Activity::class)) {
                    return [];
                }

                return Activity::with('causer:id,name,firstname,avatar')
                    ->latest()->limit(20)->get()
                    ->map(fn ($a) => [
                        'id' => $a->id,
                        'description' => $a->description,
                        'subject_type' => class_basename($a->subject_type ?? ''),
                        'subject_id' => $a->subject_id,
                        'causer' => $a->causer?->name ?? $a->causer?->firstname ?? 'Sistema',
                        'causer_avatar' => $a->causer?->avatar ?? null,
                        'created_at' => $a->created_at->diffForHumans(),
                        'icon' => $this->iconForActivity($a->description),
                        'color' => $this->colorForActivity($a->description),
                    ])->toArray();
            } catch (\Throwable $e) {
                Log::warning('Dashboard recent activity query failed');

                return [];
            }
        });

        return response()->json(['activities' => $activities]);
    }

    public function trends(Request $request): JsonResponse
    {
        $this->authorize('settings.view');

        $daysBack = min((int) $request->get('days', 7), 30);
        $start = now()->subDays($daysBack - 1)->toDateString();
        $days = collect(range($daysBack - 1, 0))->map(fn ($d) => now()->subDays($d)->toDateString())->values();
        $labels = $days->map(fn ($d) => Carbon::parse($d)->format('d M'))->values();

        $reviewsTrend = array_fill(0, $daysBack, 0);
        $attentionTrend = array_fill(0, $daysBack, 0);
        $formsTrend = array_fill(0, $daysBack, 0);

        try {
            if (class_exists(Review::class)) {
                $rows = Review::selectRaw('DATE(review_time) as day, COUNT(*) as total')
                    ->whereDate('review_time', '>=', $start)->groupBy('day')->pluck('total', 'day')->toArray();
                foreach ($days as $i => $day) {
                    $reviewsTrend[$i] = (int) ($rows[$day] ?? 0);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Dashboard trends failed', ['kpi' => 'reviews']);
        }

        try {
            if (class_exists(Attention::class)) {
                $rows = Attention::selectRaw('DATE(created_at) as day, COUNT(*) as total')
                    ->whereDate('created_at', '>=', $start)->groupBy('day')->pluck('total', 'day')->toArray();
                foreach ($days as $i => $day) {
                    $attentionTrend[$i] = (int) ($rows[$day] ?? 0);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Dashboard trends failed', ['kpi' => 'attention']);
        }

        try {
            if (class_exists(FormSubmission::class)) {
                $rows = FormSubmission::selectRaw('DATE(created_at) as day, COUNT(*) as total')
                    ->whereDate('created_at', '>=', $start)->groupBy('day')->pluck('total', 'day')->toArray();
                foreach ($days as $i => $day) {
                    $formsTrend[$i] = (int) ($rows[$day] ?? 0);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Dashboard trends failed', ['kpi' => 'forms']);
        }

        return response()->json(['labels' => $labels, 'reviews' => $reviewsTrend, 'attention' => $attentionTrend, 'forms' => $formsTrend]);
    }

    public function distribution(): JsonResponse
    {
        $this->authorize('settings.view');

        $data = ['labels' => [], 'series' => [], 'colors' => []];

        try {
            if (class_exists(Attention::class)) {
                $rows = Attention::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->toArray();
                $map = ['received' => 'Recibido', 'in_process' => 'En proceso', 'resolved' => 'Resuelto', 'closed' => 'Cerrado'];
                $colorMap = ['received' => '#FA896B', 'in_process' => '#FEC90F', 'resolved' => '#13C672', 'closed' => '#888888'];
                foreach ($rows as $status => $count) {
                    $data['labels'][] = $map[$status] ?? ucfirst($status);
                    $data['series'][] = (int) $count;
                    $data['colors'][] = $colorMap[$status] ?? '#adb5bd';
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Dashboard distribution query failed');
        }

        return response()->json($data);
    }

    public function latestReviews(): JsonResponse
    {
        $this->authorize('settings.view');

        $reviews = [];
        try {
            if (class_exists(Review::class)) {
                $reviews = Review::select(['id', 'author_name', 'star_rating', 'comment', 'review_time', 'google_reply_text'])
                    ->whereNotNull('comment')
                    ->latest('review_time')
                    ->limit(5)
                    ->get()
                    ->map(fn ($r) => [
                        'id' => $r->id,
                        'author' => $r->author_name ?? 'Anónimo',
                        'stars' => $r->star_rating,
                        'comment' => str($r->comment ?? '')->limit(80)->value(),
                        'date' => $r->review_time?->diffForHumans() ?? '',
                        'replied' => ! empty($r->google_reply_text),
                    ])->toArray();
            }
        } catch (\Throwable $e) {
            Log::warning('Dashboard latest reviews query failed');
        }

        return response()->json(['reviews' => $reviews]);
    }

    public function queueStats(): JsonResponse
    {
        $this->authorize('settings.system');

        $failedJobs = 0;
        $pendingByQueue = [];
        $totalPending = 0;
        try {
            $failedJobs = DB::table('failed_jobs')->count();
        } catch (\Throwable $e) {
        }
        try {
            foreach (DB::table('jobs')->selectRaw('queue, COUNT(*) as count')->groupBy('queue')->get() as $row) {
                $pendingByQueue[$row->queue] = (int) $row->count;
                $totalPending += (int) $row->count;
            }
        } catch (\Throwable $e) {
        }

        return response()->json([
            'failed_jobs' => $failedJobs,
            'pending_by_queue' => $pendingByQueue,
            'total_pending' => $totalPending,
            'horizon_status' => $this->resolveHorizonStatus(),
        ]);
    }

    public function health(): JsonResponse
    {
        $this->authorize('settings.view');
        $checks = ['database' => false, 'cache' => false, 'storage' => false];
        try {
            DB::select('SELECT 1');
            $checks['database'] = true;
        } catch (\Throwable $e) {
        }
        try {
            Cache::put('dashboard:health:ping', 1, 10);
            $checks['cache'] = Cache::get('dashboard:health:ping') === 1;
        } catch (\Throwable $e) {
        }
        try {
            $checks['storage'] = is_writable(storage_path('framework/cache'));
        } catch (\Throwable $e) {
        }

        return response()->json(['status' => ($checks['database'] && $checks['cache'] && $checks['storage']) ? 'operational' : 'degraded', 'checks' => $checks, 'updated_at' => now()->toDateTimeString()]);
    }

    public function securityMetrics(): JsonResponse
    {
        $this->authorize('settings.view');

        $metrics = [
            'security_score' => null,
            'two_fa_adoption_pct' => null,
            'active_sessions' => null,
            'failed_logins_24h' => null,
        ];

        try {
            $totalUsers = User::count();
            $usersWith2fa = User::whereNotNull('two_factor_confirmed_at')->count();
            $metrics['two_fa_adoption_pct'] = $totalUsers > 0
                ? round(($usersWith2fa / $totalUsers) * 100, 1)
                : 0;
        } catch (\Throwable $e) {
            Log::warning('Dashboard securityMetrics: 2fa query failed');
        }

        try {
            $cutoff = now()->subMinutes(15)->getTimestamp();
            $metrics['active_sessions'] = UserSession::where('last_activity', '>=', $cutoff)->distinct('user_id')->count('user_id');
        } catch (\Throwable $e) {
            Log::warning('Dashboard securityMetrics: sessions query failed');
        }

        try {
            if (class_exists(LoginAttempt::class)) {
                $metrics['failed_logins_24h'] = LoginAttempt::whereIn('status', [LoginAttempt::STATUS_FAILED, LoginAttempt::STATUS_LOCKOUT])
                    ->where('attempted_at', '>=', now()->subHours(24))
                    ->count();
            }
        } catch (\Throwable $e) {
            Log::warning('Dashboard securityMetrics: failed logins query failed');
        }

        // Security Score: 100 - penalizaciones
        // Penaliza por baja adopción 2FA (hasta -30), muchos fallos (hasta -30), muchas sesiones (hasta -10)
        $score = 100;
        if ($metrics['two_fa_adoption_pct'] !== null) {
            $score -= (int) ((100 - $metrics['two_fa_adoption_pct']) * 0.30);
        }
        if ($metrics['failed_logins_24h'] !== null) {
            $score -= min(30, $metrics['failed_logins_24h'] * 3);
        }
        if ($metrics['active_sessions'] !== null && $metrics['active_sessions'] > 10) {
            $score -= min(10, ($metrics['active_sessions'] - 10) * 2);
        }
        $metrics['security_score'] = max(0, min(100, $score));

        return response()->json($metrics);
    }

    public function loginAttemptsTimeline(): JsonResponse
    {
        $this->authorize('settings.view');

        $hours = collect(range(23, 0))->map(fn ($h) => now()->subHours($h)->format('H:00'))->values();
        $success = array_fill(0, 24, 0);
        $failed = array_fill(0, 24, 0);
        $lockout = array_fill(0, 24, 0);

        try {
            if (class_exists(LoginAttempt::class)) {
                $start = now()->subHours(24);
                $rows = LoginAttempt::selectRaw('
                    HOUR(attempted_at) as hr,
                    status,
                    COUNT(*) as total
                ')
                    ->where('attempted_at', '>=', $start)
                    ->groupBy('hr', 'status')
                    ->get();

                $nowHour = (int) now()->format('H');
                foreach ($rows as $row) {
                    // Calculate offset from now
                    $hr = (int) $row->hr;
                    $offset = ($hr <= $nowHour) ? ($nowHour - $hr) : (24 - $hr + $nowHour);
                    $idx = 23 - $offset;
                    if ($idx >= 0 && $idx < 24) {
                        if ($row->status === LoginAttempt::STATUS_SUCCESS || $row->status === LoginAttempt::STATUS_2FA_SUCCESS) {
                            $success[$idx] = (int) $row->total;
                        } elseif ($row->status === LoginAttempt::STATUS_FAILED || $row->status === LoginAttempt::STATUS_2FA_FAILED) {
                            $failed[$idx] = (int) $row->total;
                        } elseif ($row->status === LoginAttempt::STATUS_LOCKOUT) {
                            $lockout[$idx] = (int) $row->total;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Dashboard loginAttemptsTimeline query failed');
        }

        return response()->json(['hours' => $hours, 'success' => $success, 'failed' => $failed, 'lockout' => $lockout]);
    }

    public function topFailedIps(): JsonResponse
    {
        $this->authorize('settings.view');

        $ips = [];
        try {
            if (class_exists(LoginAttempt::class)) {
                $ips = LoginAttempt::selectRaw('ip_address, COUNT(*) as attempts, MAX(attempted_at) as last_attempt')
                    ->whereIn('status', [LoginAttempt::STATUS_FAILED, LoginAttempt::STATUS_LOCKOUT])
                    ->where('attempted_at', '>=', now()->subHours(24))
                    ->groupBy('ip_address')
                    ->orderByDesc('attempts')
                    ->limit(5)
                    ->get()
                    ->map(fn ($row) => [
                        'ip' => $row->ip_address,
                        'attempts' => (int) $row->attempts,
                        'last_attempt' => $row->last_attempt ? Carbon::parse($row->last_attempt)->diffForHumans() : '',
                    ])
                    ->toArray();
            }
        } catch (\Throwable $e) {
            Log::warning('Dashboard topFailedIps query failed');
        }

        return response()->json(['ips' => $ips]);
    }

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

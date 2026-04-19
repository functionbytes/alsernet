<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Seo\Http\Requests\ImportSearchConsoleRequest;
use Modules\Seo\Http\Requests\UpdateVerificationCodesRequest;
use Modules\Seo\Models\SeoAuditLog;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Models\SeoRedirect;

class SeoDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Seo.metas.index');
    }

    public function index(): View
    {
        $metaStats = Cache::remember('seo.dashboard.meta_stats', 300, function () {
            return [
                'total' => SeoMeta::count(),
                'indexable' => SeoMeta::whereNotLike('robots', '%noindex%')->count(),
                'noindex' => SeoMeta::where('robots', 'LIKE', '%noindex%')->count(),
                'missing_description' => SeoMeta::whereNull('description')->orWhere('description', '')->count(),
                'missing_og_image' => SeoMeta::whereNull('og_image')->orWhere('og_image', '')->count(),
                'with_score' => SeoMeta::whereNotNull('seo_score')->count(),
                'avg_score' => round(SeoMeta::whereNotNull('seo_score')->avg('seo_score') ?? 0, 1),
            ];
        });

        $gradeDistribution = Cache::remember('seo.dashboard.grade_distribution', 300, function () {
            return SeoMeta::whereNotNull('seo_grade')
                ->selectRaw('seo_grade, COUNT(*) as count')
                ->groupBy('seo_grade')
                ->pluck('count', 'seo_grade')
                ->toArray();
        });

        $redirectStats = Cache::remember('seo.dashboard.redirect_stats', 300, function () {
            return [
                'total' => SeoRedirect::count(),
                'active' => SeoRedirect::where('is_active', true)->count(),
                'total_hits' => (int) SeoRedirect::sum('hits_count'),
            ];
        });

        $recentAudits = Cache::remember('seo.dashboard.recent_audits', 300, function () {
            return SeoAuditLog::with('seoMeta')
                ->latest('audited_at')
                ->limit(10)
                ->get();
        });

        $scoreTrend = Cache::remember('seo.dashboard.score_trend', 300, function () {
            return SeoAuditLog::selectRaw('DATE(audited_at) as date, ROUND(AVG(score), 1) as avg_score, COUNT(*) as audits')
                ->where('audited_at', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        });

        $topIssues = Cache::remember('seo.dashboard.top_issues', 300, function () {
            return SeoAuditLog::whereNotNull('issues')
                ->latest('audited_at')
                ->limit(100)
                ->pluck('issues')
                ->flatten(1)
                ->where('status', '!=', 'ok')
                ->groupBy('code')
                ->map(fn ($group) => ['code' => $group->first()['code'], 'message' => $group->first()['message'], 'count' => $group->count()])
                ->sortByDesc('count')
                ->take(5)
                ->values();
        });

        $worstPages = Cache::remember('seo.dashboard.worst_pages', 300, function () {
            return SeoMeta::whereNotNull('seo_score')
                ->orderBy('seo_score', 'asc')
                ->limit(5)
                ->get();
        });

        $duplicateTitles = Cache::remember('seo.dashboard.duplicate_titles', 300, function () {
            return SeoMeta::query()
                ->selectRaw('title, COUNT(*) as count')
                ->whereNotNull('title')
                ->where('title', '!=', '')
                ->groupBy('title')
                ->havingRaw('COUNT(*) > 1')
                ->orderByDesc('count')
                ->limit(10)
                ->get();
        });

        $duplicateDescriptions = Cache::remember('seo.dashboard.duplicate_descriptions', 300, function () {
            return SeoMeta::query()
                ->selectRaw('description, COUNT(*) as count')
                ->whereNotNull('description')
                ->where('description', '!=', '')
                ->groupBy('description')
                ->havingRaw('COUNT(*) > 1')
                ->orderByDesc('count')
                ->limit(5)
                ->get();
        });

        $cannibalization = Cache::remember('seo.dashboard.cannibalization', 300, function () {
            return SeoMeta::query()
                ->select('target_keyword', DB::raw('COUNT(*) as page_count'), DB::raw('GROUP_CONCAT(id ORDER BY id SEPARATOR ",") as meta_ids'))
                ->whereNotNull('target_keyword')
                ->where('target_keyword', '!=', '')
                ->groupBy('target_keyword')
                ->having('page_count', '>', 1)
                ->orderByDesc('page_count')
                ->limit(20)
                ->get();
        });

        // Score goal tracking
        $scoreGoal = config('Seo.score_goal', 80);
        $totalAudited = SeoMeta::whereNotNull('seo_score')->count();
        $meetingGoal = SeoMeta::whereNotNull('seo_score')->where('seo_score', '>=', $scoreGoal)->count();
        $goalPercent = $totalAudited > 0 ? round(($meetingGoal / $totalAudited) * 100) : 0;

        ['improving' => $improving, 'declining' => $declining] = Cache::remember('seo.dashboard.trending', 300, function () {
            $trendingData = DB::select('
                SELECT
                    m.id,
                    m.title,
                    m.canonical_url,
                    latest.score AS current_score,
                    prev.score AS previous_score,
                    (latest.score - prev.score) AS score_change
                FROM seo_metas m
                INNER JOIN seo_audit_logs latest ON latest.seo_meta_id = m.id
                INNER JOIN seo_audit_logs prev ON prev.seo_meta_id = m.id
                WHERE latest.id = (
                    SELECT id FROM seo_audit_logs WHERE seo_meta_id = m.id ORDER BY audited_at DESC LIMIT 1
                )
                AND prev.id = (
                    SELECT id FROM seo_audit_logs WHERE seo_meta_id = m.id ORDER BY audited_at DESC LIMIT 1 OFFSET 1
                )
                AND latest.score != prev.score
                ORDER BY ABS(latest.score - prev.score) DESC
                LIMIT 20
            ');

            $collection = collect($trendingData);

            return [
                'improving' => $collection->filter(fn ($r) => $r->score_change > 0)->take(5),
                'declining' => $collection->filter(fn ($r) => $r->score_change < 0)->take(5),
            ];
        });

        return view('Seo::settings.dashboard.index', compact(
            'metaStats', 'gradeDistribution', 'redirectStats',
            'recentAudits', 'scoreTrend', 'topIssues', 'worstPages',
            'duplicateTitles', 'duplicateDescriptions', 'cannibalization',
            'scoreGoal', 'goalPercent', 'meetingGoal', 'totalAudited',
            'improving', 'declining'
        ));
    }

    public function analytics(): View
    {
        $search = request('search');

        $pages = SeoMeta::query()
            ->whereNotNull('gsc_clicks')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('canonical_url', 'like', "%{$search}%");
            }))
            ->orderByDesc('gsc_clicks')
            ->paginate(20)
            ->withQueryString();

        $gscStats = SeoMeta::query()
            ->whereNotNull('gsc_clicks')
            ->selectRaw('
                SUM(gsc_clicks) as total_clicks,
                SUM(gsc_impressions) as total_impressions,
                AVG(gsc_position) as avg_position,
                COUNT(*) as pages_with_data
            ')
            ->first();

        $lastUpdated = SeoMeta::whereNotNull('gsc_updated_at')->max('gsc_updated_at');

        return view('Seo::settings.dashboard.analytics', compact('pages', 'gscStats', 'lastUpdated'));
    }

    public function clearCache(): RedirectResponse
    {
        $this->clearDashboardCache();

        return redirect()->route('setting.seo.dashboard')
            ->with('success', 'Cache del dashboard SEO eliminado correctamente.');
    }

    public function verification(): View
    {
        $settings = [
            'seo_google_verification' => setting('seo_google_verification', config('Seo.verification.google', '')),
            'seo_bing_verification' => setting('seo_bing_verification', config('Seo.verification.bing', '')),
            'seo_pinterest_verification' => setting('seo_pinterest_verification', config('Seo.verification.pinterest', '')),
            'seo_baidu_verification' => setting('seo_baidu_verification', config('Seo.verification.baidu', '')),
            'seo_yandex_verification' => setting('seo_yandex_verification', config('Seo.verification.yandex', '')),
        ];

        return view('Seo::settings.dashboard.verification', compact('settings'));
    }

    public function verificationUpdate(UpdateVerificationCodesRequest $request): JsonResponse
    {
        $data = $request->validated();

        updateSettings([
            'seo_google_verification' => $data['seo_google_verification'] ?? '',
            'seo_bing_verification' => $data['seo_bing_verification'] ?? '',
            'seo_pinterest_verification' => $data['seo_pinterest_verification'] ?? '',
            'seo_baidu_verification' => $data['seo_baidu_verification'] ?? '',
            'seo_yandex_verification' => $data['seo_yandex_verification'] ?? '',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Códigos de verificación guardados correctamente.',
        ]);
    }

    public function showSearchConsoleImport(): View
    {
        return view('Seo::settings.dashboard.search-console-import');
    }

    public function importSearchConsole(ImportSearchConsoleRequest $request): RedirectResponse
    {
        $handle = fopen($request->file('csv_file')->getPathname(), 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);

            return back()->with('error', 'Archivo CSV inválido.');
        }

        $headerMap = array_flip(array_map('strtolower', array_map('trim', $header)));
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $pageIndex = $headerMap['page'] ?? $headerMap['top pages'] ?? $headerMap['página'] ?? 0;
            $page = trim($row[$pageIndex] ?? '');
            $clicks = (int) ($row[$headerMap['clicks'] ?? $headerMap['clics'] ?? 1] ?? 0);
            $impressions = (int) ($row[$headerMap['impressions'] ?? $headerMap['impresiones'] ?? 2] ?? 0);
            $position = round((float) ($row[$headerMap['position'] ?? $headerMap['posición'] ?? 4] ?? 0), 1);

            if (empty($page)) {
                $skipped++;

                continue;
            }

            $path = parse_url($page, PHP_URL_PATH);
            $meta = SeoMeta::where('canonical_url', $page)
                ->orWhere('canonical_url', $path)
                ->first();

            if ($meta) {
                $meta->updateQuietly([
                    'gsc_clicks' => $clicks,
                    'gsc_impressions' => $impressions,
                    'gsc_position' => $position,
                    'gsc_updated_at' => now(),
                ]);
                $imported++;
            } else {
                $skipped++;
            }
        }

        fclose($handle);

        return redirect()->route('setting.seo.report.index')
            ->with('success', "Search Console: {$imported} páginas actualizadas, {$skipped} sin coincidencia.");
    }

    private function clearDashboardCache(): void
    {
        Cache::forget('seo.dashboard.meta_stats');
        Cache::forget('seo.dashboard.grade_distribution');
        Cache::forget('seo.dashboard.redirect_stats');
        Cache::forget('seo.dashboard.recent_audits');
        Cache::forget('seo.dashboard.score_trend');
        Cache::forget('seo.dashboard.top_issues');
        Cache::forget('seo.dashboard.worst_pages');
        Cache::forget('seo.dashboard.duplicate_titles');
        Cache::forget('seo.dashboard.duplicate_descriptions');
        Cache::forget('seo.dashboard.cannibalization');
        Cache::forget('seo.dashboard.trending');
    }
}

<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Seo\Jobs\BulkSeoAuditJob;
use Modules\Seo\Jobs\CheckBrokenLinksJob;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Services\InternalLinkAnalyzer;
use Modules\Seo\Services\SeoAuditService;

class SeoAuditController extends Controller
{
    public function __construct(private readonly SeoAuditService $auditService) {}

    public function index(): View
    {
        return view('Seo::settings.audit.index');
    }

    public function auditUrl(Request $request): JsonResponse
    {
        $request->validate(['url' => 'required|url']);

        $result = $this->auditService->auditUrl($request->input('url'));

        return response()->json(['status' => true, 'data' => $result]);
    }

    public function auditAll(): JsonResponse
    {
        $results = $this->auditService->auditAllMetas();

        return response()->json([
            'status' => true,
            'data' => $results->values()->toArray(),
            'summary' => [
                'total' => $results->count(),
                'avg_score' => $results->count() > 0 ? round($results->avg('score'), 1) : 0,
                'with_issues' => $results->where('issues_count', '>', 0)->count(),
                'score_a' => $results->where('grade', 'A')->count(),
                'score_b' => $results->where('grade', 'B')->count(),
                'score_c_or_below' => $results->whereIn('grade', ['C', 'D', 'F'])->count(),
            ],
        ]);
    }

    public function startBulkAudit(): JsonResponse
    {
        $progressKey = 'seo.bulk_audit.progress';
        $existing = Cache::get($progressKey);

        if ($existing && $existing['status'] === 'running') {
            return response()->json(['error' => 'Ya hay una auditoría en progreso.'], 409);
        }

        BulkSeoAuditJob::dispatch();

        return response()->json(['message' => 'Auditoría masiva iniciada.', 'status' => 'started']);
    }

    public function bulkAuditProgress(): JsonResponse
    {
        $progress = Cache::get('seo.bulk_audit.progress', ['status' => 'idle', 'processed' => 0, 'total' => 0]);

        return response()->json($progress);
    }

    public function startBrokenLinksCheck(): JsonResponse
    {
        $existing = Cache::get('seo.broken_links.progress');

        if ($existing && $existing['status'] === 'running') {
            return response()->json(['error' => 'Ya hay una verificación en progreso.'], 409);
        }

        CheckBrokenLinksJob::dispatch();

        return response()->json(['message' => 'Verificación iniciada.']);
    }

    public function brokenLinksProgress(): JsonResponse
    {
        $progress = Cache::get('seo.broken_links.progress', ['status' => 'idle', 'checked' => 0, 'total' => 0]);
        $results = Cache::get('seo.broken_links.results', []);

        return response()->json(array_merge($progress, ['broken' => $results]));
    }

    public function analyzeInternalLinks(): JsonResponse
    {
        $cacheKey = 'seo.internal_links.results';
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return response()->json(array_merge($cached, ['from_cache' => true]));
        }

        $urls = SeoMeta::whereNotNull('canonical_url')
            ->where('canonical_url', '!=', '')
            ->pluck('canonical_url');

        $results = (new InternalLinkAnalyzer)->findOrphansByLinks($urls);

        Cache::put($cacheKey, $results, 3600);

        return response()->json(array_merge($results, ['from_cache' => false]));
    }

    public function coreWebVitals(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url|max:500',
            'strategy' => 'nullable|in:mobile,desktop',
        ]);

        $url = $request->input('url');
        $strategy = $request->input('strategy', 'mobile');
        $apiKey = config('Seo.pagespeed_api_key', '');

        $params = ['url' => $url, 'strategy' => $strategy];
        if ($apiKey) {
            $params['key'] = $apiKey;
        }

        try {
            $response = Http::timeout(30)->get(
                'https://www.googleapis.com/pagespeedonline/v5/runPagespeed',
                $params
            );

            if ($response->failed()) {
                return response()->json(['error' => 'Error al consultar PageSpeed API: '.$response->status()], 422);
            }

            $data = $response->json();
            $categories = $data['lighthouseResult']['categories'] ?? [];
            $audits = $data['lighthouseResult']['audits'] ?? [];

            return response()->json([
                'performance_score' => round(($categories['performance']['score'] ?? 0) * 100),
                'seo_score' => round(($categories['seo']['score'] ?? 0) * 100),
                'accessibility_score' => round(($categories['accessibility']['score'] ?? 0) * 100),
                'best_practices_score' => round(($categories['best-practices']['score'] ?? 0) * 100),
                'lcp' => $audits['largest-contentful-paint']['displayValue'] ?? 'N/A',
                'fid' => $audits['total-blocking-time']['displayValue'] ?? 'N/A',
                'cls' => $audits['cumulative-layout-shift']['displayValue'] ?? 'N/A',
                'fcp' => $audits['first-contentful-paint']['displayValue'] ?? 'N/A',
                'ttfb' => $audits['server-response-time']['displayValue'] ?? 'N/A',
                'strategy' => $strategy,
            ]);
        } catch (\Throwable $e) {
            Log::error('SEO audit failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.'], 422);
        }
    }

    public function checkCanonicals(): JsonResponse
    {
        $metas = SeoMeta::query()
            ->whereNotNull('canonical_url')
            ->where('canonical_url', '!=', '')
            ->select(['id', 'title', 'canonical_url'])
            ->limit(100)
            ->get();

        if ($metas->isEmpty()) {
            return response()->json([
                'status' => true,
                'results' => [],
                'summary' => ['total' => 0, 'ok' => 0, 'broken' => 0],
            ]);
        }

        $results = $metas->map(function ($meta) {
            try {
                $response = Http::timeout(5)
                    ->withoutRedirecting()
                    ->head($meta->canonical_url);

                $status = $response->status();
                $ok = $response->successful() || in_array($status, [301, 302]);

                return [
                    'id' => $meta->id,
                    'title' => $meta->title ?? '(sin título)',
                    'canonical_url' => $meta->canonical_url,
                    'status' => $status,
                    'ok' => $ok,
                ];
            } catch (\Exception $e) {
                Log::error('SEO audit failed', ['error' => $e->getMessage()]);

                return [
                    'id' => $meta->id,
                    'title' => $meta->title ?? '(sin título)',
                    'canonical_url' => $meta->canonical_url,
                    'status' => 0,
                    'ok' => false,
                    'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
                ];
            }
        });

        return response()->json([
            'status' => true,
            'results' => $results->values(),
            'summary' => [
                'total' => $results->count(),
                'ok' => $results->where('ok', true)->count(),
                'broken' => $results->where('ok', false)->count(),
            ],
        ]);
    }
}

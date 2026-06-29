<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Seo\Models\Seo404Log;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Models\SeoRedirect;
use Modules\Seo\Models\SeoStaticUrl;
use Modules\Seo\Services\RedirectSuggestionService;

class Seo404Controller extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Seo.redirects.index');
    }

    public function index(): View
    {
        $search = request('search');
        $status = request('status');

        $logs = Seo404Log::query()
            ->when($search, fn ($q) => $q->where('path', 'like', "%{$search}%"))
            ->when($status === 'with_redirect', fn ($q) => $q->where('has_redirect', true))
            ->when($status === 'without_redirect', fn ($q) => $q->where('has_redirect', false))
            ->orderByHits()
            ->paginate(25)
            ->withQueryString();

        $row = Seo404Log::selectRaw('
            COUNT(*) as total_unique,
            SUM(hit_count) as total_hits,
            SUM(CASE WHEN has_redirect = 0 THEN 1 ELSE 0 END) as without_redirect,
            SUM(CASE WHEN last_seen_at >= ? THEN 1 ELSE 0 END) as recent_24h
        ', [now()->subDay()])->first();

        $stats = [
            'total_unique' => (int) $row->total_unique,
            'total_hits' => (int) $row->total_hits,
            'without_redirect' => (int) $row->without_redirect,
            'recent_24h' => (int) $row->recent_24h,
        ];

        return view('Seo::settings.404-logs.index', compact('logs', 'stats'));
    }

    /**
     * Redirect to the redirects create form with the 404 path pre-filled.
     */
    public function createRedirect(Seo404Log $log): RedirectResponse
    {
        $log->update(['has_redirect' => true]);

        return redirect()->route('settings.seo.redirects.create', [
            'source_path' => $log->path,
        ]);
    }

    /**
     * Mark a 404 log entry as resolved (has redirect).
     */
    public function markResolved(Seo404Log $log): JsonResponse
    {
        $log->update(['has_redirect' => true]);

        return response()->json(['status' => true]);
    }

    /**
     * Clear all 404 logs.
     */
    public function clear(Request $request): RedirectResponse
    {
        $request->validate(['confirm' => 'required|in:yes']);

        Seo404Log::query()->delete();

        return back()->with('success', 'Registros 404 eliminados correctamente.');
    }

    /**
     * Delete a single 404 log entry.
     */
    public function destroy(Seo404Log $log): RedirectResponse
    {
        $log->delete();

        return back()->with('success', 'Registro eliminado.');
    }

    /**
     * Suggest redirects for unresolved 404 logs.
     */
    public function suggestRedirects(): JsonResponse
    {
        $logs = Seo404Log::where('has_redirect', false)
            ->orderByDesc('hit_count')
            ->limit(50)
            ->get(['id', 'path', 'hit_count']);

        $existingPaths = SeoMeta::whereNotNull('canonical_url')
            ->where('canonical_url', '!=', '')
            ->pluck('canonical_url')
            ->map(fn ($url) => parse_url($url, PHP_URL_PATH))
            ->filter()
            ->values();

        $staticPaths = SeoStaticUrl::where('is_active', true)
            ->pluck('url')
            ->map(fn ($url) => parse_url($url, PHP_URL_PATH) ?: $url)
            ->filter()
            ->values();

        $existingPaths = $existingPaths->merge($staticPaths)->unique()->values();

        $suggestions = (new RedirectSuggestionService)->suggestForLogs($logs, $existingPaths);

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Perform a bulk action (delete or mark_resolved) on selected log entries.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'string', Rule::in(['delete', 'mark_resolved'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:seo_404_logs,id'],
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');

        $count = match ($action) {
            'delete' => Seo404Log::whereIn('id', $ids)->delete(),
            'mark_resolved' => Seo404Log::whereIn('id', $ids)->update(['has_redirect' => true]),
        };

        return response()->json(['success' => true, 'count' => $count, 'message' => $count.' entrada(s) actualizadas.']);
    }

    /**
     * Bulk create redirects from suggested or user-confirmed data.
     */
    public function bulkCreateRedirects(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'redirects' => 'required|array|max:100',
            'redirects.*.log_id' => 'required|integer|exists:seo_404_logs,id',
            'redirects.*.target' => 'required|string|max:500',
        ]);

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($validated, &$created, &$skipped) {
            foreach ($validated['redirects'] as $item) {
                $log = Seo404Log::find($item['log_id']);

                if (! $log || $log->has_redirect) {
                    $skipped++;

                    continue;
                }

                if (SeoRedirect::where('source_path', $log->path)->exists()) {
                    $skipped++;

                    continue;
                }

                SeoRedirect::create([
                    'source_path' => $log->path,
                    'target_path' => $item['target'],
                    'status_code' => 301,
                    'is_active' => true,
                ]);

                $log->update(['has_redirect' => true]);
                $created++;
            }
        });

        return response()->json([
            'created' => $created,
            'skipped' => $skipped,
            'message' => "{$created} redirecciones creadas, {$skipped} omitidas.",
        ]);
    }
}

<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Modules\Seo\Http\Requests\StoreSeoRedirectRequest;
use Modules\Seo\Http\Requests\UpdateSeoRedirectRequest;
use Modules\Seo\Models\SeoRedirect;
use Modules\Seo\Services\RedirectChainDetector;

class SeoRedirectController extends Controller
{
    /** @var array<string> */
    private const SORTABLE_COLUMNS = ['created_at', 'updated_at', 'source_path', 'target_path', 'status_code', 'hits_count'];

    public function __construct()
    {
        $this->middleware('can:Seo.redirects.index')->only('index', 'show', 'clearCache', 'detectChains');
        $this->middleware('can:Seo.redirects.create')->only('create', 'store');
        $this->middleware('can:Seo.redirects.update')->only('edit', 'update', 'toggleActive');
        $this->middleware('can:Seo.redirects.delete')->only('destroy', 'bulkDelete');
    }

    /**
     * Display a listing of the redirects.
     */
    public function index(Request $request): View
    {
        $query = SeoRedirect::query();

        // Search functionality
        if ($search = $request->get('search')) {
            $query->search($search);
        }

        // Filter by status code
        if ($statusCode = $request->get('status_code')) {
            $query->withStatusCode($statusCode);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sort by hits or created date
        $sortBy = in_array($request->get('sort_by'), self::SORTABLE_COLUMNS, true)
            ? $request->get('sort_by')
            : 'created_at';
        $sortDirection = $request->get('sort_direction') === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'hits_count') {
            $query->byHits($sortDirection);
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $redirects = $query->paginate(15)->withQueryString();

        // Statistics for overview cards
        $row = SeoRedirect::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
            SUM(CASE WHEN status_code = 301 THEN 1 ELSE 0 END) as permanent,
            SUM(CASE WHEN status_code = 302 THEN 1 ELSE 0 END) as temporary,
            COALESCE(SUM(hits_count), 0) as total_hits
        ')->first();

        $stats = [
            'total' => (int) ($row->total ?? 0),
            'active' => (int) ($row->active ?? 0),
            'inactive' => (int) ($row->inactive ?? 0),
            'permanent' => (int) ($row->permanent ?? 0),
            'temporary' => (int) ($row->temporary ?? 0),
            'total_hits' => (int) ($row->total_hits ?? 0),
        ];

        return view('Seo::settings.redirects.index', compact('redirects', 'stats'));
    }

    /**
     * Show the form for creating a new redirect.
     */
    public function create(): View
    {
        return view('Seo::settings.redirects.create');
    }

    /**
     * Store a newly created redirect in storage.
     */
    public function store(StoreSeoRedirectRequest $request): RedirectResponse
    {
        $redirect = SeoRedirect::create($request->validated());

        $this->clearRedirectCache($redirect->source_path);

        $chain = (new RedirectChainDetector)->detect($redirect->source_path);

        if (count($chain) > 2) {
            return redirect()
                ->route('setting.seo.redirects.index')
                ->with('warning', 'Redireccion creada. Advertencia: cadena de redirecciones detectada: '.implode(' → ', $chain));
        }

        return redirect()
            ->route('setting.seo.redirects.index')
            ->with('success', 'Redireccion creada correctamente.');
    }

    /**
     * Display the specified redirect.
     */
    public function show(SeoRedirect $redirect): View
    {
        return view('Seo::settings.redirects.show', compact('redirect'));
    }

    /**
     * Show the form for editing the specified redirect.
     */
    public function edit(SeoRedirect $redirect): View
    {
        return view('Seo::settings.redirects.edit', compact('redirect'));
    }

    /**
     * Update the specified redirect in storage.
     */
    public function update(UpdateSeoRedirectRequest $request, SeoRedirect $redirect): RedirectResponse
    {
        $oldSourcePath = $redirect->source_path;

        $redirect->update($request->validated());

        $this->clearRedirectCache($oldSourcePath);
        $this->clearRedirectCache($redirect->source_path);

        $chain = (new RedirectChainDetector)->detect($redirect->source_path);

        if (count($chain) > 2) {
            return redirect()
                ->route('setting.seo.redirects.index')
                ->with('warning', 'Redireccion actualizada. Advertencia: cadena de redirecciones detectada: '.implode(' → ', $chain));
        }

        return redirect()
            ->route('setting.seo.redirects.index')
            ->with('success', 'Redireccion actualizada correctamente.');
    }

    /**
     * Remove the specified redirect from storage.
     */
    public function destroy(SeoRedirect $redirect): RedirectResponse
    {
        $sourcePath = $redirect->source_path;

        $redirect->delete();

        // Clear cache
        $this->clearRedirectCache($sourcePath);

        return redirect()
            ->route('setting.seo.redirects.index')
            ->with('success', 'Redireccion eliminada correctamente.');
    }

    /**
     * Toggle the active status of a redirect.
     */
    public function toggleActive(SeoRedirect $redirect): RedirectResponse
    {
        $redirect->update(['is_active' => ! $redirect->is_active]);

        // Clear cache
        $this->clearRedirectCache($redirect->source_path);

        $status = $redirect->is_active ? 'activada' : 'desactivada';

        return back()->with('success', "Redireccion {$status} correctamente.");
    }

    /**
     * Bulk delete redirects.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = is_string($request->ids) ? json_decode($request->ids, true) : $request->ids;

        if (empty($ids) || ! is_array($ids)) {
            return back()->with('error', 'No se seleccionaron redirecciones.');
        }

        $redirects = SeoRedirect::whereIn('id', $ids)->get(['id', 'source_path']);

        DB::transaction(function () use ($redirects) {
            foreach ($redirects as $redirect) {
                $this->clearRedirectCache($redirect->source_path);
                $redirect->delete();
            }
        });

        return back()->with('success', count($ids).' redirecciones eliminadas correctamente.');
    }

    /**
     * Test a redirect by making an HTTP request to the source path.
     */
    public function test(SeoRedirect $redirect): JsonResponse
    {
        try {
            $response = Http::withoutRedirecting()->timeout(5)->get(url($redirect->source_path));

            return response()->json([
                'status' => $response->status(),
                'expected' => $redirect->status_code,
                'matches' => $response->status() === $redirect->status_code,
                'target' => $redirect->target_path,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Detect all redirect chains and return as JSON.
     */
    public function detectChains(): JsonResponse
    {
        $chains = (new RedirectChainDetector)->detectAll();

        return response()->json([
            'chains' => $chains,
            'count' => $chains->count(),
        ]);
    }

    /**
     * Clear all redirect caches.
     */
    public function clearCache(): RedirectResponse
    {
        SeoRedirect::query()->pluck('source_path')->each(
            fn (string $path) => $this->clearRedirectCache($path)
        );

        return back()->with('success', 'Cache de redirecciones limpiada correctamente.');
    }

    /**
     * Clear cache for a specific redirect path.
     */
    protected function clearRedirectCache(string $path): void
    {
        $cacheKey = 'seo_redirect_'.md5(strtolower($path));
        Cache::forget($cacheKey);
    }
}

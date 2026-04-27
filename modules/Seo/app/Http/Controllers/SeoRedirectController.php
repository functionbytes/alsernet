<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Seo\Http\Requests\ImportHtaccessRequest;
use Modules\Seo\Http\Requests\ImportRedirectsCsvRequest;
use Modules\Seo\Http\Requests\StoreSeoRedirectRequest;
use Modules\Seo\Http\Requests\UpdateSeoRedirectRequest;
use Modules\Seo\Models\SeoRedirect;
use Modules\Seo\Models\SeoRedirectHit;
use Modules\Seo\Services\RedirectChainDetector;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeoRedirectController extends Controller
{
    /** @var array<string> */
    private const SORTABLE_COLUMNS = ['created_at', 'updated_at', 'source_path', 'target_path', 'status_code', 'hits_count'];

    public function __construct()
    {
        $this->middleware('can:Seo.redirects.index')->only('index', 'show', 'test', 'clearCache', 'detectChains', 'analytics');
        $this->middleware('can:Seo.redirects.create')->only('create', 'store');
        $this->middleware('can:Seo.redirects.update')->only('edit', 'update', 'toggleActive', 'resolveChains');
        $this->middleware('can:Seo.redirects.delete')->only('destroy', 'bulkDelete');
        $this->middleware('can:Seo.redirects.index')->only('export', 'showImport', 'import', 'showHtaccessImport', 'importHtaccess');
    }

    /**
     * Display a listing of the redirects.
     */
    public function index(Request $request): View
    {
        $query = SeoRedirect::query();

        if ($search = $request->get('search')) {
            $query->search($search);
        }

        if ($statusCode = $request->get('status_code')) {
            $query->withStatusCode($statusCode);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

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

        $row = SeoRedirect::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
            SUM(CASE WHEN status_code = 301 THEN 1 ELSE 0 END) as permanent,
            SUM(CASE WHEN status_code = 302 THEN 1 ELSE 0 END) as temporary,
            SUM(CASE WHEN is_regex = 1 THEN 1 ELSE 0 END) as regex,
            SUM(CASE WHEN is_wildcard = 1 THEN 1 ELSE 0 END) as wildcard,
            COALESCE(SUM(hits_count), 0) as total_hits
        ')->first();

        $stats = [
            'total' => (int) ($row->total ?? 0),
            'active' => (int) ($row->active ?? 0),
            'inactive' => (int) ($row->inactive ?? 0),
            'permanent' => (int) ($row->permanent ?? 0),
            'temporary' => (int) ($row->temporary ?? 0),
            'regex' => (int) ($row->regex ?? 0),
            'wildcard' => (int) ($row->wildcard ?? 0),
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
                ->route('settings.seo.redirects.index')
                ->with('warning', 'Redireccion creada. Advertencia: cadena de redirecciones detectada: '.implode(' → ', $chain));
        }

        return redirect()
            ->route('settings.seo.redirects.index')
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
                ->route('settings.seo.redirects.index')
                ->with('warning', 'Redireccion actualizada. Advertencia: cadena de redirecciones detectada: '.implode(' → ', $chain));
        }

        return redirect()
            ->route('settings.seo.redirects.index')
            ->with('success', 'Redireccion actualizada correctamente.');
    }

    /**
     * Remove the specified redirect from storage.
     */
    public function destroy(SeoRedirect $redirect): RedirectResponse
    {
        $sourcePath = $redirect->source_path;

        $redirect->delete();

        $this->clearRedirectCache($sourcePath);

        return redirect()
            ->route('settings.seo.redirects.index')
            ->with('success', 'Redireccion eliminada correctamente.');
    }

    /**
     * Toggle the active status of a redirect.
     */
    public function toggleActive(SeoRedirect $redirect): RedirectResponse
    {
        $redirect->update(['is_active' => ! $redirect->is_active]);

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

        $ids = array_values(array_map('intval', array_filter($ids, fn ($id) => is_int($id) || ctype_digit((string) $id))));

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
     * Return 30-day hit analytics for a redirect as JSON.
     */
    public function analytics(SeoRedirect $redirect): JsonResponse
    {
        $hits = SeoRedirectHit::where('seo_redirect_id', $redirect->id)
            ->where('hit_date', '>=', now()->subDays(30)->toDateString())
            ->orderBy('hit_date')
            ->get(['hit_date', 'hit_count']);

        // Build a full 30-day range, filling missing days with 0
        $dateRange = collect();
        for ($i = 29; $i >= 0; $i--) {
            $dateRange[now()->subDays($i)->toDateString()] = 0;
        }

        foreach ($hits as $hit) {
            $dateRange[$hit->hit_date->toDateString()] = $hit->hit_count;
        }

        return response()->json([
            'labels' => $dateRange->keys()->toArray(),
            'data' => $dateRange->values()->toArray(),
            'total_30d' => $hits->sum('hit_count'),
            'redirect' => [
                'source_path' => $redirect->source_path,
                'target_path' => $redirect->target_path,
                'hits_count' => $redirect->hits_count,
            ],
        ]);
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
            Log::warning('Redirect test failed', ['redirect_id' => $redirect->id, 'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.']);

            return response()->json(['error' => 'No se pudo realizar la petición de prueba.'], 422);
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
     * Flatten all detected redirect chains (A→B→C becomes A→C).
     */
    public function resolveChains(): RedirectResponse
    {
        $updated = (new RedirectChainDetector)->resolveAll();

        SeoRedirect::flushPatternCache();

        return back()->with('success', "Se aplanaron {$updated} cadenas de redirects.");
    }

    /**
     * Export all redirects as a CSV file.
     */
    public function export(): StreamedResponse
    {
        $filename = 'redirects_'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['source_path', 'target_path', 'status_code', 'is_active', 'hits_count']);

            SeoRedirect::query()->orderBy('source_path')->each(function (SeoRedirect $redirect) use ($handle) {
                fputcsv($handle, [
                    $redirect->source_path,
                    $redirect->target_path,
                    $redirect->status_code,
                    $redirect->is_active ? '1' : '0',
                    $redirect->hits_count,
                ]);
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Show the import form.
     */
    public function showImport(): View
    {
        return view('Seo::settings.redirects.import');
    }

    /**
     * Import redirects from a CSV file.
     */
    public function import(ImportRedirectsCsvRequest $request): RedirectResponse
    {
        $file = $request->file('csv_file');
        $handle = fopen($file->getPathname(), 'r');

        $header = fgetcsv($handle);

        $expected = ['source_path', 'target_path', 'status_code'];
        if (! $header || ! array_intersect($expected, $header)) {
            fclose($handle);

            return back()->with('error', 'Formato CSV inválido. Se requieren columnas: source_path, target_path, status_code');
        }

        $headerMap = array_flip($header);
        $imported = 0;
        $skipped = 0;
        $errors = 0;
        $skipExisting = $request->boolean('skip_existing', true);

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $sourcePath = trim($row[$headerMap['source_path']] ?? '');
                $targetPath = trim($row[$headerMap['target_path']] ?? '');
                $statusCode = (int) ($row[$headerMap['status_code']] ?? 301);

                if (empty($sourcePath) || empty($targetPath)) {
                    $errors++;

                    continue;
                }

                if (! in_array($statusCode, [301, 302, 307, 308])) {
                    $statusCode = 301;
                }

                $exists = SeoRedirect::where('source_path', $sourcePath)->exists();

                if ($exists && $skipExisting) {
                    $skipped++;

                    continue;
                }

                SeoRedirect::updateOrCreate(
                    ['source_path' => $sourcePath],
                    ['target_path' => $targetPath, 'status_code' => $statusCode, 'is_active' => true]
                );
                $imported++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);

            Log::error('SEO redirect import failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Error durante la importación. Por favor, inténtalo de nuevo.');
        }

        fclose($handle);

        SeoRedirect::pluck('source_path')->each(
            fn (string $path) => $this->clearRedirectCache($path)
        );

        return redirect()
            ->route('settings.seo.redirects.index')
            ->with('success', "Importación completada: {$imported} importados, {$skipped} omitidos, {$errors} con error.");
    }

    /**
     * Show the .htaccess import form.
     */
    public function showHtaccessImport(): View
    {
        return view('Seo::settings.redirects.htaccess-import');
    }

    /**
     * Import redirects from pasted .htaccess content.
     */
    public function importHtaccess(ImportHtaccessRequest $request): RedirectResponse
    {
        $content = $request->input('htaccess_content');
        $skipExisting = $request->boolean('skip_existing', true);
        $lines = explode("\n", $content);

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        DB::beginTransaction();
        try {
            foreach ($lines as $line) {
                $line = trim($line);

                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }

                if (preg_match('/^Redirect\s+(\d{3})\s+(\S+)\s+(\S+)/i', $line, $m)) {
                    $statusCode = (int) $m[1];
                    $sourcePath = $m[2];
                    $targetPath = $m[3];

                    if (! in_array($statusCode, [301, 302, 307, 308])) {
                        $errors++;

                        continue;
                    }
                } elseif (preg_match('/^RedirectPermanent\s+(\S+)\s+(\S+)/i', $line, $m)) {
                    $sourcePath = $m[1];
                    $targetPath = $m[2];
                    $statusCode = 301;
                } elseif (preg_match('/^RewriteRule\s+(\S+)\s+(\S+)\s+\[([^\]]+)\]/i', $line, $m)) {
                    $flags = $m[3];
                    if (! str_contains(strtolower($flags), 'r=')) {
                        continue;
                    }
                    preg_match('/r=(\d{3})/i', $flags, $statusMatch);
                    $statusCode = (int) ($statusMatch[1] ?? 301);
                    $sourcePath = '/'.ltrim($m[1], '^');
                    $sourcePath = rtrim($sourcePath, '$');
                    $targetPath = $m[2];
                } else {
                    continue;
                }

                if (empty($sourcePath) || empty($targetPath)) {
                    $errors++;

                    continue;
                }

                $exists = SeoRedirect::where('source_path', $sourcePath)->exists();
                if ($exists && $skipExisting) {
                    $skipped++;

                    continue;
                }

                SeoRedirect::updateOrCreate(
                    ['source_path' => $sourcePath],
                    ['target_path' => $targetPath, 'status_code' => $statusCode, 'is_active' => true]
                );
                $imported++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('SEO redirect import failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Error durante la importación. Por favor, inténtalo de nuevo.');
        }

        return redirect()
            ->route('settings.seo.redirects.index')
            ->with('success', "Importación .htaccess: {$imported} importados, {$skipped} omitidos, {$errors} con error.");
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
        Cache::forget(SeoRedirect::cacheKey($path));
    }
}

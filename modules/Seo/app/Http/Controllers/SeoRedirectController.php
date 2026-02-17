<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Seo\Http\Requests\StoreSeoRedirectRequest;
use Modules\Seo\Http\Requests\UpdateSeoRedirectRequest;
use Modules\Seo\Models\SeoRedirect;

class SeoRedirectController extends Controller
{
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
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if ($sortBy === 'hits_count') {
            $query->byHits($sortDirection);
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $redirects = $query->paginate(15)->withQueryString();

        // Statistics for overview cards
        $stats = [
            'total' => SeoRedirect::count(),
            'active' => SeoRedirect::where('is_active', true)->count(),
            'inactive' => SeoRedirect::where('is_active', false)->count(),
            'permanent' => SeoRedirect::where('status_code', 301)->count(),
            'temporary' => SeoRedirect::where('status_code', 302)->count(),
            'total_hits' => SeoRedirect::sum('hits_count'),
        ];

        return view('Seo::admin.redirects.index', compact('redirects', 'stats'));
    }

    /**
     * Show the form for creating a new redirect.
     */
    public function create(): View
    {
        return view('Seo::admin.redirects.create');
    }

    /**
     * Store a newly created redirect in storage.
     */
    public function store(StoreSeoRedirectRequest $request): RedirectResponse
    {
        $redirect = SeoRedirect::create($request->validated());

        // Clear cache for this redirect
        $this->clearRedirectCache($redirect->source_path);

        return redirect()
            ->route('setting.seo.redirects.index')
            ->with('success', 'Redireccion creada correctamente.');
    }

    /**
     * Display the specified redirect.
     */
    public function show(SeoRedirect $redirect): View
    {
        return view('Seo::admin.redirects.show', compact('redirect'));
    }

    /**
     * Show the form for editing the specified redirect.
     */
    public function edit(SeoRedirect $redirect): View
    {
        return view('Seo::admin.redirects.edit', compact('redirect'));
    }

    /**
     * Update the specified redirect in storage.
     */
    public function update(UpdateSeoRedirectRequest $request, SeoRedirect $redirect): RedirectResponse
    {
        $oldSourcePath = $redirect->source_path;

        $redirect->update($request->validated());

        // Clear cache for both old and new source paths
        $this->clearRedirectCache($oldSourcePath);
        $this->clearRedirectCache($redirect->source_path);

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

        $redirects = SeoRedirect::whereIn('id', $ids)->get();

        foreach ($redirects as $redirect) {
            $this->clearRedirectCache($redirect->source_path);
            $redirect->delete();
        }

        return back()->with('success', count($ids).' redirecciones eliminadas correctamente.');
    }

    /**
     * Clear all redirect caches.
     */
    public function clearCache(): RedirectResponse
    {
        // Clear all redirect caches
        $redirects = SeoRedirect::all();

        foreach ($redirects as $redirect) {
            $this->clearRedirectCache($redirect->source_path);
        }

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

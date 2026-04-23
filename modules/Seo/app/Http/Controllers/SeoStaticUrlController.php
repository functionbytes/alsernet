<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Seo\Http\Requests\StoreSeoStaticUrlRequest;
use Modules\Seo\Http\Requests\UpdateSeoStaticUrlRequest;
use Modules\Seo\Models\SeoStaticUrl;

class SeoStaticUrlController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Seo.static-urls.index')->only('index', 'create');
        $this->middleware('can:Seo.static-urls.create')->only('store');
        $this->middleware('can:Seo.static-urls.update')->only('edit', 'update', 'toggleActive');
        $this->middleware('can:Seo.static-urls.delete')->only('destroy');
    }

    /**
     * Display a listing of the static URLs.
     */
    public function index(): View
    {
        $search = request('search');
        $status = request('status');

        $staticUrls = SeoStaticUrl::query()
            ->when($search, fn ($q) => $q->where('url', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%"))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('url')
            ->paginate(20)
            ->withQueryString();

        $row = SeoStaticUrl::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
        ')->first();

        $total = (int) $row->total;
        $totalActive = (int) $row->active;

        return view('Seo::settings.static-urls.index', compact('staticUrls', 'totalActive', 'total'));
    }

    /**
     * Show the form for creating a new static URL.
     */
    public function create(): View
    {
        return view('Seo::settings.static-urls.create');
    }

    /**
     * Store a newly created static URL in storage.
     */
    public function store(StoreSeoStaticUrlRequest $request): RedirectResponse
    {
        SeoStaticUrl::create($request->validated());

        return redirect()
            ->route('setting.seo.static-urls.index')
            ->with('success', __('seo::static-urls.created_successfully'));
    }

    /**
     * Show the form for editing the specified static URL.
     */
    public function edit(SeoStaticUrl $seoStaticUrl): View
    {
        return view('Seo::settings.static-urls.edit', compact('seoStaticUrl'));
    }

    /**
     * Update the specified static URL in storage.
     */
    public function update(UpdateSeoStaticUrlRequest $request, SeoStaticUrl $seoStaticUrl): RedirectResponse
    {
        $seoStaticUrl->update($request->validated());

        return redirect()
            ->route('setting.seo.static-urls.index')
            ->with('success', __('seo::static-urls.updated_successfully'));
    }

    /**
     * Remove the specified static URL from storage.
     */
    public function destroy(SeoStaticUrl $seoStaticUrl): RedirectResponse
    {
        $seoStaticUrl->delete();

        return redirect()
            ->route('setting.seo.static-urls.index')
            ->with('success', __('seo::static-urls.deleted_successfully'));
    }

    /**
     * Toggle the active status of a static URL.
     */
    public function toggleActive(SeoStaticUrl $seoStaticUrl): RedirectResponse
    {
        $seoStaticUrl->update(['is_active' => ! $seoStaticUrl->is_active]);

        $messageKey = $seoStaticUrl->is_active
            ? 'seo::static-urls.activated'
            : 'seo::static-urls.deactivated';

        return back()->with('success', __($messageKey));
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'string', Rule::in(['activate', 'deactivate', 'delete'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:seo_static_urls,id'],
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');

        $count = match ($action) {
            'activate' => SeoStaticUrl::query()->whereIn('id', $ids)->update(['is_active' => true]),
            'deactivate' => SeoStaticUrl::query()->whereIn('id', $ids)->update(['is_active' => false]),
            'delete' => SeoStaticUrl::query()->whereIn('id', $ids)->delete(),
        };

        return response()->json([
            'success' => true,
            'count' => $count,
            'message' => $count.' URL(s) actualizadas.',
        ]);
    }
}

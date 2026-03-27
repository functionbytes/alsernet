<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Seo\Http\Requests\UpdateSeoMetaRequest;
use Modules\Seo\Models\SeoMeta;

class SeoMetaWebController extends Controller
{
    /** @var array<string> */
    private const SORTABLE_COLUMNS = ['updated_at', 'created_at', 'title'];

    public function __construct()
    {
        $this->middleware('can:Seo.metas.index')->only('index', 'show');
        $this->middleware('can:Seo.metas.update')->only('edit', 'update');
        $this->middleware('can:Seo.metas.delete')->only('destroy', 'bulkDelete');
    }

    /**
     * Display a listing of SEO meta records with filters and statistics.
     */
    public function index(Request $request): View
    {
        $query = SeoMeta::with('seoable');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($seoableType = $request->get('seoable_type')) {
            $query->forType($seoableType);
        }

        if ($robots = $request->get('robots')) {
            $query->withRobots($robots);
        }

        $sortBy = in_array($request->get('sort_by'), self::SORTABLE_COLUMNS, true)
            ? $request->get('sort_by')
            : 'updated_at';
        $query->orderBy($sortBy, 'desc');

        $metas = $query->paginate(15)->withQueryString();

        $seoableTypes = SeoMeta::selectRaw('DISTINCT seoable_type')
            ->whereNotNull('seoable_type')
            ->pluck('seoable_type');

        $stats = [
            'total' => SeoMeta::count(),
            'indexable' => SeoMeta::where('robots', 'like', '%index%')
                ->where('robots', 'not like', '%noindex%')
                ->count(),
            'noindex' => SeoMeta::where('robots', 'like', '%noindex%')->count(),
            'missing_description' => SeoMeta::whereNull('description')->count(),
            'missing_og_image' => SeoMeta::whereNull('og_image')->count(),
        ];

        return view('Seo::settings.metas.index', compact('metas', 'seoableTypes', 'stats'));
    }

    /**
     * Display the specified SEO meta record.
     */
    public function show(SeoMeta $meta): View
    {
        $meta->load('seoable');

        return view('Seo::settings.metas.show', compact('meta'));
    }

    /**
     * Show the form for editing the specified SEO meta.
     */
    public function edit(SeoMeta $meta): View
    {
        $meta->load('seoable');

        return view('Seo::settings.metas.edit', compact('meta'));
    }

    /**
     * Update the specified SEO meta in storage.
     */
    public function update(UpdateSeoMetaRequest $request, SeoMeta $meta): RedirectResponse
    {
        $meta->update($request->validated());

        return redirect()
            ->route('setting.seo.metas.show', $meta)
            ->with('success', 'Meta SEO actualizado correctamente.');
    }

    /**
     * Remove the specified SEO meta from storage.
     */
    public function destroy(SeoMeta $meta): RedirectResponse
    {
        $meta->delete();

        return redirect()
            ->route('setting.seo.metas.index')
            ->with('success', 'Meta SEO eliminado correctamente.');
    }

    /**
     * Bulk delete SEO meta records.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = is_string($request->ids) ? json_decode($request->ids, true) : $request->ids;

        if (empty($ids) || ! is_array($ids)) {
            return back()->with('error', 'No se seleccionaron registros.');
        }

        SeoMeta::whereIn('id', $ids)->delete();

        return back()->with('success', count($ids).' registros meta SEO eliminados correctamente.');
    }
}

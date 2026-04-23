<?php

namespace Modules\Seo\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Seo\Http\Requests\StoreSeoMetaRequest;
use Modules\Seo\Http\Requests\UpdateSeoMetaRequest;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Services\SeoService;

class SeoMetaController extends Controller
{
    public function __construct(
        protected SeoService $seoService
    ) {
        $this->middleware('can:Seo.metas.index')->only('index', 'show', 'statistics', 'preview');
        $this->middleware('can:Seo.metas.create')->only('store');
        $this->middleware('can:Seo.metas.update')->only('update', 'bulkUpdate');
        $this->middleware('can:Seo.metas.delete')->only('destroy');
    }

    /**
     * Display a listing of SEO meta records.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SeoMeta::query();

        // Filter by seoable type
        if ($request->has('seoable_type')) {
            $query->forType($request->input('seoable_type'));
        }

        // Filter by robots directive
        if ($request->has('robots')) {
            $query->withRobots($request->input('robots'));
        }

        // Search in title or description
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $seoMetas = $query->with('seoable')
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json($seoMetas);
    }

    /**
     * Store a newly created SEO meta record.
     */
    public function store(StoreSeoMetaRequest $request): JsonResponse
    {
        $seoMeta = SeoMeta::create($request->validated());

        return response()->json([
            'message' => 'SEO meta created successfully',
            'data' => $seoMeta->load('seoable'),
        ], 201);
    }

    /**
     * Display the specified SEO meta record.
     */
    public function show(SeoMeta $seoMeta): JsonResponse
    {
        return response()->json([
            'data' => $seoMeta->load('seoable'),
        ]);
    }

    /**
     * Update the specified SEO meta record.
     */
    public function update(UpdateSeoMetaRequest $request, SeoMeta $seoMeta): JsonResponse
    {
        $seoMeta->update($request->validated());

        return response()->json([
            'message' => 'SEO meta updated successfully',
            'data' => $seoMeta->fresh(['seoable']),
        ]);
    }

    /**
     * Remove the specified SEO meta record.
     */
    public function destroy(SeoMeta $seoMeta): JsonResponse
    {
        $seoMeta->delete();

        return response()->json([
            'message' => 'SEO meta deleted successfully',
        ]);
    }

    /**
     * Generate a preview of how the SEO meta will appear.
     */
    public function preview(Request $request): JsonResponse
    {
        $seoService = new SeoService;

        if ($request->has('title')) {
            $seoService->setTitle($request->input('title'));
        }

        if ($request->has('description')) {
            $seoService->setDescription($request->input('description'));
        }

        if ($request->has('og_title')) {
            $seoService->setOgTitle($request->input('og_title'));
        }

        if ($request->has('og_description')) {
            $seoService->setOgDescription($request->input('og_description'));
        }

        if ($request->has('og_image')) {
            $seoService->setOgImage($request->input('og_image'));
        }

        if ($request->has('twitter_title')) {
            $seoService->setTwitterTitle($request->input('twitter_title'));
        }

        if ($request->has('twitter_description')) {
            $seoService->setTwitterDescription($request->input('twitter_description'));
        }

        if ($request->has('twitter_image')) {
            $seoService->setTwitterImage($request->input('twitter_image'));
        }

        return response()->json([
            'preview' => $seoService->generatePreview(),
            'html' => $seoService->render(),
        ]);
    }

    /**
     * Bulk update SEO meta records.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:seo_metas,id',
            'data' => 'required|array',
        ]);

        $updated = SeoMeta::whereIn('id', $request->input('ids'))
            ->update(array_filter($request->input('data'), fn ($value) => ! is_null($value)));

        return response()->json([
            'message' => "Successfully updated {$updated} SEO meta records",
            'updated' => $updated,
        ]);
    }

    /**
     * Get statistics about SEO meta records.
     */
    public function statistics(): JsonResponse
    {
        $row = SeoMeta::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN robots LIKE ? THEN 1 ELSE 0 END) as indexable,
            SUM(CASE WHEN robots LIKE ? THEN 1 ELSE 0 END) as noindex,
            SUM(CASE WHEN description IS NULL THEN 1 ELSE 0 END) as missing_description,
            SUM(CASE WHEN og_image IS NULL THEN 1 ELSE 0 END) as missing_og_image
        ', ['%index%', '%noindex%'])->first();

        $byType = SeoMeta::query()
            ->selectRaw('seoable_type, COUNT(*) as count')
            ->groupBy('seoable_type')
            ->pluck('count', 'seoable_type');

        $stats = [
            'total' => (int) $row->total,
            'indexable' => (int) $row->indexable,
            'noindex' => (int) $row->noindex,
            'by_type' => $byType,
            'missing_description' => (int) $row->missing_description,
            'missing_og_image' => (int) $row->missing_og_image,
        ];

        return response()->json($stats);
    }
}

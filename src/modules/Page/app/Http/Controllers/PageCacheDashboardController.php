<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageCacheAudit;
use Modules\Page\Services\PageCacheService;

class PageCacheDashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Page::class);

        return view('page::cache.dashboard');
    }

    public function stats(): JsonResponse
    {
        $this->authorize('viewAny', Page::class);

        return response()->json(PageCacheService::getStats());
    }

    public function audits(): JsonResponse
    {
        $this->authorize('viewAny', Page::class);

        $audits = PageCacheAudit::latest()
            ->limit(50)
            ->get()
            ->map(fn ($audit) => [
                'id' => $audit->id,
                'action' => $audit->action,
                'page_id' => $audit->page_id,
                'slug' => $audit->slug,
                'user' => $audit->user?->name,
                'created_at' => $audit->created_at->diffForHumans(),
            ]);

        return response()->json($audits);
    }

    public function warm(): JsonResponse
    {
        $this->authorize('viewAny', Page::class);

        $count = PageCacheService::warmPopular(999);

        return response()->json([
            'success' => true,
            'message' => "Warmed cache for {$count} pages",
            'count' => $count,
        ]);
    }

    public function clear(): JsonResponse
    {
        $this->authorize('viewAny', Page::class);

        PageCacheService::flushAll();

        return response()->json([
            'success' => true,
            'message' => 'Page cache cleared',
        ]);
    }
}

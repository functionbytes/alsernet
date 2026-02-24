<?php

namespace Modules\Page\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Page\Models\PageCacheAudit;
use Modules\Page\Services\PageCacheService;

class PageCacheController extends Controller
{
    public function stats(): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Page\Models\Page::class);

        return response()->json(PageCacheService::getStats());
    }

    public function warmAll(): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Page\Models\Page::class);

        $count = PageCacheService::warmPopular(999);

        return response()->json([
            'success' => true,
            'message' => "Warmed cache for {$count} pages",
            'count' => $count,
        ]);
    }

    public function clear(): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Page\Models\Page::class);

        PageCacheService::flushAll();

        return response()->json([
            'success' => true,
            'message' => 'Page cache cleared',
        ]);
    }

    public function audits(): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Page\Models\Page::class);

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
}

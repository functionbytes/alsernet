<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Page\Jobs\ScanPagePerformanceJob;
use Modules\Page\Models\Page;
use Modules\Page\Models\PagePerformanceMetric;

class PagePerformanceController extends Controller
{
    public function scan(Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        ScanPagePerformanceJob::dispatch($page, 'mobile');
        ScanPagePerformanceJob::dispatch($page, 'desktop');

        return response()->json([
            'message' => 'Análisis iniciado. Los resultados estarán disponibles en unos minutos.',
        ]);
    }

    public function show(Page $page): JsonResponse
    {
        $this->authorize('view', $page);

        $mobile = $page->performanceMetrics()
            ->where('strategy', 'mobile')
            ->latest()
            ->first();

        $desktop = $page->performanceMetrics()
            ->where('strategy', 'desktop')
            ->latest()
            ->first();

        return response()->json(compact('mobile', 'desktop'));
    }

    public function top(): JsonResponse
    {
        $this->authorize('viewAny', Page::class);

        $pages = PagePerformanceMetric::query()
            ->where('strategy', 'mobile')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                    ->from('page_performance_metrics')
                    ->where('strategy', 'mobile')
                    ->groupBy('page_id');
            })
            ->with('page:id,title,slug')
            ->orderByDesc('performance_score')
            ->limit(10)
            ->get();

        return response()->json($pages);
    }
}

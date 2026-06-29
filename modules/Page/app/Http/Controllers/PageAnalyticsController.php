<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Page\Exports\PageAnalyticsExport;
use Modules\Page\Models\Page;
use Modules\Page\Services\PageAnalyticsService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PageAnalyticsController extends Controller
{
    public function __construct(
        private readonly PageAnalyticsService $analyticsService,
    ) {}

    /**
     * Return granular analytics for a single page.
     */
    public function show(Page $page, Request $request): JsonResponse
    {
        $this->authorize('update', $page);

        $days = min((int) $request->get('days', 30), 365);

        return response()->json([
            'views_by_day' => $this->analyticsService->viewsByDay($page, $days),
            'views_by_device' => $this->analyticsService->viewsByDevice($page, $days),
            'views_by_browser' => $this->analyticsService->viewsByBrowser($page, $days),
            'views_by_country' => $this->analyticsService->viewsByCountry($page, $days),
            'views_by_locale' => $this->analyticsService->viewsByLocale($page, $days),
            'top_referrers' => $this->analyticsService->topReferrers($page, $days),
            'summary' => $this->analyticsService->summary($page, $days),
            'previous_summary' => $this->analyticsService->summary($page, $days, true),
            'seo' => [
                'keywords' => $page->seo_keywords,
                'title' => $page->seo_title,
                'description' => $page->seo_description,
                'noindex' => $page->seo_noindex,
            ],
        ]);
    }

    /**
     * Return a global overview: top pages by views.
     */
    public function overview(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Page::class);

        $days = min((int) $request->get('days', 30), 365);

        return response()->json([
            'top_pages' => $this->analyticsService->topPages($days),
        ]);
    }

    /**
     * Export analytics data for a page as CSV or Excel.
     * GET /pages/{page}/analytics/export?days=30&format=csv|excel
     */
    public function export(Page $page, Request $request): StreamedResponse|Response
    {
        $this->authorize('update', $page);

        $days = min((int) $request->get('days', 30), 365);
        $format = $request->get('format', 'csv');
        $data = $this->analyticsService->exportViewsData($page, $days);
        $filename = "analytics-{$page->slug}-{$days}d-".now()->format('Ymd');

        if ($format === 'excel') {
            return Excel::download(new PageAnalyticsExport($data), $filename.'.xlsx');
        }

        return $this->exportCsv($data, $filename);
    }

    private function exportCsv(array $data, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($data) {
            $output = fopen('php://output', 'w');

            // BOM for UTF-8 Excel compatibility
            fwrite($output, "\xEF\xBB\xBF");

            if (! empty($data)) {
                fputcsv($output, array_keys($data[0]), ';');
                foreach ($data as $row) {
                    fputcsv($output, $row, ';');
                }
            }

            fclose($output);
        }, $filename.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ]);
    }
}

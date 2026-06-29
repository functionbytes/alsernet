<?php

namespace Modules\Page\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Modules\Page\Models\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PageExportService
{
    /** @var array<string> */
    private array $columns = [
        'id', 'title', 'slug', 'status', 'template',
        'description', 'content', 'published_at', 'created_at',
    ];

    public function exportCsv(Enumerable $pages): StreamedResponse
    {
        return response()->streamDownload(function () use ($pages) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel compatibility
            fputcsv($out, $this->columns, ';');

            foreach ($pages as $page) {
                fputcsv($out, $this->pageToRow($page), ';');
            }

            fclose($out);
        }, 'pages-export-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportJson(Collection $pages): JsonResponse
    {
        return response()->json(
            $pages->map(fn (Page $p) => $this->pageToRow($p))->values(),
            200,
            ['Content-Disposition' => 'attachment; filename="pages-export-'.now()->format('Ymd').'.json"']
        );
    }

    /** @return array<string, mixed> */
    private function pageToRow(Page $page): array
    {
        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'status' => $page->status,
            'template' => $page->template ?? 'default',
            'description' => strip_tags($page->description ?? ''),
            'content' => $page->content ?? '',
            'published_at' => $page->published_at?->format('Y-m-d H:i:s') ?? '',
            'created_at' => $page->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

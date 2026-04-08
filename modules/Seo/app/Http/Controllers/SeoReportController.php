<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Models\SeoRedirect;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeoReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Seo.metas.index');
    }

    public function index(): View
    {
        $stats = [
            'total_metas' => SeoMeta::count(),
            'avg_score' => round(SeoMeta::whereNotNull('seo_score')->avg('seo_score') ?? 0, 1),
            'total_redirects' => SeoRedirect::count(),
        ];

        return view('Seo::settings.report.index', compact('stats'));
    }

    public function export(Request $request): StreamedResponse
    {
        $format = $request->get('format', 'csv');
        $filename = 'seo-report-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($format) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Header
            fputcsv($handle, [
                'ID', 'Tipo', 'Modelo', 'Titulo SEO', 'Descripcion', 'Keywords',
                'OG Titulo', 'OG Imagen', 'Twitter Card', 'Canonical URL',
                'Robots', 'Score SEO', 'Grado', 'Ultima auditoria', 'Actualizado',
            ]);

            SeoMeta::with('seoable')->orderBy('seo_score', 'asc')->each(function ($meta) use ($handle) {
                fputcsv($handle, [
                    $meta->id,
                    class_basename($meta->seoable_type ?? ''),
                    $meta->seoable?->title ?? $meta->seoable?->name ?? "#{$meta->seoable_id}",
                    $meta->title ?? '',
                    $meta->description ?? '',
                    $meta->keywords ?? '',
                    $meta->og_title ?? '',
                    $meta->og_image ?? '',
                    $meta->twitter_card ?? '',
                    $meta->canonical_url ?? '',
                    $meta->robots ?? 'index,follow',
                    $meta->seo_score ?? '',
                    $meta->seo_grade ?? '',
                    $meta->seo_audited_at?->format('Y-m-d H:i') ?? '',
                    $meta->updated_at->format('Y-m-d H:i'),
                ]);
            });

            // Add redirects section if full report
            if ($format === 'full') {
                fputcsv($handle, []);
                fputcsv($handle, ['--- REDIRECCIONES ---']);
                fputcsv($handle, ['Source Path', 'Target Path', 'Codigo', 'Activa', 'Hits']);

                SeoRedirect::orderBy('hits_count', 'desc')->each(function ($r) use ($handle) {
                    fputcsv($handle, [
                        $r->source_path, $r->target_path, $r->status_code,
                        $r->is_active ? 'Si' : 'No', $r->hits_count,
                    ]);
                });
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function exportPdf(): Response
    {
        $metas = SeoMeta::with('seoable')
            ->select(['id', 'title', 'description', 'robots', 'seo_score', 'seo_grade', 'seo_audited_at', 'canonical_url', 'seoable_type', 'seoable_id'])
            ->orderByDesc('seo_score')
            ->limit(500)
            ->get();

        $stats = [
            'total_metas' => SeoMeta::count(),
            'avg_score' => round(SeoMeta::whereNotNull('seo_score')->avg('seo_score') ?? 0, 1),
            'total_redirects' => SeoRedirect::count(),
            'grade_distribution' => SeoMeta::whereNotNull('seo_grade')
                ->selectRaw('seo_grade, COUNT(*) as count')
                ->groupBy('seo_grade')
                ->pluck('count', 'seo_grade')
                ->toArray(),
        ];

        $generatedAt = now()->format('d/m/Y H:i');
        $html = view('Seo::settings.report.pdf', compact('metas', 'stats', 'generatedAt'))->render();

        $filename = 'seo-report-'.now()->format('Y-m-d').'.pdf';

        // barryvdh/laravel-dompdf is not installed — stream as HTML download
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.html\"",
        ]);
    }
}

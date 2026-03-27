<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Reviews\Enums\ReportStatus;
use Modules\Reviews\Http\Requests\GenerateReportRequest;
use Modules\Reviews\Jobs\GenerateReportJob;
use Modules\Reviews\Models\GeneratedReport;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        $locations = ReviewGoogleLocation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $recentReports = GeneratedReport::query()
            ->where('user_id', auth()->id())
            ->recent(30)
            ->latest()
            ->limit(10)
            ->get();

        return view('reviews::reports.index', compact('locations', 'recentReports'));
    }

    public function generate(GenerateReportRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $filename = $this->generateFilename($validated['report_type'], $validated['format']);

        $generatedReport = GeneratedReport::create([
            'user_id' => auth()->id(),
            'report_type' => $validated['report_type'],
            'format' => $validated['format'],
            'filename' => $filename,
            'filepath' => '',
            'filters' => $validated,
            'status' => ReportStatus::Processing,
        ]);

        GenerateReportJob::dispatch($generatedReport);

        return redirect()
            ->route('reviews.reports.index')
            ->with('success', 'Reporte en cola para generación. Recibirás una notificación cuando esté listo.');
    }

    public function download(GeneratedReport $generatedReport): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $generatedReport);

        if (! $generatedReport->isCompleted()) {
            return redirect()
                ->route('reviews.reports.index')
                ->with('error', 'El reporte aún no está disponible.');
        }

        if ($generatedReport->isExpired()) {
            return redirect()
                ->route('reviews.reports.index')
                ->with('error', 'Este reporte ha expirado.');
        }

        if (! Storage::disk('local')->exists($generatedReport->filepath)) {
            return redirect()
                ->route('reviews.reports.index')
                ->with('error', 'El archivo del reporte no fue encontrado.');
        }

        return Storage::disk('local')->download(
            $generatedReport->filepath,
            $generatedReport->filename
        );
    }

    public function list(): JsonResponse
    {
        $this->authorize('viewAny', GeneratedReport::class);

        $reports = GeneratedReport::query()
            ->where('user_id', auth()->id())
            ->recent(30)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($report) => [
                'id' => $report->id,
                'report_type' => $report->report_type,
                'format' => $report->format,
                'filename' => $report->filename,
                'file_size' => $report->getFileSizeFormatted(),
                'status' => $report->status,
                'created_at' => $report->created_at->format('Y-m-d H:i:s'),
                'completed_at' => $report->completed_at?->format('Y-m-d H:i:s'),
                'download_url' => $report->isCompleted() && ! $report->isExpired() ? $report->getDownloadUrl() : null,
                'is_expired' => $report->isExpired(),
            ]);

        return response()->json($reports);
    }

    public function destroy(GeneratedReport $generatedReport): RedirectResponse
    {
        $this->authorize('delete', $generatedReport);

        $generatedReport->deleteFile();
        $generatedReport->delete();

        return redirect()
            ->route('reviews.reports.index')
            ->with('success', 'Reporte eliminado correctamente.');
    }

    private function generateFilename(string $reportType, string $format): string
    {
        $typeSlug = str_replace('_', '-', $reportType);
        $timestamp = now()->format('Y-m-d_His');

        $extension = match ($format) {
            'excel' => 'xlsx',
            'csv' => 'csv',
            'pdf' => 'pdf',
            default => 'txt',
        };

        return "report_{$typeSlug}_{$timestamp}.{$extension}";
    }
}

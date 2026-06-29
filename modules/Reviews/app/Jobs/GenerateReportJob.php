<?php

namespace Modules\Reviews\Jobs;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Reviews\Exports\ReportExport;
use Modules\Reviews\Mail\ReviewReportMail;
use Modules\Reviews\Models\GeneratedReport;
use Modules\Reviews\Services\ReviewReportService;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public int $backoff = 60;

    public function __construct(
        public GeneratedReport $generatedReport
    ) {
        $this->onQueue('reports');
    }

    public function handle(ReviewReportService $reportService): void
    {
        try {
            Log::info('Starting report generation', [
                'report_id' => $this->generatedReport->id,
                'type' => $this->generatedReport->report_type,
                'format' => $this->generatedReport->format,
            ]);

            $reportData = $this->generateReportData($reportService);
            $filepath = $this->saveReportFile($reportData);
            $fileSize = Storage::disk('local')->size($filepath);

            $this->generatedReport->markAsCompleted($filepath, $fileSize);

            $this->sendEmailNotification($filepath);

            Log::info('Report generation completed', [
                'report_id' => $this->generatedReport->id,
                'filepath' => $filepath,
                'file_size' => $fileSize,
            ]);
        } catch (\Exception $e) {
            Log::error('Report generation failed', [
                'report_id' => $this->generatedReport->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->generatedReport->markAsFailed($e->getMessage());

            throw $e;
        }
    }

    private function generateReportData(ReviewReportService $reportService): array
    {
        return match ($this->generatedReport->report_type) {
            'location_summary' => $reportService->generateLocationReport($this->generatedReport->filters ?? []),
            'period_analysis' => $reportService->generatePeriodReport($this->generatedReport->filters ?? []),
            'comparison' => $reportService->generateComparisonReport($this->generatedReport->filters ?? []),
            'response_performance' => $reportService->generateResponsePerformanceReport($this->generatedReport->filters ?? []),
            'detailed_export' => [
                'reviews' => $reportService->generateExportReport($this->generatedReport->filters ?? []),
            ],
            default => throw new \InvalidArgumentException("Unknown report type: {$this->generatedReport->report_type}"),
        };
    }

    private function saveReportFile(array $reportData): string
    {
        $directory = 'reports';
        $filename = $this->generatedReport->filename;

        if (! Storage::disk('local')->exists($directory)) {
            Storage::disk('local')->makeDirectory($directory);
        }

        return match ($this->generatedReport->format) {
            'csv' => $this->saveCsvReport($reportData, $directory, $filename),
            'excel' => $this->saveExcelReport($reportData, $directory, $filename),
            'pdf' => $this->savePdfReport($reportData, $directory, $filename),
            default => throw new \InvalidArgumentException("Unknown format: {$this->generatedReport->format}"),
        };
    }

    private function saveCsvReport(array $reportData, string $directory, string $filename): string
    {
        $filepath = "{$directory}/{$filename}";
        $fullPath = Storage::disk('local')->path($filepath);

        $handle = fopen($fullPath, 'w');

        try {
            if ($this->generatedReport->report_type === 'detailed_export') {
                fputcsv($handle, ['ID', 'Location', 'Reviewer', 'Rating', 'Comment', 'Review Date', 'Has Reply', 'Reply Text', 'Visible']);

                foreach ($reportData['reviews'] as $review) {
                    fputcsv($handle, [
                        $review->id,
                        $review->location->name,
                        $review->reviewer_name,
                        $review->star_rating->value(),
                        $review->comment,
                        $review->review_time->format('Y-m-d H:i:s'),
                        $review->hasGoogleReply() ? 'Yes' : 'No',
                        $review->google_reply_text,
                        $review->moderation?->is_visible ? 'Yes' : 'No',
                    ]);
                }
            } else {
                $values = array_map(fn ($v) => is_array($v) ? json_encode($v) : $v, array_values($reportData));
                fputcsv($handle, array_keys($reportData));
                fputcsv($handle, $values);
            }
        } finally {
            fclose($handle);
        }

        return $filepath;
    }

    private function saveExcelReport(array $reportData, string $directory, string $filename): string
    {
        $filepath = "{$directory}/{$filename}";

        Excel::store(
            new ReportExport($reportData, $this->generatedReport->report_type),
            $filepath,
            'local'
        );

        return $filepath;
    }

    private function savePdfReport(array $reportData, string $directory, string $filename): string
    {
        $filepath = "{$directory}/{$filename}";
        $viewName = "reviews::reports.pdf.{$this->generatedReport->report_type}";

        $pdf = Pdf::loadView($viewName, ['data' => $reportData])
            ->setPaper('a4', 'portrait');

        Storage::disk('local')->put($filepath, $pdf->output());

        return $filepath;
    }

    private function sendEmailNotification(string $filepath): void
    {
        $user = $this->generatedReport->user;

        if (! $user || ! $user->email) {
            return;
        }

        $absolutePath = Storage::disk('local')->path($filepath);

        try {
            Mail::to($user->email)
                ->send(new ReviewReportMail($this->generatedReport, $absolutePath));
        } catch (\Exception $e) {
            Log::error('Failed to send review report email', [
                'report_id' => $this->generatedReport->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateReportJob failed permanently', [
            'report_id' => $this->generatedReport->id,
            'error' => $exception->getMessage(),
        ]);

        $this->generatedReport->markAsFailed($exception->getMessage());
    }
}

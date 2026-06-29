<?php

namespace Modules\Reviews\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        private readonly array $reportData,
        private readonly string $reportType
    ) {}

    public function array(): array
    {
        return match ($this->reportType) {
            'location_summary' => $this->formatLocationSummary(),
            'period_analysis' => $this->formatPeriodAnalysis(),
            'comparison' => $this->formatComparison(),
            'response_performance' => $this->formatResponsePerformance(),
            'detailed_export' => $this->formatDetailedExport(),
            default => [],
        };
    }

    public function headings(): array
    {
        return match ($this->reportType) {
            'location_summary' => ['Location', 'Total Reviews', 'Avg Rating', 'With Comment', 'With Reply', 'Reply Rate %'],
            'period_analysis' => ['Period', 'Total Reviews', 'Avg Rating', 'With Reply'],
            'comparison' => ['Metric', 'Period 1', 'Period 2', 'Change'],
            'response_performance' => ['Rating', 'Total', 'With Reply', 'Reply Rate %'],
            'detailed_export' => ['ID', 'Location', 'Reviewer', 'Rating', 'Comment', 'Review Date', 'Has Reply', 'Reply Text', 'Visible'],
            default => [],
        };
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return match ($this->reportType) {
            'location_summary' => 'Location Summary',
            'period_analysis' => 'Period Analysis',
            'comparison' => 'Comparison',
            'response_performance' => 'Response Performance',
            'detailed_export' => 'Reviews Export',
            default => 'Report',
        };
    }

    private function formatLocationSummary(): array
    {
        $rows = [];

        foreach ($this->reportData['locations'] ?? [] as $location) {
            $rows[] = [
                $location['location_name'],
                $location['total_reviews'],
                $location['avg_rating'],
                $location['with_comment'],
                $location['with_reply'],
                $location['reply_rate'],
            ];
        }

        return $rows;
    }

    private function formatPeriodAnalysis(): array
    {
        $rows = [];

        foreach ($this->reportData['time_series'] ?? [] as $period) {
            $rows[] = [
                $period['period'],
                $period['total_reviews'],
                $period['avg_rating'],
                $period['with_reply'],
            ];
        }

        return $rows;
    }

    private function formatComparison(): array
    {
        $p1 = $this->reportData['period1']['stats'] ?? [];
        $p2 = $this->reportData['period2']['stats'] ?? [];
        $comp = $this->reportData['comparison'] ?? [];

        return [
            ['Total Reviews', $p1['total_reviews'] ?? 0, $p2['total_reviews'] ?? 0, $comp['reviews_change'] ?? 0],
            ['Avg Rating', $p1['avg_rating'] ?? 0, $p2['avg_rating'] ?? 0, $comp['rating_change'] ?? 0],
            ['Reply Rate %', $p1['reply_rate'] ?? 0, $p2['reply_rate'] ?? 0, $comp['reply_rate_change'] ?? 0],
        ];
    }

    private function formatResponsePerformance(): array
    {
        $rows = [];

        foreach ($this->reportData['by_rating'] ?? [] as $rating => $stats) {
            $rows[] = [
                $rating.' Stars',
                $stats['total'],
                $stats['with_reply'],
                $stats['reply_rate'],
            ];
        }

        return $rows;
    }

    private function formatDetailedExport(): array
    {
        $rows = [];

        foreach ($this->reportData['reviews'] ?? [] as $review) {
            $rows[] = [
                $review->id,
                $review->location->name,
                $review->reviewer_name,
                $review->star_rating->value(),
                $review->comment,
                $review->review_time->format('Y-m-d H:i:s'),
                $review->hasGoogleReply() ? 'Yes' : 'No',
                $review->google_reply_text,
                $review->moderation?->is_visible ? 'Yes' : 'No',
            ];
        }

        return $rows;
    }
}

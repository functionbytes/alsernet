<?php

namespace Modules\Analytics\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AnalyticsReportExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $report
    ) {}

    public function sheets(): array
    {
        return [
            new AnalyticsOverviewSheet($this->report),
            new AnalyticsTopPagesSheet($this->report),
        ];
    }
}

class AnalyticsOverviewSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private readonly array $report) {}

    public function title(): string
    {
        return 'Resumen';
    }

    public function headings(): array
    {
        return ['Métrica', 'Valor'];
    }

    public function array(): array
    {
        $overview = $this->report['overview'];

        return [
            ['Período', $this->report['period']['start'].' → '.$this->report['period']['end']],
            ['Sesiones', $overview['sessions']],
            ['Usuarios', $overview['users']],
            ['Vistas de página', $overview['pageviews']],
            ['Tasa de rebote', round($overview['bounce_rate'] * 100, 1).'%'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

class AnalyticsTopPagesSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private readonly array $report) {}

    public function title(): string
    {
        return 'Páginas más visitadas';
    }

    public function headings(): array
    {
        return ['Título', 'URL', 'Vistas'];
    }

    public function array(): array
    {
        return array_map(
            fn ($page) => [$page['title'], $page['url'], $page['views']],
            $this->report['top_pages'] ?? []
        );
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

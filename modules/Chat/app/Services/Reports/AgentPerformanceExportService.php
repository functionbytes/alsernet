<?php

namespace Modules\Chat\Services\Reports;

use App\Exports\AgentPerformanceExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Maatwebsite\Excel\Excel;

class AgentPerformanceExportService
{
    /**
     * Export to PDF format.
     */
    public function exportPdf(array $metrics, Carbon $start, Carbon $end, string $filename)
    {
        $pdf = Pdf::loadView('pdf.agent-performance', [
            'metrics' => $metrics,
            'startDate' => $start->format('Y-m-d'),
            'endDate' => $end->format('Y-m-d'),
        ]);

        return $pdf->download($filename.'.pdf');
    }

    /**
     * Export to Excel/CSV format.
     */
    public function exportExcel(array $metrics, Carbon $start, Carbon $end, string $filename, string $format)
    {
        $export = new AgentPerformanceExport(
            $metrics,
            $start->format('Y-m-d'),
            $end->format('Y-m-d')
        );

        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $writerType = $format === 'csv' ? Excel::CSV : Excel::XLSX;

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename.'.'.$extension, $writerType);
    }
}

<?php

namespace Modules\HelpdeskChat\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AgentPerformanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected array $metrics;

    protected string $agentName;

    protected string $startDate;

    protected string $endDate;

    public function __construct(array $metrics, string $startDate, string $endDate)
    {
        $this->metrics = $metrics;
        $this->agentName = $metrics['agent']['name'] ?? 'Unknown';
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect([
            [
                'metric' => 'Conversations Resolved',
                'value' => $this->metrics['conversation']['resolved'] ?? 0,
            ],
            [
                'metric' => 'Conversations Assigned',
                'value' => $this->metrics['conversation']['assigned'] ?? 0,
            ],
            [
                'metric' => 'Resolution Rate',
                'value' => ($this->metrics['conversation']['resolution_rate'] ?? 0).'%',
            ],
            [
                'metric' => 'Messages Sent',
                'value' => $this->metrics['messages']['sent'] ?? 0,
            ],
            [
                'metric' => 'Avg First Response Time',
                'value' => round($this->metrics['response_times']['first_response_avg'] ?? 0, 2).' min',
            ],
            [
                'metric' => 'Avg Resolution Time',
                'value' => round($this->metrics['response_times']['resolution_avg'] ?? 0, 2).' hours',
            ],
            [
                'metric' => 'CSAT Rating',
                'value' => round($this->metrics['csat']['avg_rating'] ?? 0, 2).'/5',
            ],
            [
                'metric' => 'CSAT Responses',
                'value' => $this->metrics['csat']['total_responses'] ?? 0,
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'Metric',
            'Value',
        ];
    }

    public function map($row): array
    {
        return [
            $row['metric'],
            $row['value'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function title(): string
    {
        return 'Agent Performance';
    }
}

<?php

namespace Modules\Page\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PageAnalyticsExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly array $data) {}

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return ! empty($this->data) ? array_keys($this->data[0]) : [];
    }

    public function title(): string
    {
        return 'Analytics';
    }
}

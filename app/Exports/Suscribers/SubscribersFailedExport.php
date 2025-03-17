<?php

namespace App\Exports\Suscribers;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SubscribersFailedExport implements FromCollection, WithHeadings
{
    protected $failedRecords;

    public function __construct($failedRecords)
    {
        $this->failedRecords = $failedRecords;
    }

    public function collection()
    {
        return collect($this->failedRecords);
    }

    public function headings(): array
    {
        return ['firstname', 'lastname', 'email', 'parties', 'commercial', 'lang', 'birthday', 'check', 'unsubscriber', 'management', 'customer', 'error'];
    }
}

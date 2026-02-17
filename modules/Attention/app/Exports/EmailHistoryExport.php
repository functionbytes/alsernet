<?php

namespace Modules\Attention\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmailHistoryExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Radicado',
            'Tipo PQRSF',
            'Destinatario',
            'Asunto',
            'Tipo Email',
            'Estado',
            'Plantilla',
            'Enviado por',
            'Fecha Envío',
            'Fecha Creación',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

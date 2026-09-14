<?php

namespace Modules\PriceLabels\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PriceLabelRowsImport implements ToCollection
{
    /** @var array<int, array<string, string>> */
    public array $rows = [];

    /**
     * @param  array<string, string>  $columnMap  clave de campo => letra de columna Excel (ej. ['referencia' => 'A'])
     */
    public function __construct(
        private readonly array $columnMap
    ) {}

    public function collection(Collection $collection): void
    {
        $indexMap = [];
        foreach ($this->columnMap as $key => $letter) {
            $indexMap[$key] = Coordinate::columnIndexFromString($letter) - 1;
        }

        foreach ($collection->skip(1) as $row) {
            $values = [];
            foreach ($indexMap as $key => $index) {
                $values[$key] = trim((string) ($row[$index] ?? ''));
            }

            if (implode('', $values) === '') {
                continue;
            }

            $this->rows[] = $values;
        }
    }
}

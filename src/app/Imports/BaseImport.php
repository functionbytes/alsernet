<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

abstract class BaseImport implements SkipsOnFailure, ToCollection, WithChunkReading, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    protected array $imported = [];

    /** @var array<int, array{row: array<string, mixed>, error: string}> */
    protected array $skipped = [];

    public function chunkSize(): int
    {
        return 100;
    }

    abstract public function rules(): array;

    abstract protected function processRow(array $row): void;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            try {
                $this->processRow($row->toArray());
                $this->imported[] = true;
            } catch (\Throwable $e) {
                $this->skipped[] = [
                    'row' => $row->toArray(),
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    public function getImportedCount(): int
    {
        return count($this->imported);
    }

    public function getSkippedCount(): int
    {
        return count($this->skipped);
    }

    /** @return array<int, array{row: array<string, mixed>, error: string}> */
    public function getSkipped(): array
    {
        return $this->skipped;
    }
}

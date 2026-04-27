<?php

namespace Modules\Ecommerce\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Ecommerce\Models\ProductCategory;

class ProductCategoryImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            if (empty($row['nombre'])) {
                continue;
            }

            $name = trim($row['nombre']);

            ProductCategory::query()->updateOrCreate(
                ['name' => $name],
                [
                    'slug' => Str::slug($name),
                    'description' => $row['descripcion'] ?? null,
                    'order' => is_numeric($row['orden'] ?? null) ? (int) $row['orden'] : 0,
                ]
            );
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }
}

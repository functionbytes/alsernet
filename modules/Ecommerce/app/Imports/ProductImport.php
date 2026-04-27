<?php

namespace Modules\Ecommerce\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Ecommerce\Models\Product;

class ProductImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            if (empty($row['nombre'])) {
                continue;
            }

            $sku = ($row['sku'] ?? null) ?: ('IMP-'.uniqid());
            $status = in_array($row['estado'] ?? '', ['published', 'draft']) ? $row['estado'] : 'draft';

            Product::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $row['nombre'],
                    'price' => is_numeric($row['precio'] ?? null) ? $row['precio'] : 0,
                    'sale_price' => is_numeric($row['precio_oferta'] ?? null) ? $row['precio_oferta'] : null,
                    'quantity' => is_numeric($row['stock'] ?? null) ? (int) $row['stock'] : 0,
                    'status' => $status,
                ]
            );
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }
}

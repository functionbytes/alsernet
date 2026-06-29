<?php

namespace Modules\Ecommerce\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Ecommerce\Models\Product;

class ProductInventoryImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            if (empty($row['sku'])) {
                continue;
            }

            $quantity = (int) ($row['stock'] ?? $row['quantity'] ?? 0);

            Product::query()
                ->where('sku', $row['sku'])
                ->update([
                    'quantity' => $quantity,
                    'stock_status' => $quantity > 0 ? 'in_stock' : 'out_of_stock',
                ]);
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }
}

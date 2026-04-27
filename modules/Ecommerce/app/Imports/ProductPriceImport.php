<?php

namespace Modules\Ecommerce\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Ecommerce\Models\Product;

class ProductPriceImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            if (empty($row['sku'])) {
                continue;
            }

            $data = [];

            if (is_numeric($row['precio'] ?? null)) {
                $data['price'] = $row['precio'];
            }

            if (is_numeric($row['precio_oferta'] ?? null)) {
                $data['sale_price'] = $row['precio_oferta'];
            }

            if (empty($data)) {
                continue;
            }

            Product::query()
                ->where('sku', $row['sku'])
                ->update($data);
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }
}

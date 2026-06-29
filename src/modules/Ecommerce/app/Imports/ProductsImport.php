<?php

namespace Modules\Ecommerce\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Ecommerce\Models\Product;

class ProductsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            Product::query()->updateOrCreate(
                ['sku' => $row['sku']],
                [
                    'name' => $row['name'],
                    'slug' => $row['slug'] ?? \Str::slug($row['name']),
                    'price' => $row['price'],
                    'sale_price' => $row['sale_price'] ?? null,
                    'quantity' => $row['quantity'] ?? 0,
                    'status' => $row['status'] ?? 'published',
                ]
            );
        }
    }
}

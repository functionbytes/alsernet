<?php

namespace Modules\Ecommerce\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Ecommerce\Models\Product;

class ProductInventoryExport implements FromQuery, WithHeadings, WithMapping
{
    public function query(): Builder
    {
        return Product::query()
            ->where('is_variation', false)
            ->select(['id', 'name', 'sku', 'quantity', 'stock_status'])
            ->orderBy('name');
    }

    public function headings(): array
    {
        return ['ID', 'Nombre', 'SKU', 'Stock', 'Estado stock'];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->sku ?? '',
            $product->quantity ?? 0,
            $product->stock_status ?? '',
        ];
    }
}

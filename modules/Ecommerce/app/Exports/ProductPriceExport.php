<?php

namespace Modules\Ecommerce\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Ecommerce\Models\Product;

class ProductPriceExport implements FromQuery, WithHeadings, WithMapping
{
    public function query(): Builder
    {
        return Product::query()
            ->where('is_variation', false)
            ->select(['id', 'name', 'sku', 'price', 'sale_price'])
            ->orderBy('name');
    }

    public function headings(): array
    {
        return ['ID', 'Nombre', 'SKU', 'Precio', 'Precio oferta'];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->sku ?? '',
            $product->price,
            $product->sale_price ?? '',
        ];
    }
}

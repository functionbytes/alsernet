<?php

namespace Modules\Ecommerce\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Ecommerce\Models\Product;

class ProductsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Product::query()
            ->select('id', 'name', 'slug', 'sku', 'price', 'sale_price', 'quantity', 'status')
            ->get();
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Slug', 'SKU', 'Price', 'Sale Price', 'Quantity', 'Status'];
    }
}

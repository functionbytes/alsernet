<?php

namespace Modules\Ecommerce\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Ecommerce\Models\Product;

class ProductExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    public function __construct(private readonly array $filters = []) {}

    public function query(): Builder
    {
        return Product::query()
            ->with(['brand', 'categories'])
            ->when($this->filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when(
                $this->filters['category_id'] ?? null,
                fn ($q, $id) => $q->whereHas('categories', fn ($q) => $q->where('ecommerce_product_categories.id', $id))
            )
            ->when($this->filters['brand_id'] ?? null, fn ($q, $id) => $q->where('brand_id', $id))
            ->latest();
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function headings(): array
    {
        return ['ID', 'Nombre', 'SKU', 'Precio', 'Precio oferta', 'Stock', 'Marca', 'Estado', 'Fecha creacion'];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->sku ?? '',
            $product->price,
            $product->sale_price ?? '',
            $product->quantity ?? 0,
            $product->brand->name ?? '',
            $product->status instanceof \BackedEnum ? $product->status->value : ($product->status ?? ''),
            $product->created_at->format('Y-m-d'),
        ];
    }
}

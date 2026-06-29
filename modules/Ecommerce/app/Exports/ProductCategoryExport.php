<?php

namespace Modules\Ecommerce\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Ecommerce\Models\ProductCategory;

class ProductCategoryExport implements FromQuery, WithHeadings, WithMapping
{
    public function query(): Builder
    {
        return ProductCategory::query()
            ->with('parent')
            ->orderBy('name');
    }

    public function headings(): array
    {
        return ['ID', 'Nombre', 'Categoria padre', 'Orden', 'Estado'];
    }

    public function map($category): array
    {
        return [
            $category->id,
            $category->name,
            $category->parent?->name ?? '—',
            $category->order,
            $category->status ?? '',
        ];
    }
}

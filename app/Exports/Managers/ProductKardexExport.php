<?php

namespace App\Exports\Managers;

use App\Models\Location;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ProductKardexExport implements FromQuery, Responsable, WithMapping, WithHeadings, WithStrictNullComparison
{
    use Exportable;
    private $course;
    private $enterprise;
    private $modalitie;
    public function __construct(){

    }
    public function query() {
        $products = DB::table('products')->where('count', '=', 0)->orderByDesc('count');
        return $products;
    }

    public function headingss(): array
    {
        return [
            'PRODUCTO',
            'REFERENCIA',
            'CODIGO DE BARRAS',
            'LOCALIZACION',
            'LOCALIZACION ORIGINAL',
            'INVENTARIO',
        ];
    }

    public function queryss() {

        $products = DB::table('products')
            ->join('inventarie_locations_items', function ($join) {
                $join->on('products.id', '=', 'inventarie_locations_items.product_id');
            })
            ->join('locations', function ($join) {
                $join->on(function ($join) {
                    $join->on('locations.id', '=', 'inventarie_locations_items.validate_id')
                        ->orOn('locations.id', '=', 'inventarie_locations_items.original_id');
                });
            })
            ->select(
                'products.*',
                'inventarie_locations_items.validate_id',
                'inventarie_locations_items.original_id',
                DB::raw('COUNT(inventarie_locations_items.id) as inventory_count')
            )
            ->groupBy(
                'products.id',
                'locations.id',
                'locations.title',
                'inventarie_locations_items.validate_id',
                'inventarie_locations_items.original_id'
            )
            ->orderByDesc('inventory_count');

        return $products;

    }
    public function maps($row): array
    {
        return [
            $row->title == null ?  '' : $row->title,
            $row->reference == null ?  '' : $row->reference,
            $row->barcode == null ?  '' : $row->barcode,
            $row->validate_id == null ?  '' : Location::id($row->validate_id)->title,
            $row->original_id == null ?  '' : Location::id($row->original_id)->title,
            $row->inventory_count == null ?  '' : $row->inventory_count,
        ];
    }

    public function map($row): array
    {
        return [
            $row->title == null ?  '' : $row->title,
            $row->reference == null ?  '' : $row->reference,
            $row->barcode == null ?  '' : $row->barcode,
            $row->count == null ?  '' : $row->count,
        ];

    }
    public function headings(): array
    {
        return [
            'PRODUCTO',
            'REFERENCIA',
            'INVENTARIO',
        ];
    }


}


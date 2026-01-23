<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Warehouse\Exports\Managers\ProductExport;
use Modules\Warehouse\Exports\Managers\ProductKardexExport;

class ReportController extends Controller
{
    public function generateInventary(Request $request)
    {

        return Excel::download(new ProductExport, 'Reporte inventario.xlsx');

    }

    public function generateKardex(Request $request)
    {
        return Excel::download(new ProductKardexExport, 'Reporte kardex.xlsx');

    }
}

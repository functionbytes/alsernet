<?php

namespace App\Http\Controllers\Managers\Products;

use App\Exports\Managers\ProductExport;
use App\Exports\Managers\ProductKardexExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{

    public function generateInventary(Request $request){

        return Excel::download(new ProductExport(), 'Reporte inventario.xlsx');
    
    }

    public function generateKardex(Request $request){
        return Excel::download(new ProductKardexExport(), 'Reporte kardex.xlsx');

    }


}

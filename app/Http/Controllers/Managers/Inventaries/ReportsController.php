<?php

namespace App\Http\Controllers\Managers\Inventaries;

use App\Exports\Managers\Orders\OrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Order\OrderCondition;
use App\Models\Order\OrderMethod;
use App\Models\Order\OrderType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends Controller
{
    public function report(){

        $conditions = OrderCondition::available()->get();
        $conditions->prepend('', '');
        $conditions = $conditions->pluck('label', 'id');

        $types = OrderType::available()->get();
        $types->prepend('', '');
        $types = $types->pluck('label', 'id');

        $methods = OrderMethod::available()->get();
        $methods->prepend('', '');
        $methods = $methods->pluck('label', 'id');

        return view('managers.views.orders.report.index')->with([
            'methods' => $methods,
            'types' => $types,
            'conditions' => $conditions,
        ]);

    }
    public function generate(Request $request){

        $type  = $request->type;
        $method  = $request->methods;
        $condition  = $request->condition;
        $date = explode(" - ", $request->range);
        $start = Carbon::parse($date[0])->startOfDay();
        $end = Carbon::parse($date[1])->endOfDay();

        return Excel::download(new OrdersExport($type,$method,$condition,$start,$end), 'Reporte Ordenes.xlsx');

    }

}

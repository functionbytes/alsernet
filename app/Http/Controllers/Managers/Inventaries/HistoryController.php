<?php

namespace App\Http\Controllers\Managers\Inventaries;

use App\Exports\Managers\Orders\OrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Inventarie\InventarieCondition;
use App\Models\Inventarie\InventarieLocationItem;
use App\Models\Order\OrderCondition;
use App\Models\Order\OrderMethod;
use App\Models\Order\OrderType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class HistoryController extends Controller
{
    public function index(){

        $items = InventarieLocationItem::get();

        return view('managers.views.inventaries.historys.index')->with([
            'items' => $items,
        ]);

    }
    public function edit($slack){

        $item = InventarieLocationItem::slack($slack);

        $conditions = InventarieCondition::get();
        $conditions->prepend('' , '');
        $conditions = $conditions->pluck('title','id');

        return view('managers.views.inventaries.historys.edit')->with([
            'item' => $item,
            'conditions' => $conditions,
        ]);

    }


}

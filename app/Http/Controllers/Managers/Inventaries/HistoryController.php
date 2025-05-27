<?php

namespace App\Http\Controllers\Managers\Inventaries;

use App\Exports\Managers\Orders\OrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Inventarie\EventCategorie;
use App\Models\Inventarie\InventarieCondition;
use App\Models\Inventarie\InventarieLocation;
use App\Models\Inventarie\InventarieLocationItem;
use App\Models\Order\OrderCondition;
use App\Models\Order\OrderMethod;
use App\Models\Order\OrderType;
use App\Models\Product\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class HistoryController extends Controller
{
    public function index(Request $request){

        $searchKey = null ?? $request->search;
        $items = InventarieLocationItem::latest();

        if ($searchKey) {
            $items->when(!strpos($searchKey, '-'), function ($query) use ($searchKey) {
                $query->where('products.reference', 'like', '%' . $searchKey . '%')
                    ->orWhere('products.barcode', 'like', '%' . $searchKey . '%')
                    ->orWhere('products.title', 'like', '%' . $searchKey . '%')
                    ->orWhereHas('location', function ($q) use ($searchKey) {
                        $q->where('locations.title', 'like', '%' . $searchKey . '%');
                    });
            });
        }


        $items = $items->paginate(paginationNumber());

        return view('managers.views.inventaries.historys.index')->with([
            'items' => $items,
            'searchKey' => $searchKey,
        ]);

    }
    public function edit($uid){

        $item = InventarieLocationItem::uid($uid);

        $inventarie = $item->location;
        $conditions = InventarieCondition::get();
        $conditions->prepend('' , '');
        $conditions = $conditions->pluck('title','id');

        return view('managers.views.inventaries.historys.edit')->with([
            'item' => $item,
            'conditions' => $conditions,
            'inventarie' => $inventarie,
        ]);

    }




}

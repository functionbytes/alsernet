<?php

namespace App\Http\Controllers\Managers\Inventaries;

use App\Exports\Managers\Orders\OrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Inventarie\Inventarie;
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

class LocationsController extends Controller
{
    public function index($slack){

        $inventarie = Inventarie::slack($slack);
        $locations = $inventarie->locations;

        return view('managers.views.inventaries.locations.index')->with([
            'inventarie' => $inventarie,
            'locations' => $locations,
        ]);

    }
    public function details($slack){

        $location = InventarieLocation::slack($slack);
        $items = $location->items;

        return view('managers.views.inventaries.locations.details')->with([
            'location' => $location,
            'items' => $items,
        ]);

    }

}

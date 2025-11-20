<?php

namespace App\Http\Controllers\Warehouses\Warehouses;

use App\Models\Product\ProductLocation;
use App\Models\Warehouse\Warehouse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Http\Controllers\Controller;
use App\Models\Inventarie\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WarehousesController extends Controller
{
    public function index(Request $request){

        $searchKey = null ?? $request->search;
        $available = null ?? $request->available;

        $warehouses = Warehouse::latest();

        if ($searchKey != null) {
            $warehouses = $warehouses->where('title', 'like', '%' . $searchKey . '%');
        }

        if ($available != null) {
            $warehouses = $warehouses->where('available', $available);
        }

        $warehouses = $warehouses->paginate(paginationNumber());

        return view('warehouses.views.warehouses.warehouses.index')->with([
            'warehouses' => $warehouses,
            'available' => $available,
            'searchKey' => $searchKey,
        ]);

    }

    public function arrange( $uid){

        $warehouse = Warehouse::uid($uid);
        $user = app('warehouses');

        return view('warehouses.views.warehouses.warehouses.arrange')->with([
            'warehouse' => $warehouse,
        ]);

    }

    public function content($uid){

        $warehouse = Warehouse::uid($uid);

        return view('warehouses.views.warehouses.warehouses.content')->with([
            'warehouse' => $warehouse,
        ]);


    }

    public function destroy($uid){
        $warehouse = Warehouse::uid($uid);
        $warehouse->delete();
        return redirect()->route('warehouses.warehouses.index');
    }

}


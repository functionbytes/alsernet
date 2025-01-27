<?php

namespace App\Http\Controllers\Inventaries\Inventaries;

use App\Models\Product\ProductLocation;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Http\Controllers\Controller;
use App\Models\Inventarie\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InventariesController extends Controller
{
    public function index(Request $request){

        $searchKey = null ?? $request->search;
        $available = null ?? $request->available;

        $inventaries = Event::latest();

        if ($searchKey != null) {
            $inventaries = $inventaries->where('title', 'like', '%' . $searchKey . '%');
        }

        if ($available != null) {
            $inventaries = $inventaries->where('available', $available);
        }

        $inventaries = $inventaries->paginate(paginationNumber());

        return view('inventaries.views.inventaries.inventaries.index')->with([
            'inventaries' => $inventaries,
            'available' => $available,
            'searchKey' => $searchKey,
        ]);

    }

    public function arrange( $slack){

        $inventarie = Event::uid($slack);
        $user = app('inventarie');
        $shop = $user->shop;

        return view('inventaries.views.inventaries.inventaries.arrange')->with([
            'inventarie' => $inventarie,
            'shop' => $shop,
        ]);

    }

    public function content($slack){

        $inventarie = Event::uid($slack);

        return view('inventaries.views.inventaries.inventaries.content')->with([
            'inventarie' => $inventarie,
        ]);

    }

    public function destroy($slack){
        $inventarie = Event::uid($slack);
        $inventarie->delete();
        return redirect()->route('inventaries.inventaries.index');
    }

}


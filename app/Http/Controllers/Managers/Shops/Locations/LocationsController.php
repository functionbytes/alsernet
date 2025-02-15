<?php

namespace App\Http\Controllers\Managers\Shops\Locations;

use App\Http\Controllers\Controller;
use App\Models\Inventarie\InventarieLocationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Location;
use App\Models\Shop;

use App\Models\Inventarie\InventarieLocation;

class LocationsController extends Controller
{
    public function index(Request $request,$uid){

        $shop = Shop::uid($uid);
        $searchKey = null ?? $request->search;
        $available = null ?? $request->available;

        $locations = $shop->locations()->orderBy('id', 'desc');

        if ($searchKey != null) {
            $locations = $locations->where('title', 'like', '%' . $searchKey . '%');
        }

        if ($available != null) {
            $locations = $locations->where('available', $available);
        }

        $locations = $locations->paginate(paginationNumber());

        return view('managers.views.shops.locations.locations.index')->with([
            'shop' => $shop,
            'locations' => $locations,
            'available' => $available,
            'searchKey' => $searchKey,
        ]);

    }
    public function create($uid,$lang = 'es'){


        $shop = Shop::uid($uid);

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables->prepend('' , '');
        $availables = $availables->pluck('label','id');

        $shops = Shop::available()->get();
        $shops->prepend('', '');
        $shops = $shops->pluck('title', 'id');

        return view('managers.views.shops.locations.locations.create')->with([
            'availables' => $availables,
            'shops' => $shops,
            'shop' => $shop,
        ]);

    }

    public function exists($uid){

        $shop = Shop::uid($uid);

        return view('managers.views.shops.locations.locations.exists')->with([
            'shop' => $shop,
        ]);

    }

    public function validate(Request $request){

        $shop  = Shop::uid($request->shop);
        $locationValidate = Location::validateExits($request->location,$shop->id);

        if (!$locationValidate) {

            $location = new Location;
            $location->uid = $this->generate_uid('locations');
            $location->title = Str::upper($request->location);
            $location->barcode = $request->location;
            $location->latitude = null;
            $location->longitude = null;
            $location->available = 1;
            $location->shop_id = $shop->id;
            $location->save();

            return response()->json([
                'success' => true,
                'uid' => $location->uid,
                'message' => 'Se actualizo la clase correctamente',
            ]);

        }else{

            return response()->json([
                'success' => false,
                'message' => 'Se actualizo la clase correctamente',
            ]);
        }

    }


    public function edit($uid){

        $location = Location::uid($uid);

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label','id');

        $shops = Shop::available()->get();
        $shops->prepend('', '');
        $shops = $shops->pluck('title', 'id');

        return view('managers.views.shops.locations.locations.edit')->with([
            'location' => $location,
            'availables' => $availables,
            'shop' => $location->shop,
            'shops' => $shops,
        ]);

    }


    public function update(Request $request){

        $location = Location::uid($request->uid);
        $location->title = Str::upper($request->title);
        $location->barcode = $request->barcode;
        $location->latitude = $request->latitude;
        $location->available = $request->available;
        $location->shop_id = $request->shop;
        $location->update();

        return response()->json([
            'success' => true,
            'uid' => $location->uid,
            'message' => 'Se actualizo la clase correctamente',
        ]);

    }

    public function store(Request $request){

        $location = new Location;
        $location->uid = $this->generate_uid('locations');
        $location->title = Str::upper($request->title);
        $location->barcode = $request->barcode;
        $location->latitude = $request->latitude;
        $location->available = $request->available;
        $location->shop_id = $request->shop;
        $location->save();

        return response()->json([
            'success' => true,
            'uid' => $location->uid,
            'message' => 'Se creo el curso correctamente',
        ]);

    }
    public function destroy($uid){

        $shop = null;
        $location = Location::uid($uid);
        $shop = $location->shop;
        $location->delete();
        return redirect()->route('manager.shops.locations',$shop->uid);
    }

    public function history(Request $request,$uid){

        $searchKey = null ?? $request->search;
        $location = Location::uid($uid);
        $inventarielocation = InventarieLocation::where('location_id', $location->id)->first();

        $items = $inventarielocation->items();


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

}


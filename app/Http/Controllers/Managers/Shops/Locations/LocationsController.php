<?php

namespace App\Http\Controllers\Managers\Shops\Locations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Location;
use App\Models\Shop;

class LocationsController extends Controller
{
    public function index(Request $request,$slack){

        $shop = Shop::slack($slack);
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
    public function create(){

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
        ]);

    }

    public function exists($slack){

        $shop = Shop::slack($slack);

        return view('managers.views.shops.locations.locations.exists')->with([
            'shop' => $shop,
        ]);

    }

    public function validate(Request $request){

        $shop  = Shop::slack($request->shop);
        $locationValidate = Location::validateExits($request->location,$shop->id);

        if (!$locationValidate) {

            $location = new Location;
            $location->slack = $this->generate_slack('locations');
            $location->title = Str::upper($request->location);
            $location->barcode = $request->location;
            $location->latitude = null;
            $location->longitude = null;
            $location->available = 1;
            $location->shop_id = $shop->id;
            $location->save();

            return response()->json([
                'success' => true,
                'slack' => $location->slack,
                'message' => 'Se actualizo la clase correctamente',
            ]);

        }else{

            return response()->json([
                'success' => false,
                'message' => 'Se actualizo la clase correctamente',
            ]);
        }



    }


    public function edit($slack){

        $location = Location::slack($slack);

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

        $location = Location::slack($request->slack);
        $location->title = Str::upper($request->title);
        $location->barcode = $request->barcode;
        $location->latitude = $request->latitude;
        $location->available = $request->available;
        $location->shop_id = $request->shop;
        $location->update();

        return response()->json([
            'success' => true,
            'slack' => $location->slack,
            'message' => 'Se actualizo la clase correctamente',
        ]);

    }

    public function store(Request $request){

        $location = new Location;
        $location->slack = $this->generate_slack('locations');
        $location->title = Str::upper($request->title);
        $location->barcode = $request->barcode;
        $location->latitude = $request->latitude;
        $location->available = $request->available;
        $location->shop_id = $request->shop;
        $location->save();

        return response()->json([
            'success' => true,
            'slack' => $location->slack,
            'message' => 'Se creo el curso correctamente',
        ]);

    }
    public function destroy($slack){

        $shop = null;
        $location = Location::slack($slack);
        $shop = $location->shop;
        $location->delete();
        return redirect()->route('manager.shops.locations',$shop->slack);
    }

}


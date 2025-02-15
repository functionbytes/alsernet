<?php


namespace App\Http\Controllers\Inventaries\Locations;


use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class BarcodeController extends Controller
{

    public function all(Request $request){

        $locations = Location::all()->take(100);
        return view('inventaries.views.locations.barcodes.all')->with([
            'locations' => $locations,
        ]);

    }
    public function single($uid){

        $location = Location::uid($uid);

        return view('inventaries.views.locations.barcodes.all')->with([
            'location' => $location,
        ]);
    }

}

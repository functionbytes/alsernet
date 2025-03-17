<?php

namespace App\Http\Controllers\Managers\Inventaries;

use App\Models\Inventarie\InventarieLocationItem;
use App\Models\Inventarie\InventarieLocation;
use App\Models\Inventarie\Inventarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocationsController extends Controller
{
    public function index(Request $request, $uid)
    {
        $inventarie = Inventarie::uid($uid)->firstOrFail();
        $searchKey = $request->search ?? null;

        $locations = $inventarie->locations();

        if ($searchKey) {
            $locations = $locations->join('locations', 'locations.id', '=', 'inventarie_locations.location_id')
               ->where(function ($query) use ($searchKey) {
                    $query->where('locations.title', 'like', '%' . $searchKey . '%')
                        ->orWhere('locations.barcode', 'like', '%' . $searchKey . '%');
                })
               ->select('inventarie_locations.*');
        }

        $locations = $locations->paginate(paginationNumber());

        return view('managers.views.inventaries.locations.index', [
            'inventarie' => $inventarie,
            'locations' => $locations,
            'searchKey' => $searchKey,
        ]);
    }



    public function details($uid){

        $location = InventarieLocation::uid($uid);
        $items = $location->items;

        return view('managers.views.inventaries.locations.details')->with([
            'location' => $location,
            'items' => $items,
        ]);

    }



    public function edit($uid){

        $location = InventarieLocation::uid($uid);

        $availables = collect([
            ['id' => '1', 'label' => 'Cerrado'],
            ['id' => '0', 'label' => 'Abierto'],
        ]);

        $availables = $availables->pluck('label','id');

        return view('managers.views.inventaries.locations.edit')->with([
          'location' => $location,
          'availables' => $availables,
        ]);

  }


  public function update(Request $request){

      $location = InventarieLocation::uid($request->uid);
      $location->complete = $request->available;
      $location->update();

      return response()->json([
        'success' => true,
        'uid' => $location->uid,
        'message' => 'Se actualizo la clase correctamente',
      ]);

  }


    public function destroy($uid){
        $shop = null;
        $location = InventarieLocation::uid($uid);
        $shop = $location->inventarie->uid;
        $location->delete();
        return redirect()->route('manager.inventaries.locations',$shop);
    }


    public function destroyItem($uid){
        $location = null;
        $item = InventarieLocationItem::uid($uid);
        $location = $item->location->uid;
        $item->delete();
        return redirect()->route('manager.inventaries.locations.details',$location);
    }




}

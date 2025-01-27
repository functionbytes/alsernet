<?php

namespace App\Http\Controllers\Managers\Inventaries;

use App\Models\Inventarie\InventarieLocationItem;
use App\Models\Inventarie\InventarieLocation;
use App\Models\Inventarie\Inventarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocationsController extends Controller
{
    public function index(Request $request, $slack)
    {
        $inventarie = Inventarie::uid($slack)->firstOrFail();
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



    public function details($slack){

        $location = InventarieLocation::uid($slack);
        $items = $location->items;

        return view('managers.views.inventaries.locations.details')->with([
            'location' => $location,
            'items' => $items,
        ]);

    }



    public function edit($slack){

        $location = InventarieLocation::uid($slack);

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
        'slack' => $location->uid,
        'message' => 'Se actualizo la clase correctamente',
      ]);

  }


    public function destroy($slack){
        $shop = null;
        $location = InventarieLocation::uid($slack);
        $shop = $location->inventarie->uid;
        $location->delete();
        return redirect()->route('manager.inventaries.locations',$shop);
    }


    public function destroyItem($slack){
        $location = null;
        $item = InventarieLocationItem::uid($slack);
        $location = $item->location->uid;
        $item->delete();
        return redirect()->route('manager.inventaries.locations.details',$location);
    }




}

<?php

namespace App\Http\Controllers\Managers\Inventaries;

use App\Exports\Managers\Orders\OrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Inventarie\Event;
use App\Models\Inventarie\EventCategorie;
use App\Models\Inventarie\InventarieLocation;
use App\Models\Inventarie\InventarieLocationItem;
use App\Models\Order\OrderCondition;
use App\Models\Order\OrderMethod;
use App\Models\Order\OrderType;
use App\Models\Product\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LocationssController extends Controller
{
    public function index(Request $request, $uid)
    {
        $inventarie = Event::uid($uid)->firstOrFail();
        $searchKey = $request->search ?? null;

        $locations = $inventarie->locations();

        if ($searchKey) {
            //$locations = $locations->join('locations', 'locations.id', '=', 'inventarie_locations.location_id')
            //    ->where(function ($query) use ($searchKey) {
              //      // Filtramos por el título o el barcode de la tabla 'locations'
             //       $query->where('locations.title', 'like', '%' . $searchKey . '%')
             //           ->orWhere('locations.barcode', 'like', '%' . $searchKey . '%');
              //  })
              //  ->select('inventarie_locations.*'); // Solo seleccionamos las columnas de 'inventarie_locations'
        }

       // dd($locations->get());
        $locations = $locations->paginate(100000);

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

}

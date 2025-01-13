<?php

namespace App\Http\Controllers\Inventaries\Inventaries;

use App\Models\Inventarie\InventarieLocation;
use App\Models\Inventarie\InventarieLocationItem;
use App\Models\Location;
use App\Models\Product\Product;
use DB;
use Dflydev\DotAccessData\Data;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Http\Controllers\Controller;
use App\Models\Inventarie\Inventarie;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LocationsController extends Controller
{
    public function index(Request $request){

        $searchKey = null ?? $request->search;
        $available = null ?? $request->available;

        $inventaries = Inventarie::latest();

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

    public function content($slack){

        $inventarie = Inventarie::slack($slack);

        return view('inventaries.views.inventaries.inventaries.content')->with([
            'inventarie' => $inventarie,
        ]);

    }

    public function modalitie($slack){

        $location = InventarieLocation::slack($slack);

        return view('inventaries.views.inventaries.inventaries.modalities.modalitie')->with([
                'location' => $location
        ]);


    }


    public function location($slackLocation,$slackInventarie){

        $location = Location::slack($slackLocation);
        $inventarie = Inventarie::slack($slackInventarie);

        $locationValidate = InventarieLocation::validateExists($location->id,$inventarie->id);

        if ($locationValidate) {

            $locationValidates = InventarieLocation::validate($location->id,$inventarie->id);

            if ($locationValidates->complete == 1) {

                return view('inventaries.views.inventaries.inventaries.complete')->with([
                    'location' => $locationValidates,
                    'inventarie' => $inventarie,
                ]);

            }else  {
                return redirect()->route('inventarie.inventarie.location.modalitie',$locationValidates->slack);
            }

        }else  {

            $locationItem = new InventarieLocation();
            $locationItem->slack = $this->generate_slack('inventarie_locations');
            $locationItem->complete = 0;
            $locationItem->location_id = $location->id;
            $locationItem->inventarie_id = $inventarie->id;
            $locationItem->save();

            return redirect()->route('inventarie.inventarie.location.modalitie',$locationItem->slack);


        }


    }


    public function automatic($slackLocation){

        $item = InventarieLocation::slack($slackLocation);
        $inventarie =  $item->inventarie;
        $location = $item->location;

        return view('inventaries.views.inventaries.inventaries.modalities.automatic')->with([
            'item' => $item,
            'location' => $location,
            'inventarie' => $inventarie,
        ]);


    }

    public function manual($slackLocation){

        $item = InventarieLocation::slack($slackLocation);
        $inventarie =  $item->inventarie;
        $location = $item->location;

        return view('inventaries.views.inventaries.inventaries.modalities.manual')->with([
            'item' => $item,
            'location' => $location,
            'inventarie' => $inventarie,
        ]);

    }




      public function validateLocation(Request $request){

          $inventarie = app('inventarie');
          $shop = $inventarie->shop_id;

          $location = Location::validateExits($request->location, $shop);

          if ($location) {

              $location = Location::validate($request->location, $shop);

              return response()->json([
                  'success' => true,
                  'slack'   => $location->slack
              ]);

          }else {
              return response()->json([
                  'success' => false,
                  'message' => 'Ubicación no encontrada.'
              ]);
          }

      }


    public function validateProduct(Request $request){

        $inventarie = app('inventarie');
        $shop = $inventarie->shop_id;

        $product = Product::barcodeExits($request->product);
        //$product = Product::slack($request->location, $request->product);

        if ($product) {

            $product = Product::barcode($request->product);

            return response()->json([
                'success' => true,
                'product'   => $product
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => ''
        ]);

    }

      public function close(Request $request){

        $user = app('inventarie');
        $locationValidate = Location::slack($request->location);
        $locationItem = InventarieLocation::slack($request->item);

        if ($request->modalitie == 'automatic') {

            $products = $request->products;

            foreach ($products as $product) {

                $productItem = Product::slack($product['slack']);
                // $locationProductItem = $productItem->localization;
                $locationProductItem = 1;

                $inventarieItem = new InventarieLocationItem();
                $inventarieItem->slack = $this->generate_slack('inventarie_locations_items');
                $inventarieItem->product_id = $productItem->id;
                $inventarieItem->location_id = $locationItem->id;
                $inventarieItem->original_id = $locationProductItem;
                $inventarieItem->validate_id = $locationValidate->id;
                $inventarieItem->user_id = $user->id;
                $inventarieItem->count = 1;
                $inventarieItem->condition_id = 1;

                // if($inventarieItem->validate_id == $inventarieItem->original_id){
                //    $inventarieItem->condition_id = 1;
                // }
                $inventarieItem->save();
            }


            $locationItem->complete = 1;
            $locationItem->update();

            $itemsGroupedByProduct = $locationItem->items() // Relación de items
            ->select('product_id', DB::raw('count(*) as product_count'))
                ->groupBy('product_id')
                ->get();

            foreach ($itemsGroupedByProduct as $itemGroup) {
                $product = Product::id($itemGroup->product_id);
                $shopId = $user->shop_id;

                $locations = $product->localizations->filter(function($localization) use ($shopId) {
                    return $localization->shop_id == $shopId;
                });

                if ($product) {
                    foreach ($locations as $location) {
                        $location->count+= $itemGroup->product_count;
                        $location->update();
                    }
                }
            }

        }elseif ($request->modalitie == 'manual') {

                $productItem = Product::barcode($request->product);

                // $locationProductItem = $productItem->localization;
                $locationProductItem = 1;

                $inventarieItem = new InventarieLocationItem();
                $inventarieItem->slack = $this->generate_slack('inventarie_locations_items');
                $inventarieItem->product_id = $productItem->id;
                $inventarieItem->location_id = $locationItem->id;
                $inventarieItem->original_id = $locationProductItem;
                $inventarieItem->validate_id = $locationValidate->id;
                $inventarieItem->user_id = $user->id;
                $inventarieItem->count = $request->count;
                $inventarieItem->condition_id = 1;

                // if($inventarieItem->validate_id == $inventarieItem->original_id){
                //    $inventarieItem->condition_id = 1;
                // }
                $inventarieItem->save();
                $locationItem->complete = 1;
                $locationItem->update();

                $itemsGroupedByProduct = $locationItem->items() // Relación de items
                ->select('product_id', DB::raw('SUM(count) as total_count'))
                ->groupBy('product_id')  // Agrupar por 'product_id'
                ->get();


                foreach ($itemsGroupedByProduct as $itemGroup) {
                    $product = Product::id($itemGroup->product_id);
                    $shopId = $user->shop_id;

                    $locations = $product->localizations->filter(function($localization) use ($shopId) {
                        return $localization->shop_id == $shopId;
                    });

                    if ($product) {
                        foreach ($locations as $location) {
                            $location->count+= $itemGroup->total_count;
                            $location->update();
                        }
                    }
                }

        }


                  return response()->json([
                    'success' => true,
                    'message' => 'Se creo el curso correctamente',
                  ]);

      }

    public function destroy($slack){
        $plan = Plan::slack($slack);
        $plan->delete();
        return redirect()->route('manager.plans');
    }

}


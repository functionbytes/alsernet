<?php

namespace App\Http\Controllers\Inventaries\Inventaries;

use App\Models\Inventarie\InventarieLocation;
use App\Models\Inventarie\InventarieLocationItem;
use App\Models\Location;
use App\Models\Product\Product;
use App\Models\Product\ProductLocation;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;
use Dflydev\DotAccessData\Data;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Http\Controllers\Controller;
use App\Models\Inventarie\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LocationsController extends Controller
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

    public function content($uid){

        $inventarie = Event::uid($uid);

        return view('inventaries.views.inventaries.inventaries.content')->with([
            'inventarie' => $inventarie,
        ]);

    }

    public function modalitie($uid){

        $user = app('inventarie');
        $shop = $user->shop;
        $location = InventarieLocation::uid($uid);

        return view('inventaries.views.inventaries.inventaries.modalities.modalitie')->with([
                'location' => $location,
                'shop' => $shop
        ]);


    }


    public function location($uidLocation,$uidInventarie){

        $location = Location::uid($uidLocation);
        $inventarie = Event::uid($uidInventarie);

        $locationValidate = InventarieLocation::validateExists($location->id,$inventarie->id);

        if ($locationValidate) {

            $locationValidates = InventarieLocation::validate($location->id,$inventarie->id);

            if ($locationValidates->complete == 1) {

                return view('inventaries.views.inventaries.inventaries.complete')->with([
                    'location' => $locationValidates,
                    'inventarie' => $inventarie,
                ]);

            }else  {
                return redirect()->route('inventarie.inventarie.location.modalitie',$locationValidates->uid);
            }

        }else  {

            $locationItem = new InventarieLocation();
            $locationItem->uid = $this->generate_uid('inventarie_locations');
            $locationItem->complete = 0;
            $locationItem->location_id = $location->id;
            $locationItem->inventarie_id = $inventarie->id;
            $locationItem->save();

            return redirect()->route('inventarie.inventarie.location.modalitie',$locationItem->uid);


        }


    }


    public function automatic($uidLocation){

        $item = InventarieLocation::uid($uidLocation);
        $inventarie =  $item->inventarie;
        $location = $item->location;

        return view('inventaries.views.inventaries.inventaries.modalities.automatic')->with([
            'item' => $item,
            'location' => $location,
            'inventarie' => $inventarie,
        ]);


    }

    public function manual($uidLocation){

        $item = InventarieLocation::uid($uidLocation);
        $inventarie =  $item->inventarie;
        $location = $item->location;

        return view('inventaries.views.inventaries.inventaries.modalities.manual')->with([
            'item' => $item,
            'location' => $location,
            'inventarie' => $inventarie,
        ]);

    }

    public function validateGenerate(Request $request){

        $shop  = Shop::uid($request->shop);
        $shopName = Str::upper($shop->name);
        $locationValidate = Location::validateExits($request->location,$shop->id);
        $lastLocation = Location::where('shop_id', $shop->id)->latest()->first(); // Get the last created location for the shop.
        $sequentialNumber = $lastLocation ? (int)substr($lastLocation->title, -3) + 1 : 1; // Increment the sequential number.
        $sequentialNumber = str_pad($sequentialNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationValidate) {

            $title = $shopName.'locations'.$sequentialNumber;
            $location = new Location;
            $location->uid = $this->generate_uid('locations');
            $location->title = Str::upper($title);
            $location->barcode = $request->title;
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



      public function validateLocation(Request $request){

          $inventarie = app('inventarie');
          $shop = $inventarie->shop_id;

          $location = Location::validateExits($request->location, $shop);

          if ($location) {

              $location = Location::validate($request->location, $shop);

              return response()->json([
                  'success' => true,
                  'uid'   => $location->uid
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
        //$product = Product::uid($request->location, $request->product);

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
        $locationValidate = Location::uid($request->location);
        $locationItem = InventarieLocation::uid($request->item);

        if ($request->modalitie == 'automatic') {

            $products = json_decode($request->products, true);;


            foreach ($products as $product) {

                $productItem = Product::uid($product['uid']);
                // $locationProductItem = $productItem->localization;
                //$locationProductItem = 1;

                $inventarieItem = new InventarieLocationItem();
                $inventarieItem->uid = $this->generate_uid('inventarie_locations_items');
                $inventarieItem->product_id = $productItem->id;
                $inventarieItem->location_id = $locationItem->id;
                $inventarieItem->original_id = null;
                $inventarieItem->validate_id = $locationValidate->id;
                $inventarieItem->user_id = $user->id;
                $inventarieItem->count = 1;
                $inventarieItem->condition_id = 1;

                // if($inventarieItem->validate_id == $inventarieItem->original_id){
                //    $inventarieItem->condition_id = 1;
                // }

                $inventarieItem->save();
            }


            $itemsGroupedByProduct = $locationItem->items() // Relación de items
            ->select('product_id', DB::raw('count(*) as product_count'))
                ->groupBy('product_id')
                ->get();

            foreach ($itemsGroupedByProduct as $itemGroup) {

                //$product = Product::id($itemGroup->product_id);
                //$shopId = $user->shop_id;

                //if ($product) {
                //    $location = ProductLocation::where('product_id', $product->id)->first();
                //    $location->count+= $itemGroup->product_count;
                //    $location->update();
                //}

                $product = Product::id($itemGroup->product_id);
                $shopId = $user->shop_id;

                    $locations = $product->localizations->filter(function($localization) use ($shopId) {
                        return $localization->shop_id == $shopId;
                    });$locations->first();

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
                $inventarieItem->uid = $this->generate_uid('inventarie_locations_items');
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

                $itemsGroupedByProduct = $locationItem->items() // Relación de items
                ->select('product_id', DB::raw('SUM(count) as total_count'))
                ->groupBy('product_id')  // Agrupar por 'product_id'
                ->get();

                foreach ($itemsGroupedByProduct as $itemGroup) {

                            //$product = Product::id($itemGroup->product_id);
                           // $shopId = $user->shop_id;

                           // if ($product) {
                           //     $location = ProductLocation::where('product_id', $product->id)->first();
                           //     $location->count+= $itemGroup->total_count;
                           //     $location->update();
                           // }

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

              //$locationItem->complete = 1;
              $locationItem->update();

                  return response()->json([
                    'success' => true,
                    'message' => 'Se creo el curso correctamente',
                  ]);

      }


}


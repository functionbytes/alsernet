<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\Enterprise\Enterprise;
use App\Models\Product\Product;
use App\Models\Newsletter;
use App\Models\Product\ProductLocation;
use App\Structure\Elements;

class DashboardController extends Controller
{
    public function dashboard(){


        //     $products = Product::get()->take(20);
        //   dd($products);
        //foreach ($products as $product) {
        //   $product->count= count($product->items) > 0 ? $product->items->sum('count') : 0;
        //   $product->save();
        //}
        //dd();


        //$locations = Product::where('validate', 1)->take(10000)->get();

        //foreach ($locations as $location) {



          //  $location->count = $location->items ? $location->items->sum('count') : 0;
            // Guarda los cambios de 'count' y marca como validado
          //  $location->validate = 0;
          //  $location->save();
        //}

        return view('managers.views.dashboard.index')->with([
        ]);

    }

}

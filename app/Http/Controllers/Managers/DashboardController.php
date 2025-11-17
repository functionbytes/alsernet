<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Enterprise\Enterprise;
use App\Models\Prestashop\Category;
use App\Models\Subscriber;
use App\Models\Product\ProductLocation;
use App\Structure\Elements;

class DashboardController extends Controller
{
    public function dashboard(){

        $product = Category::find(15)->langs->first()->procesar();
        dd($product);

        return view('managers.views.dashboard.index')->with([
        ]);

    }

}

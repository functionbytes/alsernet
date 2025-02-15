<?php


namespace App\Http\Controllers\Inventaries\Products;


use App\Http\Controllers\Controller;
use App\Models\Faq\FaqCategorie;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Faq\Template;

class BarcodeController extends Controller
{

    public function all(Request $request){

        $products = Product::all()->take(100);

        return view('inventaries.views.products.barcodes.all')->with([
            'products' => $products,
        ]);

    }
    public function single($uid){

        $product = Product::uid($uid);

        return view('inventaries.views.products.barcodes.all')->with([
            'product' => $product,
        ]);
    }

}

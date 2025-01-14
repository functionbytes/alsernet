<?php

namespace App\Http\Controllers\Managers\Products;

use App\Models\App;
use App\Models\Product\ProductLocation;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductsController extends Controller
{


    public function validate(Request $request){

        $products = App::where('validate', 0)->take(6000)->get();


        foreach ($products as $product) {

            $existingProduct = Product::where('reference', $product->reference)->first();

            if ($existingProduct) {
                // Actualizar título y slug
                $existingProduct->title = Str::upper($product->title);
                $existingProduct->slug  = Str::slug(Str::lower($product->title), '-');

                // Verificar y actualizar referencia si está vacía
                if (empty($existingProduct->reference) && !empty($product->reference)) {
                    $existingProduct->reference = $product->reference;
                }

                // Verificar y actualizar barcode si está vacío
                if (empty($existingProduct->barcode) && !empty($product->barcode)) {
                    $existingProduct->barcode = $product->barcode;
                }

                $existingProduct->save();

            } else {
                // Crear nuevo producto si no existe
                $newProduct = new Product;
                $newProduct->slack = $this->generate_slack('products');
                $newProduct->title = Str::upper($product->title);
                $newProduct->slug  = Str::slug(Str::lower($product->title), '-');
                $newProduct->reference = $product->reference;
                $newProduct->barcode = $product->barcode;
                $newProduct->available = 1;
                $newProduct->save();

                $newProductLocation = new ProductLocation();
                $newProductLocation->product_id = $newProduct->id;
                $newProductLocation->location_id = null;
                $newProductLocation->shop_id = 1;
                $newProductLocation->count = 0;
                $newProductLocation->save();

            }

            $product->validate = 1;
            $product->save();
            dump($product->reference);


        }

    }



    public function index(Request $request){

            $searchKey = null ?? $request->search;
            $available = null ?? $request->available;

            $products = Product::latest();

            if ($searchKey) {
                $products->when(!strpos($searchKey, '-'), function ($query) use ($searchKey) {
                    $query->where('products.reference', 'like', '%' . $searchKey . '%')
                        ->orWhere('products.barcode', 'like', '%' . $searchKey . '%')
                        ->orWhere('products.title', 'like', '%' . $searchKey . '%');
                });
            }

            if ($available != null) {
                $products = $products->where('available', $available);
            }

            $products = $products->paginate(paginationNumber());

            return view('managers.views.products.products.index')->with([
                'products' => $products,
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

          return view('managers.views.products.products.create')->with([
              'availables' => $availables
            ]);

      }

      public function edit($slack){

            $product = Product::slack($slack);

            $availables = collect([
                ['id' => '1', 'label' => 'Publico'],
                ['id' => '0', 'label' => 'Oculto'],
            ]);

            $availables = $availables->pluck('label','id');

            return view('managers.views.products.products.edit')->with([
              'product' => $product,
              'availables' => $availables,
            ]);

      }


      public function update(Request $request){

          $product = Product::slack($request->slack);
          $product->title = Str::upper($request->title);
          $product->slug  = Str::slug($request->title, '-');
          $product->reference = $request->reference;
          $product->barcode = $request->barcode;
          $product->available = $request->available;
          $product->update();

          return response()->json([
            'success' => true,
            'slack' => $product->slack,
            'message' => 'Se actualizo el producto correctamente',
          ]);

      }

      public function store(Request $request){

          $product = new Product;
          $product->slack = $this->generate_slack('products');
          $product->title = Str::upper($request->title);
          $product->slug  = Str::slug($request->title, '-');
          $product->reference = $request->reference;
          $product->barcode = $request->barcode;
          $product->available = $request->available;
          $product->save();

          return response()->json([
            'success' => true,
            'slack' => $product->slack,
            'message' => 'Se creo el producto correctamente',
          ]);

      }

    public function destroy($slack){
        $product = Product::slack($slack);
        $product->delete();
        return redirect()->route('manager.products');
    }

}


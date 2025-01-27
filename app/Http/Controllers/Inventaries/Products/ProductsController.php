<?php

namespace App\Http\Controllers\Inventaries\Products;

use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductsController extends Controller
{

    public function index(Request $request){

            $searchKey = null ?? $request->search;
            $available = null ?? $request->available;

            $products = Product::latest();

            if ($searchKey != null) {
                $products = $products->where('title', 'like', '%' . $searchKey . '%');
            }

            if ($available != null) {
                $products = $products->where('available', $available);
            }

            $products = $products->paginate(paginationNumber());

            return view('inventaries.views.products.products.index')->with([
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

          return view('inventaries.views.products.products.create')->with([
              'availables' => $availables
            ]);

      }

      public function edit($slack){

            $product = Product::uid($slack);

            $availables = collect([
                ['id' => '1', 'label' => 'Publico'],
                ['id' => '0', 'label' => 'Oculto'],
            ]);

            $availables = $availables->pluck('label','id');

            return view('inventaries.views.products.products.edit')->with([
              'product' => $product,
              'availables' => $availables,
            ]);

      }


      public function update(Request $request){

          $product = Product::uid($request->uid);
          $product->title = Str::upper($request->title);
          $product->slug  = Str::slug($request->title, '-');
          $product->reference = $request->reference;
          $product->barcode = $request->barcode;
          $product->available = $request->available;
          $product->update();

          return response()->json([
            'status' => true,
            'slack' => $product->uid,
            'message' => 'Se actualizo la clase correctamente',
          ]);

      }

      public function store(Request $request){

          $product = new Product;
          $product->uid = $this->generate_uid('products');
          $product->title = Str::upper($request->title);
          $product->slug  = Str::slug($request->title, '-');
          $product->reference = $request->reference;
          $product->barcode = $request->barcode;
          $product->available = $request->available;
          $product->save();

          return response()->json([
            'status' => true,
            'slack' => $product->uid,
            'message' => 'Se creo el curso correctamente',
          ]);

      }

      public function getThumbnails($slack){

        $product = Product::uid($slack);

        if ($product->getMedia('thumbnail')->count()>0) {

            $thumbnails = $product->getMedia('thumbnail');

            foreach ($thumbnails as $thumbnail) {

                $images[] = [
                    'id' => $thumbnail->id,
                    'uuid' => $thumbnail->uuid,
                    'name' => $thumbnail->name,
                    'file' => $thumbnail->file_name,
                    'path' => $thumbnail->getfullUrl(),
                    'size' =>  $thumbnail->size
                ];
            }

            return response()->json($images);
        }

        $images = [];

        return response()->json($images);

    }

    public function storeThumbnails(Request $request){

        if($request->hasFile('file') && $request->file('file')->isValid()){

            $product = Product::uid(Str::remove('"', $request->product));
            $product->addMediaFromRequest('file')->toMediaCollection('thumbnail');

            return response()->json(['status' => "success", 'product' => $product->uid]);

        }

    }

    public function deleteThumbnails($id){
        Media::find($id)->delete();
        return response()->json(['status' => "success"]);
    }

    public function destroy($slack){
        $product = Product::uid($slack);
        $product->delete();
        return redirect()->route('manager.products');
    }

}


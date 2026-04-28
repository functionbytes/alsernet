<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Catalog\Models\App;
use Modules\Warehouse\Models\Product\Product;
use Modules\Warehouse\Models\Product\ProductLocation;

class ProductsController extends Controller
{
    public function validateProductShop()
    {
        $products = Product::where('validate', 0)->take(7000)->get();

        foreach ($products as $product) {
            $newProductLocation = new ProductLocation;
            $newProductLocation->product_id = $product->id;
            $newProductLocation->location_id = null;
            $newProductLocation->shop_id = 2;
            $newProductLocation->count = 0;
            $newProductLocation->kardex = 0;
            $newProductLocation->management = 0;
            $newProductLocation->save();
            $product->validate = 1;
            $product->save();
        }

    }

    public function validateProductShops()
    {
        $products = Product::where('validate', 0)
            ->take(7000)
            ->get();

        foreach ($products as $product) {

            $newProductLocation = new ProductLocation;
            $newProductLocation->product_id = $product->id;
            $newProductLocation->location_id = null;
            $newProductLocation->shop_id = 2;
            $newProductLocation->count = 0;
            $newProductLocation->kardex = 0;
            $newProductLocation->management = 0;
            $newProductLocation->save();

            $product->validate = 1;
            $product->save();
        }

    }

    public function validateManagement(Request $request)
    {
        $products = App::where('validate', 1)->take(4000)->get();

        foreach ($products as $product) {

            $existingProduct = Product::where('reference', $product->reference)->first();

            if ($existingProduct) {

                $existingProduct->management = $product->count;
                $existingProduct->save();

            }

            $product->validate = 0;
            $product->save();
        }
    }

    public function validateProducts(Request $request)
    {
        $products = App::where('validate', 0)->take(1000)->get();

        foreach ($products as $product) {

            $existingProduct = Product::where('reference', $product->reference)->first();

            if ($existingProduct) {

                $existingProduct->management = $product->management;
                $existingProduct->save();

                // Validar si tiene ProductLocation
                $existingProductLocation = ProductLocation::where('product_id', $existingProduct->id)->first();
                if (! $existingProductLocation) {
                    $newProductLocation = new ProductLocation;
                    $newProductLocation->product_id = $existingProduct->id;
                    $newProductLocation->location_id = null;
                    $newProductLocation->shop_id = 1;
                    $newProductLocation->count = 0;
                    $newProductLocation->kardex = 0;
                    $newProductLocation->management = $product->management;
                    $newProductLocation->save();
                }
            } else {

                $newProduct = new Product;
                $newProduct->uid = $this->generate_uid('inventaries');
                $newProduct->title = Str::upper($product->title);
                $newProduct->slug = Str::slug(Str::lower($product->title), '-');
                $newProduct->reference = $product->reference;
                $newProduct->barcode = $product->barcode;
                $newProduct->available = 1;
                $newProduct->save();

                $newProductLocation = new ProductLocation;
                $newProductLocation->product_id = $newProduct->id;
                $newProductLocation->location_id = null;
                $newProductLocation->shop_id = 1;
                $newProductLocation->count = 0;
                $newProductLocation->kardex = 0;
                $newProductLocation->management = $product->management;
                $newProductLocation->save();
            }

            $product->validate = 1;
            $product->save();
            dump($product->reference);
        }
    }

    public function validates(Request $request)
    {

        $products = App::where('validate', 0)->take(6000)->get();

        foreach ($products as $product) {

            $existingProduct = Product::where('reference', $product->reference)->first();

            if ($existingProduct) {
                // Actualizar título y slug
                $existingProduct->title = Str::upper($product->title);
                $existingProduct->slug = Str::slug(Str::lower($product->title), '-');

                // Verificar y actualizar referencia si está vacía
                if (empty($existingProduct->reference) && ! empty($product->reference)) {
                    $existingProduct->reference = $product->reference;
                }

                // Verificar y actualizar barcode si está vacío
                if (empty($existingProduct->barcode) && ! empty($product->barcode)) {
                    $existingProduct->barcode = $product->barcode;
                }

                $existingProduct->save();

            } else {
                // Crear nuevo producto si no existe
                $newProduct = new Product;
                $newProduct->uid = $this->generate_uid('inventaries');
                $newProduct->title = Str::upper($product->title);
                $newProduct->slug = Str::slug(Str::lower($product->title), '-');
                $newProduct->reference = $product->reference;
                $newProduct->barcode = $product->barcode;
                $newProduct->available = 1;
                $newProduct->save();

                $newProductLocation = new ProductLocation;
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

    public function index(Request $request)
    {

        $searchKey = null ?? $request->search;
        $available = null ?? $request->available;

        $products = Product::orderBy('count', 'desc');

        if ($searchKey) {
            $products->when(! strpos($searchKey, '-'), function ($query) use ($searchKey) {
                $query->where('inventaries.reference', 'like', '%'.$searchKey.'%')
                    ->orWhere('inventaries.barcode', 'like', '%'.$searchKey.'%')
                    ->orWhere('inventaries.title', 'like', '%'.$searchKey.'%');
            });
        }

        if ($available != null) {
            $products = $products->where('available', $available);
        }

        $products = $products->paginate(10000);

        foreach ($products as $product) {
            Product::kardex($product);
        }

        return view('theme.views.inventaries.inventaries.index')->with([
            'inventaries' => $products,
            'available' => $available,
            'searchKey' => $searchKey,
        ]);

    }

    public function indexs(Request $request)
    {

        $searchKey = null ?? $request->search;
        $available = null ?? $request->available;

        $products = Product::orderBy('count', 'desc');

        // $inventaries = Product::latest()->get();
        // foreach ($inventaries as $product) {
        //   $product->count= count($product->items) > 0 ? $product->items->sum('count') : 0;
        //   $product->save();
        // }
        // dd();

        if ($searchKey) {
            $products->when(! strpos($searchKey, '-'), function ($query) use ($searchKey) {
                $query->where('inventaries.reference', 'like', '%'.$searchKey.'%')
                    ->orWhere('inventaries.barcode', 'like', '%'.$searchKey.'%')
                    ->orWhere('inventaries.title', 'like', '%'.$searchKey.'%');
            });
        }

        if ($available != null) {
            $products = $products->where('available', $available);
        }

        $products = $products->paginate(100);

        // foreach ($inventaries as $product) {
        //   Product::kardex($product);
        // }

        return view('theme.views.inventaries.inventaries.index')->with([
            'inventaries' => $products,
            'available' => $available,
            'searchKey' => $searchKey,
        ]);

    }

    public function create()
    {

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables->prepend('', '');
        $availables = $availables->pluck('label', 'id');

        return view('theme.views.inventaries.inventaries.create')->with([
            'availables' => $availables,
        ]);

    }

    public function edit($uid)
    {

        $product = Product::uid($uid);

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label', 'id');

        return view('theme.views.inventaries.inventaries.edit')->with([
            'product' => $product,
            'availables' => $availables,
        ]);

    }

    public function locations($uid)
    {

        $product = Product::uid($uid);
        $locations = $product->locations();

        $locations = $locations->paginate(paginationNumber());

        return view('theme.views.inventaries.inventaries.locations')->with([
            'product' => $product,
            'locations' => $locations,
        ]);

    }

    public function details($uid)
    {

        $product = Product::uid($uid);
        $items = $product->items();

        $items = $items->paginate(paginationNumber());

        return view('theme.views.inventaries.inventaries.details')->with([
            'product' => $product,
            'items' => $items,
        ]);

    }

    public function update(Request $request)
    {

        $product = Product::uid($request->uid);
        $product->title = Str::upper($request->title);
        $product->slug = Str::slug($request->title, '-');
        $product->reference = $request->reference;
        $product->barcode = $request->barcode;
        $product->available = $request->available;
        $product->update();

        return response()->json([
            'success' => true,
            'uid' => $product->uid,
            'message' => 'Se actualizo el producto correctamente',
        ]);

    }

    public function store(Request $request)
    {

        $product = new Product;
        $product->uid = $this->generate_uid('inventaries');
        $product->title = Str::upper($request->title);
        $product->slug = Str::slug($request->title, '-');
        $product->reference = $request->reference;
        $product->barcode = $request->barcode;
        $product->available = $request->available;
        $product->save();

        $newProductLocation = new ProductLocation;
        $newProductLocation->product_id = $product->id;
        $newProductLocation->location_id = null;
        $newProductLocation->shop_id = 1;
        $newProductLocation->count = 0;
        $newProductLocation->kardex = 0;
        $newProductLocation->management = $product->management;
        $newProductLocation->save();

        return response()->json([
            'success' => true,
            'uid' => $product->uid,
            'message' => 'Se creo el producto correctamente',
        ]);

    }

    public function destroy($uid)
    {
        $product = Product::uid($uid);
        $product->delete();

        return redirect()->route('manager.inventaries');
    }
}

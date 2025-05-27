<?php

namespace App\Http\Controllers\Managers\Shops\Shops;

use App\Models\Shop;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShopsController  extends Controller
{
    public function index(Request $request){

        $searchKey = null ?? $request->search;
        $available = null ?? $request->available;

        $shops = Shop::latest();

        if ($searchKey != null) {
            $shops = $shops->where('title', 'like', '%' . $searchKey . '%');
        }

        if ($available != null) {
            $shops = $shops->where('available', $available);
        }

        $shops = $shops->paginate(paginationNumber());

        return view('managers.views.shops.shops.index')->with([
            'shops' => $shops,
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

        return view('managers.views.shops.shops.create')->with([
            'availables' => $availables
        ]);

    }

    public function edit($uid){

        $shop = Shop::uid($uid);

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label','id');

        return view('managers.views.shops.shops.edit')->with([
            'shop' => $shop,
            'availables' => $availables,
        ]);

    }


    public function update(Request $request){

        $shop = Shop::uid($request->uid);
        $shop->title = Str::upper($request->title);
        $shop->slug  = Str::slug($request->title, '-');
        $shop->available = $request->available;
        $shop->update();

        return response()->json([
            'success' => true,
            'uid' => $shop->uid,
            'message' => 'Se actualizo la clase correctamente',
        ]);

    }

    public function store(Request $request){

        $shop = new Shop;
        $shop->uid = $this->generate_uid('shops');
        $shop->title = Str::upper($request->title);
        $shop->slug  = Str::slug($request->title, '-');
        $shop->available = $request->available;
        $shop->save();

        return response()->json([
            'success' => true,
            'uid' => $shop->uid,
            'message' => 'Se creo el curso correctamente',
        ]);

    }

    public function destroy($uid){
        $shop = Shop::uid($uid);
        $shop->delete();
        return redirect()->route('manager.shops');
    }

}


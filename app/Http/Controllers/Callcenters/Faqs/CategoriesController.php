<?php

namespace App\Http\Controllers\Callcenters\Faqs;

use App\Http\Controllers\Controller;
use App\Models\Faq\FaqCategorie;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoriesController extends Controller
{
    public function index(Request $request){

            $searchKey = null ?? $request->search;
            $available = null ?? $request->available;

            $categories = FaqCategorie::descending();

            if ($searchKey) {
                $categories = $categories->where('title', 'like', '%' . $searchKey . '%');
            }

            if ($request->available != null) {
                $categories = $categories->where('available', $available);
            }

            $categories = $categories->paginate(paginationNumber());

            return view('callcenters.views.faqs.categories.index')->with([
                'categories' => $categories,
                'available' => $available,
                'searchKey' => $searchKey,
            ]);

    }
    public function create(){

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label','id');

        return view('callcenters.views.faqs.categories.create')->with([
            'availables' => $availables
        ]);

    }
    public function view($uid){

        $categorie = FaqCategorie::uid($uid);

        return view('callcenters.views.faqs.categories.view')->with([
            'categorie' => $categorie
        ]);

    }
    public function edit($uid){

        $categorie = FaqCategorie::uid($uid);

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label','id');

        return view('callcenters.views.faqs.categories.edit')->with([
            'categorie' => $categorie,
            'availables' => $availables
        ]);

    }
    public function update(Request $request){

        $categorie = FaqCategorie::uid($request->uid);
        $categorie->title = $request->title;
        $categorie->slug  = Str::slug($request->title, '-');
        $categorie->available = $request->available;
        $categorie->update();

        return response()->json([
            'success' => true,
            'message' => 'Se ha atualizado correctamente.'
        ]);

    }
    public function store(Request $request){

        $categorie = new FaqCategorie;
        $categorie->uid = $this->generate_uid('faqs_categories');
        $categorie->title = $request->title;
        $categorie->slug  = Str::slug($request->title, '-');
        $categorie->available = $request->available;
        $categorie->save();

        return response()->json([
            'success' => true,
            'message' => 'Se ha creado correctamente.'
        ]);

    }
    public function destroy($uid){

        $categorie = FaqCategorie::uid($uid);
        $categorie->delete();

        return redirect()->route('support.categories.blogs');

    }

}

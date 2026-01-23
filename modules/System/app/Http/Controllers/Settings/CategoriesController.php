<?php

namespace Modules\System\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function index(Request $request)
    {

        $searchKey = null ?? $request->search;
        $available = null ?? $request->available;

        $categories = Categorie::descending();

        if ($searchKey) {
            $categories = $categories->where('title', 'like', '%'.$searchKey.'%');
        }

        if ($request->available != null) {
            $categories = $categories->where('available', $available);
        }

        $categories = $categories->paginate(paginationNumber());

        return view('theme.views.backups.categories.index')->with([
            'categories' => $categories,
            'available' => $available,
            'searchKey' => $searchKey,
        ]);

    }

    public function create()
    {

        return view('theme.views.backups.categories.create')->with([

        ]);

    }

    public function view($uid)
    {

        $categorie = Categorie::uid($uid);

        return view('theme.views.backups.categories.view')->with([
            'categorie' => $categorie,
        ]);

    }

    public function edit($uid)
    {

        $categorie = Categorie::uid($uid);

        return view('theme.views.backups.categories.edit')->with([
            'categorie' => $categorie,
        ]);

    }

    public function store(Request $request)
    {

        $categorie = new Categorie;
        $categorie->uid = $this->generate_uid('categories');
        $categorie->title = $request->title;
        $categorie->available = $request->available;
        $categorie->save();

        return response()->json([
            'success' => true,
            'message' => 'Se ha creado correctamente',
        ]);

    }

    public function update(Request $request)
    {

        $categorie = Categorie::uid($request->uid);
        $categorie->title = $request->title;
        $categorie->available = $request->available;
        $categorie->update();

        return response()->json([
            'success' => true,
            'message' => 'Se ha actualizo correctamente',
        ]);

    }

    public function destroy($uid)
    {

        $categorie = Categorie::uid($uid);
        $categorie->delete();

        return redirect()->back();

    }
}

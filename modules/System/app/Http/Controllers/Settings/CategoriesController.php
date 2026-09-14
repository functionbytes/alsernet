<?php

namespace Modules\System\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\System\Http\Requests\StoreCategorieRequest;
use Modules\System\Http\Requests\UpdateCategorieRequest;

class CategoriesController extends Controller
{
    public function index(Request $request): View
    {

        $searchKey = $request->search ?? null;
        $available = $request->available ?? null;

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

    public function create(): View
    {

        return view('theme.views.backups.categories.create')->with([

        ]);

    }

    public function view(string $uid): View
    {

        $categorie = Categorie::uid($uid);

        return view('theme.views.backups.categories.view')->with([
            'categorie' => $categorie,
        ]);

    }

    public function edit(string $uid): View
    {

        $categorie = Categorie::uid($uid);

        return view('theme.views.backups.categories.edit')->with([
            'categorie' => $categorie,
        ]);

    }

    public function store(StoreCategorieRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $categorie = new Categorie;
        $categorie->uid = $this->generate_uid('categories');
        $categorie->title = $validated['title'];
        $categorie->available = $validated['available'];
        $categorie->save();

        return response()->json([
            'success' => true,
            'message' => 'Se ha creado correctamente',
        ]);

    }

    public function update(UpdateCategorieRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $categorie = Categorie::uid($validated['uid']);
        $categorie->title = $validated['title'];
        $categorie->available = $validated['available'];
        $categorie->update();

        return response()->json([
            'success' => true,
            'message' => 'Se ha actualizo correctamente',
        ]);

    }

    public function destroy(string $uid): RedirectResponse
    {

        $categorie = Categorie::uid($uid);
        $categorie->delete();

        return redirect()->back();

    }
}

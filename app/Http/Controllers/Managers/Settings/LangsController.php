<?php

namespace App\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Citie;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Lang;

class LangsController extends Controller
{
    public function index(Request $request){

            $searchKey = null ?? $request->search;
            $available = null ?? $request->available;

            $langs = Lang::descending();

            if ($searchKey) {
                $langs = $langs->where('title', 'like', '%' . $searchKey . '%');
            }

            if ($request->available != null) {
                $langs = $langs->where('available', $available);
            }

            $langs = $langs->paginate(paginationNumber());

            return view('managers.views.settings.langs.index')->with([
                'langs' => $langs,
                'available' => $available,
                'searchKey' => $searchKey,
            ]);

    }

    public function create(){

        $categories = Categorie::orderBy('title' , 'desc')->pluck('title','id');

        return view('managers.views.settings.langs.create')->with([
            'categories' => $categories,
        ]);

    }

    public function view($uid){

        $lang = Lang::uid($uid);

        return view('managers.views.settings.langs.view')->with([
            'categorie' => $lang
        ]);

    }

    public function edit($uid){

        $lang = Lang::uid($uid);

        $categories = Categorie::orderBy('title' , 'desc')->pluck('title','id');

        return view('managers.views.settings.langs.edit')->with([
            'lang' => $lang,
            'categories' => $categories,
        ]);

    }

    public function store(Request $request){

        $lang = new Lang;
        $lang->uid = $this->generate_uid('langs');
        $lang->title = $request->title;
        $lang->iso_code = $request->iso_code;
        $lang->lenguage_code = $request->lenguage_code;
        $lang->locate = $request->locate;
        $lang->date_format_full = $request->date_format_full;
        $lang->date_format_lite = $request->date_format_lite;
        $lang->available = $request->available;
        $lang->save();

        if ($request->has('categories')) {
            $categoriesIds = array_filter(explode(',', $request->categories));
            $lang->categories()->syncWithoutDetaching($categoriesIds);
        }

        return response()->json([
            'success' => true,
            'message' => 'Se ha creado correctamente',
        ]);

    }

    public function update(Request $request){

        $lang = Lang::uid($request->uid);
        $lang->title = $request->title;
        $lang->iso_code = $request->iso_code;
        $lang->lenguage_code = $request->lenguage_code;
        $lang->locate = $request->locate;
        $lang->date_format_full = $request->date_format_full;
        $lang->date_format_lite = $request->date_format_lite;
        $lang->available = $request->available;
        $lang->update();

        if ($request->has('categories')) {
            $categoriesIds = array_filter(explode(',', $request->categories));
            $lang->categories()->syncWithoutDetaching($categoriesIds);
        }

        return response()->json([
            'success' => true,
            'message' => 'Se ha actualizo correctamente',
        ]);

    }

    public function destroy($uid){

        $lang = Lang::uid($uid);
        $lang->delete();

        return redirect()->back();

    }


    public static function getCategories(Request $request)
    {
        $formatted_tags = [];

        if (!empty($request->term)) {
            $lang = Lang::where('id', $request->term)->first();
            $categories = $lang->categories;

            foreach ($categories as $categorie) {
                $formatted_tags[] = [
                    'id' => $categorie->id,
                    'text' => $categorie->title
                ];
            }
        }

        return response()->json($formatted_tags);
    }



}

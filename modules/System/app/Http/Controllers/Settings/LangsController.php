<?php

namespace Modules\System\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Lang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\System\Http\Requests\StoreLangRequest;
use Modules\System\Http\Requests\UpdateLangRequest;

class LangsController extends Controller
{
    public function index(Request $request): View
    {

        $searchKey = $request->search ?? null;
        $available = $request->available ?? null;

        $langs = Lang::descending();

        if ($searchKey) {
            $langs = $langs->where('title', 'like', '%'.$searchKey.'%');
        }

        if ($request->available != null) {
            $langs = $langs->where('available', $available);
        }

        $langs = $langs->paginate(paginationNumber());

        return view('theme.views.backups.langs.index')->with([
            'langs' => $langs,
            'available' => $available,
            'searchKey' => $searchKey,
        ]);

    }

    public function create(): View
    {

        $categories = Categorie::orderBy('title', 'desc')->pluck('title', 'id');

        return view('theme.views.backups.langs.create')->with([
            'categories' => $categories,
        ]);

    }

    public function view(string $uid): View
    {

        $lang = Lang::uid($uid);

        return view('theme.views.backups.langs.view')->with([
            'categorie' => $lang,
        ]);

    }

    public function edit(string $uid): View
    {

        $lang = Lang::uid($uid);

        $categories = Categorie::orderBy('title', 'desc')->pluck('title', 'id');

        return view('theme.views.backups.langs.edit')->with([
            'lang' => $lang,
            'categories' => $categories,
        ]);

    }

    public function store(StoreLangRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $lang = new Lang;
        $lang->uid = $this->generate_uid('langs');
        $lang->title = $validated['title'];
        $lang->iso_code = $validated['iso_code'];
        $lang->lenguage_code = $validated['lenguage_code'];
        $lang->locate = $validated['locate'];
        $lang->date_format_full = $validated['date_format_full'];
        $lang->date_format_lite = $validated['date_format_lite'];
        $lang->available = $validated['available'];
        $lang->save();

        if (! empty($validated['categories'])) {
            $categoriesIds = array_filter(explode(',', $validated['categories']));
            $lang->categories()->syncWithoutDetaching($categoriesIds);
        }

        return response()->json([
            'success' => true,
            'message' => 'Se ha creado correctamente',
        ]);

    }

    public function update(UpdateLangRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $lang = Lang::uid($validated['uid']);
        $lang->title = $validated['title'];
        $lang->iso_code = $validated['iso_code'];
        $lang->lenguage_code = $validated['lenguage_code'];
        $lang->locate = $validated['locate'];
        $lang->date_format_full = $validated['date_format_full'];
        $lang->date_format_lite = $validated['date_format_lite'];
        $lang->available = $validated['available'];
        $lang->update();

        if (! empty($validated['categories'])) {
            $categoriesIds = array_filter(explode(',', $validated['categories']));
            $lang->categories()->syncWithoutDetaching($categoriesIds);
        }

        return response()->json([
            'success' => true,
            'message' => 'Se ha actualizo correctamente',
        ]);

    }

    public function destroy(string $uid): RedirectResponse
    {

        $lang = Lang::uid($uid);
        $lang->delete();

        return redirect()->back();

    }

    public static function getCategories(Request $request): JsonResponse
    {
        $formatted_tags = [];

        if (! empty($request->term)) {
            $lang = Lang::where('id', $request->term)->first();
            $categories = $lang->categories;

            foreach ($categories as $categorie) {
                $formatted_tags[] = [
                    'id' => $categorie->id,
                    'text' => $categorie->title,
                ];
            }
        }

        return response()->json($formatted_tags);
    }
}

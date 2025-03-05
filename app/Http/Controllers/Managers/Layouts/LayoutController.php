<?php

namespace App\Http\Controllers\Managers\Layouts;

use App\Http\Controllers\Controller;
use App\Models\Lang;
use App\Models\Layout\Layout;
use App\Models\Template\Template;
use Illuminate\Http\Request;

class LayoutController extends Controller
{

    public function index(Request $request)
    {

        $searchKey = null ?? $request->search;
        $available = null ?? $request->available;
        $layouts = Layout::getAll();
        //dd($layouts->get());

        if ($searchKey) {
            $layouts = $layouts->where('title', 'like', '%' . $searchKey . '%');
        }

        if ($request->available != null) {
            $layouts = $layouts->where('available', $available);
        }

        $layouts = $layouts->paginate(paginationNumber());

        return view('managers.views.layouts.layouts.index', [
            'layouts' => $layouts,
            'available' => $available,
            'searchKey' => $searchKey,
        ]);
    }

    public function create(Request $request)
    {

        $user = $request->user();
        $layout = new Layout();
        $tags = $layout->allTags();

        if (null !== $request->old()) {
            $layout->fill($request->old());
        }

        $langs = Lang::available()->get()->pluck('title','id');

        return view('managers.views.layouts.layouts.create', [
            'layout' => $layout,
            'tags' => $tags,
            'langs' => $langs,
        ]);

    }

    public function store(Request $request)
    {
    }


    public function show($id)
    {
    }

    public function edit(Request $request, $id)
    {
        $user = $request->user();
        $layout = Layout::findByUid($id);

        if (null !== $request->old()) {
            $layout->fill($request->old());
        }

        $langs = Lang::available()->get()->pluck('title','id');

        return view('managers.views.layouts.layouts.edit', [
            'layout' => $layout,
            'langs' => $langs,
        ]);
    }


    public function update(Request $request, $id)
    {

        $user = $request->user();
        $layout = Layout::findByUid($id);

        if ($request->isMethod('patch')) {
            $rules = array(
                'content' => 'required',
                'subject' => 'required',
            );

            $validator = \Validator::make($request->all(), $rules);

            // redirect if fails
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->all()[0],
                ], 400);
            }

            if ($layout->alias == 'sender_verification_email_for_amazon_ses' && preg_match("/\<((meta)|(title)|(style))/i", $request->content)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El contenido de la plantilla no incluye excepto las etiquetas: meta|título|estilo. Elimine esas etiquetas si existen y vuelva a intentarlo.',
                ], 400);
            }

            $layout->fill($request->all());
            $layout->save();

            $request->session()->flash('alert-success', trans('messages.layout.updated'));
            return response()->json([
                'status' => 'success',
                'url' => route('manager.layouts.edit', $layout->uid),
            ]);
        }
    }


}

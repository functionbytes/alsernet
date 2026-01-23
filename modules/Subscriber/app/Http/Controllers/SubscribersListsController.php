<?php

namespace Modules\Subscriber\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Lang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Subscriber\Models\Subscriber;
use Modules\Subscriber\Models\SubscriberList;

class SubscribersListsController extends Controller
{
    public function index(Request $request)
    {

        $searchKey = null ?? $request->search;
        $available = null ?? $request->available;

        $lists = SubscriberList::orderBy('title', 'desc');

        if ($searchKey) {
            $lists->when(! strpos($searchKey, '-'), function ($query) use ($searchKey) {
                $query->where('lists.title', 'like', '%'.$searchKey.'%');
            });
        }

        if ($available != null) {
            $lists = $lists->where('available', $available);
        }

        $lists = $lists->paginate(100);

        return view('theme.views.subscribers.lists.index')->with([
            'lists' => $lists,
            'available' => $available,
            'searchKey' => $searchKey,
        ]);

    }

    public function create()
    {

        $categories = Categorie::available()->get()->pluck('title', 'id');
        $langs = Lang::available()->get()->prepend('', '')->pluck('title', 'id');

        return view('theme.views.subscribers.lists.create')->with([
            'langs' => $langs,
            'categories' => $categories,
        ]);

    }

    public function edit($uid)
    {

        $list = SubscriberList::uid($uid);
        $categories = $list->lang->categories()->available()->get()->pluck('title', 'id');

        $langs = Lang::available()->get()->pluck('title', 'id');

        return view('theme.views.subscribers.lists.edit')->with([
            'list' => $list,
            'langs' => $langs,
            'categories' => $categories,
        ]);

    }

    public function details(Request $request, $uid)
    {
        // Obtener la lista por UID
        $list = SubscriberList::uid($uid);

        // Obtener la búsqueda si existe
        $searchKey = $request->search ?? null;

        // Relación correcta con paginación
        $items = SubscriberList::where('subscriber_lists.id', $list->id) // 🔹 SOLUCIÓN AQUÍ
            ->join('subscriber_list_users', 'subscriber_list_users.list_id', '=', 'subscriber_lists.id')
            ->join('subscribers', 'subscribers.id', '=', 'subscriber_list_users.subscriber_id')
            ->when($searchKey, function ($query) use ($searchKey) {
                $query->where(function ($q) use ($searchKey) {
                    $q->where('subscribers.firstname', 'like', "%$searchKey%")
                        ->orWhere('subscribers.lastname', 'like', "%$searchKey%")
                        ->orWhere(DB::raw("CONCAT(subscribers.firstname, ' ', subscribers.lastname)"), 'like', "%$searchKey%")
                        ->orWhere('subscribers.email', 'like', "%$searchKey%");
                });
            })
            ->select(
                'subscribers.*',
                'subscriber_list_users.id as list_user_id', // 🔹 Evita conflicto de 'id'
                'subscriber_lists.id as list_id' // 🔹 Evita conflicto de 'id'
            )
            ->paginate(100);

        // Lista de opciones disponibles
        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ])->pluck('label', 'id');

        return view('theme.views.subscribers.lists.details')->with([
            'list' => $list,
            'items' => $items,
            'availables' => $availables,
            'searchKey' => $searchKey,
        ]);
    }

    public function includes(Request $request, $uid)
    {

        $list = SubscriberList::uid($uid);
        $itemsListIds = $list->subscribers->pluck('id');
        $subscribers = Subscriber::whereNotIn('id', $itemsListIds)->latest()->pluck('email', 'id');

        return view('theme.views.subscribers.lists.components')->with([
            'list' => $list,
            'subscribers' => $subscribers,
        ]);

    }

    public function updateIncludes(Request $request)
    {

        $list = SubscriberList::uid($request->list);

        if ($request->has('subscribers')) {
            $subscribersIds = array_filter(explode(',', $request->subscribers));
            $list->users()->syncWithoutDetaching($subscribersIds);
        }

        return response()->json([
            'success' => true,
            'uid' => $list->uid,
            'message' => 'Se actualizo la lista correctamente',
        ]);

    }

    public function update(Request $request)
    {

        $list = SubscriberList::uid($request->uid);
        $list->title = Str::upper($request->title);
        $list->available = $request->available;
        $list->default = $request->default;
        $list->code = Str::upper($request->code);
        $list->lang_id = $request->lang;
        $list->update();

        $categoryIds = collect(explode(',', $request->categories))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->toArray();

        $list->categories()->sync($categoryIds);

        return response()->json([
            'success' => true,
            'uid' => $list->uid,
            'message' => 'Se actualizo la lista correctamente',
        ]);

    }

    public function store(Request $request)
    {

        if (SubscriberList::where('code', Str::upper($request->code))->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El código ya está en uso. Por favor, elige otro.',
            ]);
        }

        $list = new SubscriberList;
        $list->title = Str::upper($request->title);
        $list->available = $request->available;
        $list->default = $request->default;
        $list->code = Str::upper($request->code);
        $list->lang_id = $request->lang;
        $list->save();

        $categoryIds = collect(explode(',', $request->categories))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->toArray();

        $list->categories()->sync($categoryIds);

        return response()->json([
            'success' => true,
            'uid' => $list->uid,
            'message' => 'Se creo el la lista correctamente',
        ]);

    }

    public function destroy($uid)
    {
        $list = SubscriberList::uid($uid);
        $list->delete();

        return redirect()->route('manager.subscribers.lists');
    }
}

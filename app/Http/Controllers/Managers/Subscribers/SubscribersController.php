<?php

namespace App\Http\Controllers\Managers\Subscribers;

use App\Jobs\UpdateSubscriberCategoriesJob;
use App\Library\Log;
use App\Models\Subscriber\SubscriberLog;
use App\Models\Subscriber\Subscriber;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Categorie;
use App\Models\Lang;


class SubscribersController extends Controller
{

    public function index(Request $request){

        $searchKey = null ?? $request->search;

        $subscribers = Subscriber::search($searchKey);

        $subscribers = $subscribers->paginate(100);

        return view('managers.views.subscribers.subscribers.index')->with([
            'subscribers' => $subscribers,
            'searchKey' => $searchKey,
        ]);

    }
    public function edit($uid){

        $subscriber = Subscriber::uid($uid);
        $categories =  $subscriber->lang->categories()->available()->get()->pluck('title','id');
        $langs = Lang::available()->get()->prepend('' , '')->pluck('title','id');

        return view('managers.views.subscribers.subscribers.edit')->with([
            'subscriber' => $subscriber,
            'categories' => $categories,
            'langs' => $langs,
        ]);

    }

    public function logs(Request $request,$uid){

        $subscriber = Subscriber::uid($uid);

        if (!$subscriber) {
            abort(404, 'Suscriptor no encontrado');
        }

        $query = $subscriber->logs()->with('causer')->orderBy('created_at', 'desc');

        if ($request->has('search') && !empty($request->search)) {
            $query->where('log_name', 'LIKE', '%' . $request->search . '%')
                ->orWhereHas('causer', function ($q) use ($request) {
                    $q->where('name', 'LIKE', '%' . $request->search . '%');
                });
        }

        $logs = $query->paginate(20);

        return view('managers.views.subscribers.subscribers.logs')->with([
            'subscriber' => $subscriber,
            'logs' => $logs,
        ]);

    }

    public function create(){

        $categories = Categorie::available()->get()->pluck('title','id');
        $langs = Lang::available()->get()->prepend('' , '')->pluck('title','id');

        return view('managers.views.subscribers.subscribers.create')->with([
            'categories' => $categories,
            'langs' => $langs,
        ]);

    }

    public function update(Request $request)
    {
        $auth = app('managers');
        $subscriber = Subscriber::uid($request->uid);

        if (!$subscriber) {
            return response()->json([
                'success' => false,
                'message' => 'Suscriptor no encontrado.',
            ]);
        }

        $data = [
            'firstname'   => Str::upper($request->firstname),
            'lastname'    => Str::upper($request->lastname),
            'email'       => Str::lower($request->email),
            'commercial'  => $request->commercial,
            'parties'     => $request->parties,
            'observation' => $request->observation,
            'lang_id'     => $request->lang,
        ];

        $previousLangId = (int) $subscriber->lang_id;
        $currentLangId = (int) $request->lang;

        $categories = is_array($request->categories)  ? $request->categories : (empty($request->categories) ? [] : explode(',', $request->categories));

        $hasLangChanged = $previousLangId !== $currentLangId;
        $hasCategoryChanges = $subscriber->categories()->count() !== count($categories);
        $changes = collect($data)->filter(fn($value, $key) => $subscriber->$key !== $value)->isNotEmpty();

        if ($changes || $hasCategoryChanges || $hasLangChanged) {

            if ($changes) {
                $subscriber->updateWithLog($data, $auth);
            }

            if ($hasCategoryChanges || $hasLangChanged) {
                //$subscriber->updateCategoriesWithLog($categories, $auth, $hasLangChanged, $currentLangId,$previousLangId);
                UpdateSubscriberCategoriesJob::dispatch(
                    $subscriber,
                    $categories,
                    $auth,
                    $hasLangChanged,
                    $currentLangId,
                    $previousLangId
                );
            }

            return response()->json([
                'success' => true,
                'uid' => $subscriber->uid,
                'message' => 'Suscriptor actualizado correctamente.',
            ]);

        } else {

            return response()->json([
                'success' => false,
                'message' => 'No hay cambios para actualizar.',
            ]);

        }

    }


    public function store(Request $request){

        $subscriber = new Subscriber;
        $subscriber->uid = $this->generate_uid('subscribers');
        $subscriber->firstname = Str::upper($request->firstname);
        $subscriber->lastname  =  Str::upper($request->lastname);
        $subscriber->email = Str::upper($request->email);
        $subscriber->parties = $request->parties;
        $subscriber->commercial = $request->commercial;
        $subscriber->lang_id = $request->lang;
        $subscriber->check_at = $request->check_at;
        $subscriber->update();

        if ($request->has('categories')) {
            $categoriesIds = array_filter(explode(',', $request->categories));
            $subscriber->categories()->attach($categoriesIds);
        }

        return response()->json([
            'success' => true,
            'uid' => $subscriber->uid,
            'message' => 'Se creo el producto correctamente',
        ]);

    }

    public function destroy($uid){
        $subscriber = Product::uid($uid);
        $subscriber->delete();
        return redirect()->route('manager.products');
    }

}



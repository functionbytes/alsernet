<?php

namespace App\Http\Controllers\Managers\Subscribers;

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
        $subscribers = Subscriber::latest();

        if ($searchKey) {
            $subscribers->when(!strpos($searchKey, '-'), function ($query) use ($searchKey) {
                $query->where('subscribers.firstname', 'like', '%' . $searchKey . '%')
                    ->orWhere('subscribers.lastname', 'like', '%' . $searchKey . '%')
                    ->orWhere(DB::raw("CONCAT(subscribers.firstname, ' ', subscribers.lastname)"), 'like', '%' . $searchKey . '%')
                    ->orWhere('subscribers.email', 'like', '%' . $searchKey . '%');
            });
        }

        $subscribers = $subscribers->paginate(100);

        return view('shops.views.subscribers.subscribers.index')->with([
            'subscribers' => $subscribers,
            'searchKey' => $searchKey,
        ]);

    }
    public function edit($slack){

        $subscriber = Subscriber::uid($slack);
        $categories = Categorie::available()->get()->prepend('' , '')->pluck('title','id');
        $langs = Lang::available()->get()->prepend('' , '')->pluck('title','id');


        return view('shops.views.subscribers.subscribers.edit')->with([
            'subscriber' => $subscriber,
            'categories' => $categories,
            'langs' => $langs,
        ]);

    }


    public function logs(Request $request,$slack){

        $subscriber = Subscriber::uid($slack);

        if (!$subscriber) {
            abort(404, 'Suscriptor no encontrado');
        }

        // Obtener logs a través de la relación con 'causer'
        $query = $subscriber->logs()->with('causer')->orderBy('created_at', 'desc');

        // Aplicar filtro de búsqueda si se envía un término
        if ($request->has('search') && !empty($request->search)) {
            $query->where('log_name', 'LIKE', '%' . $request->search . '%')
                ->orWhereHas('causer', function ($q) use ($request) {
                    $q->where('name', 'LIKE', '%' . $request->search . '%');
                });
        }


        $logs = $query->paginate(20);

        return view('shops.views.subscribers.subscribers.logs')->with([
            'subscriber' => $subscriber,
            'logs' => $logs,
        ]);



    }


    public function create(){

        $categories = Categorie::available()->get()->pluck('title','id');
        $langs = Lang::available()->get()->prepend('' , '')->pluck('title','id');

        return view('shops.views.subscribers.subscribers.create')->with([
            'categories' => $categories,
            'langs' => $langs,
        ]);

    }

    public function update(Request $request){


        $auth = app('shops');
        $subscriber = Subscriber::uid($request->uid);

        $data = [
            'user' => $subscriber->id,
            'firstname' => Str::upper($request->firstname),
            'lastname' => Str::upper($request->lastname),
            'email' => Str::lower($request->email),
            'erp' => $request->erp,
            'lopd' => $request->lopd,
            'none' => $request->none,
            'sports' => $request->sports,
            'parties' => $request->parties,
            'suscribe' => $request->suscribe,
            'lang_id' => $request->lang,
            'check_at' => $request->check_at,
        ];

        $subscriber->updateSubscriber($data);


        if ($request->has('categories')) {

            $categoriesIds = array_filter(explode(',', $request->categories));
            $currentCategories = $subscriber->categories->pluck('id')->toArray();

            $addedCategories = array_diff($categoriesIds, $currentCategories);
            $removedCategories = array_diff($currentCategories, $categoriesIds);

            foreach ($addedCategories as $categoryId) {
                SubscriberLog::create([
                    'log_name' => 'category',
                    'description' => "Added category ID {$categoryId}",
                    'properties' => json_encode([
                        'old_value' => null,
                        'new_value' => $categoryId
                    ]),
                    'user_properties' => json_encode([
                        'user' => $auth,
                        'shop' => $auth->hasRole('shops') ? $auth->shop : null
                    ]),
                    'subject_type' => self::class,
                    'subject_id' => $subscriber->id,
                    'causer_type' => auth()->check() ? get_class($auth) : null,
                    'causer_id' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($removedCategories as $categoryId) {
                SubscriberLog::create([
                    'log_name' => 'category',
                    'description' => "Removed category ID {$categoryId}",
                    'properties' => json_encode([
                        'old_value' => $categoryId,
                        'new_value' => null
                    ]),
                    'user_properties' => json_encode([
                        'user' => $auth,
                        'shop' => auth()->check() && $auth->hasRole('shops') ? $auth->shop : null
                    ]),
                    'subject_type' => self::class,
                    'subject_id' => $subscriber->id,
                    'causer_type' => auth()->check() ? get_class($auth) : null,
                    'causer_id' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $subscriber->categories()->sync($categoriesIds);

        } else {
            foreach ($subscriber->categories->pluck('id') as $categoryId) {
                SubscriberLog::create([
                    'log_name' => 'category',
                    'description' => "Removed all categories",
                    'properties' => json_encode([
                        'old_value' => $categoryId,
                        'new_value' => null
                    ]),
                    'user_properties' => json_encode([
                        'user' => $auth,
                        'shop' => $auth->hasRole('shops') ? $auth->shop : null
                    ]),
                    'subject_type' => self::class,
                    'subject_id' => $subscriber->id,
                    'causer_type' => auth()->check() ? get_class($auth) : null,
                    'causer_id' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $subscriber->categories()->detach();
        }


        return response()->json([
            'success' => true,
            'slack' => $subscriber->uid,
            'message' => 'Se actualizo el producto correctamente',
        ]);

    }

    public function store(Request $request){

        $subscriber = new Subscriber;
        $subscriber->uid = $this->generate_uid('subscribers');
        $subscriber->firstname = Str::upper($request->firstname);
        $subscriber->lastname  =  Str::upper($request->lastname);
        $subscriber->email = Str::upper($request->email);
        $subscriber->erp = $request->erp;
        $subscriber->lopd = $request->lopd;
        $subscriber->none = $request->none;
        $subscriber->sports = $request->sports;
        $subscriber->parties = $request->parties;
        $subscriber->suscribe = $request->suscribe;
        $subscriber->lang_id = $request->lang;
        $subscriber->check_at = $request->check_at;
        $subscriber->update();

        if ($request->has('categories')) {
            $categoriesIds = array_filter(explode(',', $request->categories));
            $subscriber->categories()->attach($categoriesIds);
        }

        return response()->json([
            'success' => true,
            'slack' => $subscriber->uid,
            'message' => 'Se creo el producto correctamente',
        ]);

    }

    public function destroy($slack){
        $subscriber = Product::uid($slack);
        $subscriber->delete();
        return redirect()->route('manager.products');
    }





}

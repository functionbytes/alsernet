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

        return view('managers.views.subscribers.subscribers.index')->with([
            'subscribers' => $subscribers,
            'searchKey' => $searchKey,
        ]);

    }
    public function edit($uid){

        $subscriber = Subscriber::uid($uid);
        $categories = Categorie::available()->get()->prepend('' , '')->pluck('title','id');
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
        try {
            $auth = app('managers');
            $subscriber = Subscriber::uid($request->uid);

            // Verificar si el suscriptor existe
            if (!$subscriber) {
                return response()->json([
                    'success' => false,
                    'message' => 'Suscriptor no encontrado.',
                ], 404);
            }

            // Validar y procesar los datos
            $data = [
                'firstname'   => Str::upper($request->firstname),
                'lastname'    => Str::upper($request->lastname),
                'email'       => Str::lower($request->email),
                'erp'         => $request->erp,
                'lopd'        => $request->lopd,
                'none'        => $request->none,
                'sports'      => $request->sports,
                'parties'     => $request->parties,
                'suscribe'    => $request->suscribe,
                'observation' => $request->observation,
                'lang_id'     => $request->lang, // Nuevo idioma
            ];

            // 🔹 Obtener el idioma anterior directamente desde la base de datos
            $previousLangId = (int) Subscriber::where('id', $subscriber->id)->value('lang_id');
            $currentLangId = (int) $request->lang; // Nuevo idioma recibido en la solicitud

            // Detectar si hubo un cambio de idioma
            $hasLangChanged = $previousLangId !== $currentLangId;

            // Detectar cambios en las categorías
            $categories = is_array($request->categories)
                ? $request->categories
                : (empty($request->categories) ? [] : explode(',', $request->categories));

            $hasCategoryChanges = $subscriber->categories()->count() !== count($categories);

            // Detectar cambios en los datos del suscriptor
            $changes = collect($data)->filter(fn($value, $key) => $subscriber->$key !== $value)->isNotEmpty();

            Log::info("📌 LangID Anterior={$previousLangId}, NuevoLangID={$currentLangId}, hasLangChanged=" . ($hasLangChanged ? 'Sí' : 'No'));
            Log::info("📌 Cambios detectados: " . ($changes ? 'Sí' : 'No') . " | Cambio en categorías: " . ($hasCategoryChanges ? 'Sí' : 'No'));

            if ($changes || $hasCategoryChanges || $hasLangChanged) {
                if ($changes) {
                    $subscriber->updateWithLog($data, $auth);
                }

                if ($hasCategoryChanges || $hasLangChanged) {
                    Log::info("📌 Enviando parámetros a updateCategoriesWithLog()...");
                    $subscriber->updateCategoriesWithLog($categories, $auth, $hasLangChanged, $currentLangId,$previousLangId);
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
        } catch (\Exception $e) {
            Log::error("📌 Error en update(): " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el suscriptor.',
                'error' => $e->getMessage(),
            ], 500);
        }
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



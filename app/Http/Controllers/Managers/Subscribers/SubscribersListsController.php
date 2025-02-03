<?php

namespace App\Http\Controllers\Managers\Subscribers;

use App\Models\Lang;
use App\Models\Subscriber\Subscriber;
use App\Models\Subscriber\SubscriberList;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\Subscriber\NewsletterLIstUser;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscribersListsController extends Controller
{


    public function index(Request $request){

            $searchKey = null ?? $request->search;
            $available = null ?? $request->available;

            $lists = SubscriberList::orderBy('title' , 'desc');

            if ($searchKey) {
                $lists->when(!strpos($searchKey, '-'), function ($query) use ($searchKey) {
                    $query->where('lists.title', 'like', '%' . $searchKey . '%');
                });
            }

            if ($available != null) {
                $lists = $lists->where('available', $available);
            }

            $lists = $lists->paginate(100);

            return view('managers.views.subscribers.lists.index')->with([
                'lists' => $lists,
                'available' => $available,
                'searchKey' => $searchKey,
            ]);

        }



      public function create(){

          $availables = collect([
              ['id' => '1', 'title' => 'Publico'],
              ['id' => '0', 'title' => 'Oculto'],
          ]);

          $availables->prepend('' , '');
          $availables = $availables->pluck('title','id');

          $langs = Lang::available()->get();
          $langs->prepend('' , '');
          $langs = $langs->pluck('title','id');


          return view('managers.views.subscribers.lists.create')->with([
              'availables' => $availables,
              'langs' => $langs
            ]);

      }

      public function edit($slack){

            $list = SubscriberList::uid($slack);

            $availables = collect([
                ['id' => '1', 'title' => 'Publico'],
                ['id' => '0', 'title' => 'Oculto'],
            ]);

            $availables = $availables->pluck('title','id');

            $langs = Lang::available()->get();
            $langs = $langs->pluck('title','id');

            return view('managers.views.subscribers.lists.edit')->with([
              'list' => $list,
                'availables' => $availables,
                'langs' => $langs,
            ]);

      }



    public function details(Request $request,$slack){

        $list = SubscriberList::uid($slack);
        $items = $list->newsletters();

        $searchKey = null ?? $request->search;

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label','id');

        if ($searchKey) {
            $items = $items->join('newsletters', 'subscribers.id', '=', 'newsletter_lists_users.newsletter_id')
                ->where(function ($query) use ($searchKey) {
                    $query->where('subscribers.firstname', 'like', '%' . $searchKey . '%')
                        ->orWhere('subscribers.lastname', 'like', '%' . $searchKey . '%')
                        ->orWhere(DB::raw("CONCAT(subscribers.firstname, ' ', subscribers.lastname)"), 'like', '%' . $searchKey . '%')
                        ->orWhere('subscribers.email', 'like', '%' . $searchKey . '%');
                })
                ->select('subscribers.*' , 'newsletter_lists_users.id as id');
        }else{
            $items = $items->join('newsletters', 'subscribers.id', '=', 'newsletter_lists_users.newsletter_id')->select('subscribers.*' , 'newsletter_lists_users.id as id');
        }

        $items = $items->paginate(100);

        return view('managers.views.subscribers.lists.details')->with([
            'list' => $list,
            'items' => $items,
            'availables' => $availables,
            'searchKey' => $searchKey,
        ]);

    }


    public function includes(Request $request,$slack){

        $list = SubscriberList::uid($slack);
        $itemsListIds = $list->newsletters->pluck('id');

        $newsletters = Subscriber::whereNotIn('id', $itemsListIds)->latest()->pluck('email', 'id');

        return view('managers.views.subscribers.lists.includes')->with([
            'list' => $list,
            'newsletters' => $newsletters
        ]);

    }

    public function updateIncludes(Request $request){

        $list = SubscriberList::uid($request->list);

        if ($request->has('newsletters')) {
            $newslettersIds = array_filter(explode(',', $request->newsletters));
            $list->users()->syncWithoutDetaching($newslettersIds);
        }

        return response()->json([
            'success' => true,
            'slack' => $list->uid,
            'message' => 'Se actualizo la lista correctamente',
        ]);

    }

    public function update(Request $request){

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'available' => 'required|boolean',
            'code' => 'required|string|max:255|unique:newsletter_lists,code,' . $request->uid,
        ]);


        $list = SubscriberList::uid($request->uid);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first('code');
            return response()->json([
                'success' => false,
                'slack' => $list->uid,
                'message' => $firstError,
            ]);
        }

          $list->title = Str::upper($request->title);
          $list->available = $request->available;
          $list->code = Str::upper($request->code);
          $list->lang_id = $request->lang;
          $list->update();

          return response()->json([
            'success' => true,
            'slack' => $list->uid,
            'message' => 'Se actualizo la lista correctamente',
          ]);

      }

      public function store(Request $request){

          if (SubscriberList::where('code', Str::upper($request->code))->exists()) {
              return response()->json([
                  'success' => false,
                  'message' => 'El código ya está en uso. Por favor, elige otro.',
              ], 422);
          }

          $list = new SubscriberList;
          $list->uid = $this->generate_uuid('newsletter_lists');
          $list->title = Str::upper($request->title);
          $list->available = $request->available;
          $list->code = Str::upper($request->code);
          $list->lang_id = $request->lang;
          $list->save();

          return response()->json([
            'success' => true,
            'slack' => $list->uid,
            'message' => 'Se creo el la lista correctamente',
          ]);

      }

    public function destroy($slack){
        $list = SubscriberList::uid($slack);
        $list->delete();
        return redirect()->route('manager.subscribers.lists');
    }


}


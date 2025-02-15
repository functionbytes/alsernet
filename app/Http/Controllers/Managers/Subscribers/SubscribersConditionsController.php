<?php

namespace App\Http\Controllers\Managers\Subscribers;

use App\Models\App;
use App\Models\Subscriber\Subscriber;
use App\Models\Subscriber\SubscriberCondition;
use App\Models\Product\ProductLocation;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Models\Kardex;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscribersConditionsController extends Controller
{



    public function index(Request $request){

            $searchKey = null ?? $request->search;
            $available = null ?? $request->available;

            $conditions = SubscriberCondition::orderBy('title' , 'desc');

            if ($searchKey) {
                $conditions->when(!strpos($searchKey, '-'), function ($query) use ($searchKey) {
                    $query->where('subscribers_conditions.title', 'like', '%' . $searchKey . '%');
                });
            }

            if ($available != null) {
                $conditions = $conditions->where('available', $available);
            }

            $conditions = $conditions->paginate(100);

            return view('managers.views.subscribers.conditions.index')->with([
                'conditions' => $conditions,
                'available' => $available,
                'searchKey' => $searchKey,
            ]);

        }


      public function create(){

          $availables = collect([
              ['id' => '1', 'label' => 'Publico'],
              ['id' => '0', 'label' => 'Oculto'],
          ]);

          $availables->prepend('' , '');
          $availables = $availables->pluck('label','id');

          return view('managers.views.subscribers.conditions.create')->with([
              'availables' => $availables
            ]);

      }

      public function edit($uid){

            $condition = SubscriberCondition::uid($uid);

            $availables = collect([
                ['id' => '1', 'label' => 'Publico'],
                ['id' => '0', 'label' => 'Oculto'],
            ]);

            $availables = $availables->pluck('label','id');

            return view('managers.views.subscribers.conditions.edit')->with([
              'condition' => $condition,
              'availables' => $availables,
            ]);

      }


    public function update(Request $request){

         $condition = SubscriberCondition::uid($request->uid);
         $condition->title = Str::upper($request->title);
         $condition->available = $request->available;
         $condition->update();

          return response()->json([
            'success' => true,
            'uid' => $condition->uid,
            'message' => 'Se actualizo el estado correctamente',
          ]);

      }

      public function store(Request $request){

          $condition = new SubscriberCondition;
          $condition->uid = $this->generate_uuid('subscribers_conditions');
          $condition->title = Str::upper($request->title);
          $condition->available = $request->available;
          $condition->save();

          return response()->json([
            'success' => true,
            'uid' => $condition->uid,
            'message' => 'Se creo el estado correctamente',
          ]);

      }

    public function destroy($uid){
        $condition = SubscriberCondition::uid($uid);
        $condition->delete();
        return redirect()->route('manager.subscribers.conditions');
    }

}


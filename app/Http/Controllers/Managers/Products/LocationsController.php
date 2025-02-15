<?php

namespace App\Http\Controllers\Managers\Products;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Plan;

class LocationsController extends Controller
{
    public function index(Request $request){

        $searchKey = null ?? $request->search;
        $available = null ?? $request->available;
        $website = null ?? $request->website;

        $plans = Plan::latest();

        if ($searchKey != null) {
            $plans = $plans->where('title', 'like', '%' . $searchKey . '%');
        }

        if ($available != null) {
            $plans = $plans->where('available', $available);
        }

        if ($website != null) {
            $plans = $plans->where('website', $website);
        }

        $plans = $plans->paginate(paginationNumber());

        return view('managers.views.plans.index')->with([
            'plans' => $plans,
            'available' => $available,
            'website' => $website,
            'searchKey' => $searchKey,
        ]);

    }

    public function navegation( $uid){

          $plan = Plan::uid($uid);

          return view('managers.views.plans.navegation')->with([
              'plan' => $plan,
          ]);

    }

      public function create(){


          $availables = collect([
              ['id' => '1', 'label' => 'Publico'],
              ['id' => '0', 'label' => 'Oculto'],
          ]);

          $availables->prepend('' , '');
          $availables = $availables->pluck('label','id');

          return view('managers.views.plans.create')->with([
              'availables' => $availables
            ]);

      }

      public function edit($uid){

            $plan = Plan::uid($uid);

            $availables = collect([
                ['id' => '1', 'label' => 'Publico'],
                ['id' => '0', 'label' => 'Oculto'],
            ]);

            $availables = $availables->pluck('label','id');

            return view('managers.views.plans.edit')->with([
              'plan' => $plan,
              'availables' => $availables,
            ]);

      }


      public function update(Request $request){

          $plan = Plan::uid($request->uid);
          $plan->title = Str::upper($request->title);
          $plan->price = $request->price;
          $plan->discount = $request->discount;
          $plan->slug  = Str::slug($request->title, '-');
          $plan->description = $request->description;
          $plan->specific = $request->specific;
          $plan->available = $request->available;
          $plan->update();

          return response()->json([
            'status' => true,
            'uid' => $plan->uid,
            'message' => 'Se actualizo la clase correctamente',
          ]);

      }

      public function store(Request $request){

          $plan = new Plan;
          $plan->uid = $this->generate_uid('plans');
          $plan->title = Str::upper($request->title);
          $plan->slug  = Str::slug($request->title, '-');
          $plan->price = $request->price;
          $plan->discount = $request->discount;
          $plan->description = $request->description;
          $plan->specific = $request->specific;
          $plan->available = $request->available;
          $plan->save();

          return response()->json([
            'status' => true,
            'uid' => $plan->uid,
            'message' => 'Se creo el curso correctamente',
          ]);

      }

      public function getThumbnails($uid){

        $plan = Plan::uid($uid);

        if ($plan->getMedia('thumbnail')->count()>0) {

            $thumbnails = $plan->getMedia('thumbnail');

            foreach ($thumbnails as $thumbnail) {

                $images[] = [
                    'id' => $thumbnail->id,
                    'uuid' => $thumbnail->uuid,
                    'name' => $thumbnail->name,
                    'file' => $thumbnail->file_name,
                    'path' => $thumbnail->getfullUrl(),
                    'size' =>  $thumbnail->size
                ];
            }

            return response()->json($images);
        }

        $images = [];

        return response()->json($images);

    }

    public function storeThumbnails(Request $request){

        if($request->hasFile('file') && $request->file('file')->isValid()){

            $plan = Plan::uid(Str::remove('"', $request->plan));
            $plan->addMediaFromRequest('file')->toMediaCollection('thumbnail');

            return response()->json(['status' => "success", 'plan' => $plan->uid]);

        }

    }

    public function deleteThumbnails($id){
        Media::find($id)->delete();
        return response()->json(['status' => "success"]);
    }

    public function destroy($uid){
        $plan = Plan::uid($uid);
        $plan->delete();
        return redirect()->route('manager.plans');
    }

}


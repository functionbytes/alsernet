<?php

namespace App\Http\Controllers\Inventaries\Locations;

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

        return view('inventaries.views.plans.index')->with([
            'plans' => $plans,
            'available' => $available,
            'website' => $website,
            'searchKey' => $searchKey,
        ]);

    }

    public function navegation( $slack){

          $plan = Plan::slack($slack);

          return view('inventaries.views.plans.navegation')->with([
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

          return view('inventaries.views.plans.create')->with([
              'availables' => $availables
            ]);

      }

      public function edit($slack){

            $plan = Plan::slack($slack);

            $availables = collect([
                ['id' => '1', 'label' => 'Publico'],
                ['id' => '0', 'label' => 'Oculto'],
            ]);

            $availables = $availables->pluck('label','id');

            return view('inventaries.views.plans.edit')->with([
              'plan' => $plan,
              'availables' => $availables,
            ]);

      }


      public function update(Request $request){

          $plan = Plan::slack($request->slack);
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
            'slack' => $plan->slack,
            'message' => 'Se actualizo la clase correctamente',
          ]);

      }

      public function store(Request $request){

          $plan = new Plan;
          $plan->slack = $this->generate_slack('plans');
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
            'slack' => $plan->slack,
            'message' => 'Se creo el curso correctamente',
          ]);

      }

      public function getThumbnails($slack){

        $plan = Plan::slack($slack);

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

            $plan = Plan::slack(Str::remove('"', $request->plan));
            $plan->addMediaFromRequest('file')->toMediaCollection('thumbnail');

            return response()->json(['status' => "success", 'plan' => $plan->slack]);

        }

    }

    public function deleteThumbnails($id){
        Media::find($id)->delete();
        return response()->json(['status' => "success"]);
    }

    public function destroy($slack){
        $plan = Plan::slack($slack);
        $plan->delete();
        return redirect()->route('manager.plans');
    }

}

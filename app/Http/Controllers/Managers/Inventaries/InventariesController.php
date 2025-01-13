<?php

namespace App\Http\Controllers\Managers\Inventaries;

use App\Http\Controllers\Controller;
use App\Models\Inventarie\Inventarie;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class InventariesController extends Controller
{
    public function index(Request $request){

        $searchKey = null ?? $request->search;
        $available = null ?? $request->available;

        $inventaries = Inventarie::latest();

        if ($searchKey != null) {
            $inventaries = $inventaries->where('title', 'like', '%' . $searchKey . '%');
        }

        if ($available != null) {
            $inventaries = $inventaries->where('available', $available);
        }

        $inventaries = $inventaries->paginate(paginationNumber());

        return view('managers.views.inventaries.inventaries.index')->with([
            'inventaries' => $inventaries,
            'available' => $available,
            'searchKey' => $searchKey,
        ]);

    }

    public function navegation( $slack){

          $inventarie = Plan::slack($slack);

          return view('managers.views.inventaries.inventaries.navegation')->with([
              'plan' => $inventarie,
          ]);

    }

      public function create(){


          $availables = collect([
              ['id' => '1', 'label' => 'Publico'],
              ['id' => '0', 'label' => 'Oculto'],
          ]);

          $availables->prepend('' , '');
          $availables = $availables->pluck('label','id');

          return view('managers.views.inventaries.inventaries.create')->with([
              'availables' => $availables
            ]);

      }

      public function edit($slack){

            $inventarie = Plan::slack($slack);

            $availables = collect([
                ['id' => '1', 'label' => 'Publico'],
                ['id' => '0', 'label' => 'Oculto'],
            ]);

            $availables = $availables->pluck('label','id');

            return view('managers.views.inventaries.inventaries.edit')->with([
              'plan' => $inventarie,
              'availables' => $availables,
            ]);

      }


      public function update(Request $request){

          $inventarie = Plan::slack($request->slack);
          $inventarie->title = Str::upper($request->title);
          $inventarie->price = $request->price;
          $inventarie->discount = $request->discount;
          $inventarie->slug  = Str::slug($request->title, '-');
          $inventarie->description = $request->description;
          $inventarie->specific = $request->specific;
          $inventarie->available = $request->available;
          $inventarie->update();

          return response()->json([
            'status' => true,
            'slack' => $inventarie->slack,
            'message' => 'Se actualizo la clase correctamente',
          ]);

      }

      public function store(Request $request){

          $inventarie = new Plan;
          $inventarie->slack = $this->generate_slack('plans');
          $inventarie->title = Str::upper($request->title);
          $inventarie->slug  = Str::slug($request->title, '-');
          $inventarie->price = $request->price;
          $inventarie->discount = $request->discount;
          $inventarie->description = $request->description;
          $inventarie->specific = $request->specific;
          $inventarie->available = $request->available;
          $inventarie->save();

          return response()->json([
            'status' => true,
            'slack' => $inventarie->slack,
            'message' => 'Se creo el curso correctamente',
          ]);

      }

      public function getThumbnails($slack){

        $inventarie = Plan::slack($slack);

        if ($inventarie->getMedia('thumbnail')->count()>0) {

            $thumbnails = $inventarie->getMedia('thumbnail');

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

            $inventarie = Plan::slack(Str::remove('"', $request->plan));
            $inventarie->addMediaFromRequest('file')->toMediaCollection('thumbnail');

            return response()->json(['status' => "success", 'plan' => $inventarie->slack]);

        }

    }

    public function deleteThumbnails($id){
        Media::find($id)->delete();
        return response()->json(['status' => "success"]);
    }

    public function destroy($slack){
        $inventarie = Plan::slack($slack);
        $inventarie->delete();
        return redirect()->route('manager.plans');
    }

}


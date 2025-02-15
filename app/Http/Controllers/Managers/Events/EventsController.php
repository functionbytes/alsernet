<?php

namespace App\Http\Controllers\Managers\Events;

use App\Http\Controllers\Controller;
use App\Models\Prestashop\Banner\Banner;
use App\Models\Prestashop\Event\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventsController extends Controller
{

    public function index(Request $request){

            $searchKey = null ?? $request->search;
            $available = null ?? $request->available;

            $events = Event::descending();

            if ($searchKey) {
                $events->when(!strpos($searchKey, '-'), function ($query) use ($searchKey) {
                    $query->where('events.title', 'like', '%' . $searchKey . '%');
                });
            }

            if ($available != null) {
                $events = $events->where('available', $available);
            }

            $events = $events->paginate(10);

            return view('managers.views.events.index')->with([
                'events' => $events,
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

          $options = collect([
              ['id' => '1', 'label' => 'Si'],
              ['id' => '0', 'label' => 'No'],
          ]);

          $options->prepend('' , '');
          $options = $options->pluck('label','id');

          return view('managers.views.events.create')->with([
              'availables' => $availables,
                'options' => $options,
            ]);

      }

      public function edit($uid,$lang = 'es' ){

            $event = Event::uid($uid);

            $availables = collect([
                ['id' => '1', 'label' => 'Publico'],
                ['id' => '0', 'label' => 'Oculto'],
            ]);

            $availables = $availables->pluck('label','id');

            $options = collect([
               ['id' => '1', 'label' => 'Si'],
               ['id' => '0', 'label' => 'No'],
            ]);

            $options = $options->pluck('label','id');

            //$banners = Banner::lang($lang)->with('langs')->get();
            //$banners->prepend('' , '');
            //$banners = $banners->pluck('name','id');

            return view('managers.views.events.edit')->with([
              'event' => $event,
              'availables' => $availables,
                //'banners' => $banners,
                'options' => $options,
            ]);

    }


    public function update(Request $request){

        $event = Event::uid($request->uid);
        $event->title = Str::upper($request->title);
        $event->color_flag = $request->color_flag;
        $event->filter_flag = $request->filter_flag;
        $event->management_flag = $request->management_flag;
        $event->color_buttom = $request->color_buttom;
        $event->hover_buttom = $request->hover_buttom;
        $event->cms = $request->cms;
        $event->featured = $request->featured;
        $event->amazing = $request->amazing;
        $event->available = $request->available;
        $event->completed = $request->completed;
        $event->iva = $request->iva;
        $event->processing = $request->processing;
        $event->processed = $request->processed;
        $event->banners = $request->banners;
        $event->banners_unique = $request->banners_unique;
        $event->banners_backup = $request->banners_backup;
        $event->start_at = $request->start_at;
        $event->end_at = $request->end_at;
        $event->update();

        return response()->json([
            'success' => true,
            'uid' => $event->uid,
            'message' => 'Se actualizo el producto correctamente',
        ]);

      }

      public function store(Request $request){

          $event = new Event;
          $event  = $this->generate_uid('aalv_alsernet_event_manager');
          $event->title = Str::upper($request->title);
          $event->color_flag = $request->color_flag;
          $event->filter_flag = $request->filter_flag;
          $event->management_flag = $request->management_flag;
          $event->color_buttom = $request->color_buttom;
          $event->hover_buttom = $request->hover_buttom;
          $event->cms = $request->cms;
          $event->featured = $request->featured;
          $event->amazing = $request->amazing;
          $event->available = $request->available;
          $event->completed = $request->completed;
          $event->iva = $request->iva;
          $event->processing = $request->processing;
          $event->processed = $request->processed;
          $event->banners = $request->banners;
          $event->banners_unique = $request->banners_unique;
          $event->banners_backup = $request->banners_backup;
          $event->start_at = $request->start_at;
          $event->end_at = $request->end_at;
          $event->save();

          return response()->json([
            'success' => true,
            'uid' => $event->uid,
            'message' => 'Se creo el producto correctamente',
          ]);

      }

    public function destroy($uid){
        $event = Event::uid($uid);
        $event->delete();
        return redirect()->route('manager.events');
    }

}


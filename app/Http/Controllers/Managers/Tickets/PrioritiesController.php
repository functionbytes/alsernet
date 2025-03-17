<?php

namespace App\Http\Controllers\Managers\Tickets;

use App\Models\Ticket\TicketPriority;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrioritiesController extends Controller
{

    public function index(Request $request){

            $searchKey = null ?? $request->search;
            $available = null ?? $request->available;

            $priorities = TicketPriority::descending();

            if ($searchKey) {
                $priorities = $priorities->where('title', 'like', '%' . $searchKey . '%');
            }

            if ($request->available != null) {
                $priorities = $priorities->where('available', $available);
            }

            $priorities = $priorities->paginate(paginationNumber());

            return view('managers.views.tickets.priorities.index')->with([
                'priorities' => $priorities,
                'available' => $available,
                'searchKey' => $searchKey,
            ]);

    }

    public function create(){

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label','id');

        return view('managers.views.tickets.priorities.create')->with([
            'availables' => $availables
        ]);

    }

    public function view($uid){

        $prioritie = TicketPriority::uid($uid);

        return view('managers.views.tickets.priorities.view')->with([
            'prioritie' => $prioritie
        ]);

    }

    public function edit($uid){

        $prioritie = TicketPriority::uid($uid);

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label','id');

        return view('managers.views.tickets.priorities.edit')->with([
            'prioritie' => $prioritie,
            'availables' => $availables
        ]);

    }

    public function update(Request $request){

        $prioritie = TicketPriority::uid($request->uid);
        $prioritie->title = $request->title;
        $prioritie->color = $request->color;
        $prioritie->slug  = Str::slug($request->title, '-');
        $prioritie->available = $request->available;
        $prioritie->update();

        return response()->json([
            'success' => true,
            'uid' => $prioritie->uid,
            'message' => 'Se actualiza la prioridad correctamente',
        ]);

    }

    public function store(Request $request){

        $prioritie = new TicketPriority;
        $prioritie->uid = $this->generate_uid('ticket_priorities');
        $prioritie->title = $request->title;
        $prioritie->color = $request->color;
        $prioritie->slug  = Str::slug($request->title, '-');
        $prioritie->available = $request->available;
        $prioritie->save();

        return response()->json([
            'success' => true,
            'uid' => $prioritie->uid,
            'message' => 'Se creo la prioridad correctamente',
        ]);

    }

    public function destroy($uid){

        $prioritie = TicketPriority::uid($uid);
        $prioritie->delete();

        return redirect()->route('manager.tickets.priorities');

    }

}

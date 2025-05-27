<?php

namespace App\Http\Controllers\Managers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Ticket\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketsController extends Controller
{

    public function index(Request $request){

            $searchKey = null ?? $request->search;
            $available = null ?? $request->available;

            $tickets = Ticket::descending();

            if ($searchKey) {
                $tickets = $tickets->where('title', 'like', '%' . $searchKey . '%');
            }

            if ($request->available != null) {
                $tickets = $tickets->where('available', $available);
            }

            $tickets = $tickets->paginate(paginationNumber());

            return view('managers.views.tickets.tickets.index')->with([
                'tickets' => $tickets,
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

        return view('managers.views.tickets.tickets.create')->with([
            'availables' => $availables
        ]);

    }

    public function view($uid){

        $categorie = Categorie::uid($uid);

        return view('managers.views.tickets.tickets.view')->with([
            'categorie' => $categorie
        ]);

    }

    public function control($uid){

        $categorie = Categorie::uid($uid);

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label','id');

        return view('managers.views.tickets.tickets.control')->with([
            'categorie' => $categorie,
            'availables' => $availables
        ]);

    }
    public function edit($uid){

        $categorie = Categorie::uid($uid);

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label','id');

        return view('managers.views.tickets.tickets.edit')->with([
            'categorie' => $categorie,
            'availables' => $availables
        ]);

    }

    public function update(Request $request){

        $categorie = Categorie::uid($request->uid);
        $categorie->title = $request->title;
        $categorie->slug  = Str::slug($request->title, '-');
        $categorie->available = $request->available;
        $categorie->update();

        return response()->json($categorie->uid);

    }

    public function store(Request $request){

        $categorie = new Categorie;
        $categorie->uid = $this->generate_uid('categories_tickets');
        $categorie->title = $request->title;
        $categorie->slug  = Str::slug($request->title, '-');
        $categorie->available = $request->available;
        $categorie->save();

        return response()->json($categorie->uid);

    }

    public function destroy($uid){

        $categorie = Categorie::uid($uid);
        $categorie->delete();

        return redirect()->route('manager.tickets');

    }

}

<?php

namespace App\Http\Controllers\Managers\Tickets;

use App\Models\Ticket\TicketCategorie;
use App\Models\Ticket\TicketPriority;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoriesController extends Controller
{

    public function index(Request $request){

            $searchKey = null ?? $request->search;
            $available = null ?? $request->available;

            $categories = TicketCategorie::descending();

            if ($searchKey) {
                $categories = $categories->where('title', 'like', '%' . $searchKey . '%');
            }

            if ($request->available != null) {
                $categories = $categories->where('available', $available);
            }

            $categories = $categories->paginate(paginationNumber());

            return view('managers.views.tickets.categories.index')->with([
                'categories' => $categories,
                'available' => $available,
                'searchKey' => $searchKey,
            ]);

    }

    public function create(){

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);


        $priorities = TicketPriority::latest()->available()->get();
        $priorities->prepend('' , '');
        $priorities = $priorities->pluck('title','id');

        $availables = $availables->pluck('label','id');

        return view('managers.views.tickets.categories.create')->with([
            'availables' => $availables,
            'priorities' => $priorities,
        ]);

    }

    public function view($uid){

        $categorie = TicketCategorie::uid($uid);

        $priorities = TicketPriority::latest()->available()->get();
        $priorities->prepend('' , '');
        $priorities = $priorities->pluck('title','id');

        return view('managers.views.tickets.categories.view')->with([
            'categorie' => $categorie,
            'priorities' => $priorities,
        ]);

    }

    public function edit($uid){

        $categorie = TicketCategorie::uid($uid);

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label','id');

        $priorities = TicketPriority::latest()->available()->get();
        $priorities->prepend('' , '');
        $priorities = $priorities->pluck('title','id');

        return view('managers.views.tickets.categories.edit')->with([
            'categorie' => $categorie,
            'priorities' => $priorities,
            'availables' => $availables
        ]);

    }

    public function update(Request $request){

        $categorie = TicketCategorie::uid($request->uid);
        $categorie->title = $request->title;
        $categorie->slug  = Str::slug($request->title, '-');
        $categorie->available = $request->available;
        $categorie->priority_id = $request->prioritie;
        $categorie->update();

        return response()->json([
            'success' => true,
            'uid' => $categorie->uid,
            'message' => 'Se actualiza la categoria correctamente',
        ]);

    }

    public function store(Request $request){

        $categorie = new TicketCategorie;
        $categorie->uid = $this->generate_uid('ticket_categories');
        $categorie->title = $request->title;
        $categorie->slug  = Str::slug($request->title, '-');
        $categorie->available = $request->available;
        $categorie->priority_id = $request->prioritie;
        $categorie->save();

        return response()->json([
            'success' => true,
            'uid' => $categorie->uid,
            'message' => 'Se creo la categoria correctamente',
        ]);

    }

    public function destroy($uid){

        $categorie = TicketCategorie::uid($uid);
        $categorie->delete();

        return redirect()->route('manager.tickets.categories');

    }

}

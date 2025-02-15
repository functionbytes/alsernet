<?php

namespace App\Http\Controllers\Managers\Livechat;

use App\Http\Controllers\Controller;
use App\Models\Ticket\TicketCanned;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CannedsController extends Controller
{

    public function index(Request $request){

            $searchKey = null ?? $request->search;
            $available = null ?? $request->available;

            $canneds = TicketCanned::descending();

            if ($searchKey) {
                $canneds = $canneds->where('title', 'like', '%' . $searchKey . '%');
            }

            if ($request->available != null) {
                $canneds = $canneds->where('available', $available);
            }

            $canneds = $canneds->paginate(paginationNumber());

            return view('managers.views.tickets.canneds.index')->with([
                'canneds' => $canneds,
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

        return view('managers.views.tickets.canneds.create')->with([
            'availables' => $availables
        ]);

    }
    public function view($uid){

        $canned = TicketCanned::uid($uid);

        return view('managers.views.tickets.canneds.view')->with([
            'canned' => $canned
        ]);

    }
    public function edit($uid){

        $canned = TicketCanned::uid($uid);

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label','id');

        return view('managers.views.tickets.canneds.edit')->with([
            'canned' => $canned,
            'availables' => $availables
        ]);

    }
    public function update(Request $request){

        $canned = TicketCanned::uid($request->uid);
        $canned->title = $request->title;
        $canned->messages = $request->description;
        $canned->available = $request->available;
        $canned->update();

        return response()->json([
            'success' => true,
            'message' => 'Se ha actualizado correctamente',
            'uid' => $canned->uid,
        ]);


    }
    public function store(Request $request){


        $canned = new TicketCanned;
        $canned->uid = $this->generate_uid('ticket_canneds');
        $canned->title = $request->title;
        $canned->messages = $request->description;
        $canned->available = $request->available;
        $canned->save();

        return response()->json([
            'success' => true,
            'message' => 'Se ha creado correctamente',
            'uid' => $canned->uid,
        ]);

    }
    public function destroy($uid){

        $canned = TicketCanned::uid($uid);
        $canned->delete();

        return redirect()->route('manager.tickets.canneds');

    }

}

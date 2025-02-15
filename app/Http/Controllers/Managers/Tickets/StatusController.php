<?php

namespace App\Http\Controllers\Managers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Ticket\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StatusController extends Controller
{

    public function index(Request $request){

            $searchKey = null ?? $request->search;
            $available = null ?? $request->available;

            $status = TicketStatus::descending();

            if ($searchKey) {
                $status = $status->where('title', 'like', '%' . $searchKey . '%');
            }

            if ($request->available != null) {
                $status = $status->where('available', $available);
            }

            $status = $status->paginate(paginationNumber());

            return view('managers.views.tickets.status.index')->with([
                'status' => $status,
                'available' => $available,
                'searchKey' => $searchKey,
            ]);

    }

    public function create(){


        return view('managers.views.tickets.status.create')->with([
        ]);

    }

    public function view($uid){

        $status = TicketStatus::uid($uid);

        return view('managers.views.tickets.status.view')->with([
            'categorie' => $status
        ]);

    }

    public function edit($uid){

        $status = TicketStatus::uid($uid);

        return view('managers.views.tickets.status.edit')->with([
            'status' => $status,
        ]);

    }

    public function update(Request $request){

        $status = TicketStatus::uid($request->uid);
        $status->title = $request->title;
        $status->color = $request->color;
        $status->slug  = Str::slug($request->title, '-');
        $status->available = $request->available;
        $status->update();

        return response()->json([
            'success' => true,
            'uid' => $status->uid,
            'message' => 'Se actualiza el estado correctamente',
        ]);

    }

    public function store(Request $request){

        $status = new TicketStatus;
        $status->uid = $this->generate_uid('ticket_status');
        $status->title = $request->title;
        $status->color = $request->color;
        $status->slug  = Str::slug($request->title, '-');
        $status->available = $request->available;
        $status->save();

        return response()->json([
            'success' => true,
            'uid' => $status->uid,
            'message' => 'Se creo el estado correctamente',
        ]);

    }

    public function destroy($uid){

        $status = TicketStatus::uid($uid);
        $status->delete();

        return redirect()->route('manager.tickets.status');

    }

}

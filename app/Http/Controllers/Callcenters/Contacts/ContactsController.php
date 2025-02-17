<?php

namespace App\Http\Controllers\Callcenters\Contacts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;

class ContactsController extends Controller
{
    public function index(Request $request)
    {
        $searchKey = $request->search;
        $reviewed = $request->reviewed;

        $contacts = Contact::orderBy('created_at', 'desc');

        if ($searchKey != null) {
            $contacts = $contacts->where(function($query) use ($searchKey) {
                $query->where('firstname', 'like', '%' . $searchKey . '%')
                    ->orWhere('lastname', 'like', '%' . $searchKey . '%')
                    ->orWhere(DB::raw("CONCAT(firstname, ' ', lastname)"), 'like', '%' . $searchKey . '%');
            });
        }

        if ($reviewed != null) {
            $contacts = $contacts->where('reviewed', $reviewed);
        }

        $contacts = $contacts->paginate(paginationNumber());

        return view('callcenters.views.contacts.index')->with([
            'contacts' => $contacts,
            'reviewed' => $reviewed,
            'searchKey' => $searchKey,
        ]);
    }
    public function edit($uid)
    {

        $contact = Contact::uid($uid);;

        $revieweds = collect([
            ['id' => '1', 'label' => 'Gestionado'],
            ['id' => '0', 'label' => 'Pendiente'],
        ]);

        $revieweds = $revieweds->pluck('label','id');

        return view('callcenters.views.contacts.edit')->with([
            'contact' => $contact,
            'revieweds' => $revieweds,
        ]);

    }
    public function update(Request $request)
    {

        $contact = Contact::uid($request->uid);
        $contact->reviewed =  $request->reviewed;
        $contact->update();

        return response()->json([
            'success' => true,
            'message' => 'Se actualizado correctamente',
        ]);

    }
    public function destroy($uid)
    {
        $contact = Contact::uid($uid);
        $contact->delete();

        return redirect()->route('support.contacts');
    }

}

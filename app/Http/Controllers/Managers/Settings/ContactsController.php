<?php

namespace App\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Contact;

class ContactsController extends Controller
{

    public function index(Request $request){

        $searchKey = null ?? $request->search;
        $reviewed = null ?? $request->reviewed;
        $contacts = Contact::orderBy('created_at', 'desc')->latest();

        if ($searchKey != null) {
            $contacts->when(!strpos($searchKey, '-'), function ($query) use ($searchKey) {
                $query->where('contacts.firstname', 'like', '%' . $searchKey . '%')
                    ->orWhere('contacts.lastname', 'like', '%' . $searchKey . '%')
                    ->orWhere(DB::raw("CONCAT(contacts.firstname, ' ', contacts.lastname)"), 'like', '%' . $searchKey . '%');
            });
        }

        if ($reviewed != null) {
            $contacts = $contacts->where('reviewed', $reviewed);
        }

        $contacts = $contacts->paginate(paginationNumber());

        return view('managers.views.settings.contacts.index')->with([
            'contacts' => $contacts,
            'reviewed' => $reviewed,
            'searchKey' => $searchKey,
        ]);

    }

    public function edit($uid){

        $contact = Contact::uid($uid);;

        $revieweds = collect([
            ['id' => '1', 'label' => 'Gestionado'],
            ['id' => '0', 'label' => 'Pendiente'],
        ]);

        $revieweds = $revieweds->pluck('label','id');

        return view('managers.views.settings.contacts.edit')->with([
            'contact' => $contact,
            'revieweds' => $revieweds,
        ]);

    }


    public function update(Request $request){

        $contact = Contact::uid($request->uid);
        $contact->reviewed =  $request->reviewed;
        $contact->update();

        return response()->json([
            'success' => true,
            'message' => 'Se actualizo correctamente el formulario de contacto',
        ]);


    }

    public function destroy($uid){

        $contact = Contact::uid($uid);
        $contact->delete();

        return redirect()->route('manager.contacts');
    }

}

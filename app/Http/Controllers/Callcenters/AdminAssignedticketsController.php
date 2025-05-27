<?php

namespace App\Http\Controllers\Callcenters;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Ticket\Ticket;
use App\Models\User;
use Auth;
use App\Models\tickethistory;

use Mail;
use App\Mail\mailmailablesend;
use App\Notifications\TicketAssignNotification;

class AdminAssignedticketsController extends Controller
{

    public function create(Request $request)
    {


        $this->validate($request, [
            'assigned_user_id' => 'required',
        ]);

        $calID = Ticket::find($request->assigned_id);
        $calID->myassignuser_id	 = Auth::id();
        $calID->selfassignuser_id = null;
        $calID->save();

        $calID->ticketassignmutliple()->sync($request->assigned_user_id);

        // user informatiom
        $users = User::findOrFail($request->assigned_user_id);
        $useroutput = '';
        foreach($users as $user)
        {
            $useroutput .= '

            <div class="fs-11 font-weight-semibold ps-3">
                <div>
                    <span class="fs-12">'.$user->name.'</span>
                    <span class="text-muted">(Assignee)</span>
                </div>
                <small class="text-muted useroutput" >'.$user->getRoleNames()[0].'</small>
            </div>

            ';

        }
        // Assignee

        $tickethistory = new tickethistory();
        $tickethistory->ticket_id = $calID->id;

        $output = '<div class="d-flex align-items-center">
            <div class="mt-0">
                <p class="mb-0 fs-12 mb-1">Status
            ';
        if($calID->ticketnote->isEmpty()){
            if($calID->overduestatus != null){
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">'.$calID->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$calID->overduestatus.'</span>
                ';
            }else{
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">'.$calID->status.'</span>
                ';
            }

        }else{
            if($calID->overduestatus != null){
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">'.$calID->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$calID->overduestatus.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }else{
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">'.$calID->status.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }
        }

        $output .= '
            <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(Assigner)</span></p>
            '. $useroutput.'
        </div>
        <div class="ms-auto">
        <span class="float-end badge badge-primary-light">
            <span class="fs-11 font-weight-semibold">'.Auth::user()->getRoleNames()[0].'</span>
        </span>
        </div>

        </div>
        ';
        $tickethistory->ticketactions = $output;
        $tickethistory->save();



        $ticketData = [
            'ticket_username' => $calID->cust->username,
            'ticket_id' => $calID->ticket_id,
            'ticket_title' => $calID->subject,
            'ticket_description' => $calID->message,
            'ticket_customer_url' => route('gusetticket', $calID->ticket_id),
            'ticket_admin_url' => url('/admin/ticket-view/'.$calID->ticket_id),
        ];


        try{

            $assignee = $calID->ticketassignmutliples;
            foreach($assignee as $assignees){
                $user = User::where('id',$assignees->toassignuser_id)->get();
                foreach($user as $users){

                    if($users->id == $assignees->toassignuser_id){
                            $users->notify(new TicketAssignNotification($calID));
                            if($users->usetting->emailnotifyon == 1){
                                Mail::to($users->email)
                                    ->send( new mailmailablesend('when_ticket_assign_to_other_employee', $ticketData) );
                            }
                    }
                }
            }

        }catch(\Exception $e){
            return response()->json(['code'=>200, 'success'=> lang('The ticket was successfully assigned.', 'alerts')], 200);
        }

        return response()->json(['code'=>200, 'success'=> lang('The ticket was successfully assigned.', 'alerts')], 200);

    }

    public function show(Request $req, $id){

        if($req->ajax())
        {

            $output = '';

            $assign = Ticket::find($id);
            $assugnuser_id = $assign->ticketassignmutliples->pluck('toassignuser_id')->toArray();

            $data = User::get();

            $total_row = $data->count();

            if($total_row > 0){
                $output .='<option label="Select Agent"></option>';
                foreach($data as $row){
                    if(Auth::user()->id != $row->id){
                        $output .= '
                        <option  value="'.$row->id.'"' .(in_array($row->id, $assugnuser_id)? 'selected': '').  '>'.$row->name.' ('.(!empty($row->getRoleNames()[0])? $row->getRoleNames()[0] : '').')</option>

                        ';
                    }
                }

            }
            $data = array(
                'assign_data'=> $assign,
                'table_data' => $output,
                'total_data' => $total_row
            );

            return response()->json($data);
        }

    }

    public function update(Request $req, $id)
    {
        $calID = Ticket::find($id);
        $calID->myassignuser_id	 = null;
        $calID->selfassignuser_id = null;
        $calID->save();
        $calID->ticketassignmutliple()->detach($req->assigned_userid);

        $tickethistory = new tickethistory();
        $tickethistory->ticket_id = $calID->id;

        $output = '<div class="d-flex align-items-center">
            <div class="mt-0">
                <p class="mb-0 fs-12 mb-1">Status
            ';
        if($calID->ticketnote->isEmpty()){
            if($calID->overduestatus != null){
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">'.$calID->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$calID->overduestatus.'</span>
                ';
            }else{
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">'.$calID->status.'</span>
                ';
            }

        }else{
            if($calID->overduestatus != null){
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">'.$calID->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$calID->overduestatus.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }else{
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">'.$calID->status.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }
        }

        $output .= '
            <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(UnAssigned Ticket)</span></p>
        </div>
        <div class="ms-auto">
        <span class="float-end badge badge-primary-light">
            <span class="fs-11 font-weight-semibold">'.Auth::user()->getRoleNames()[0].'</span>
        </span>
        </div>

        </div>
        ';
        $tickethistory->ticketactions = $output;
        $tickethistory->save();

        return response()->json(['data'=> $calID, 'success'=> lang('Updated successfully', 'alerts')]);
    }
}

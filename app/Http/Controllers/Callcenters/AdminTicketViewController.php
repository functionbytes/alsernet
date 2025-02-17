<?php

namespace App\Http\Controllers\Callcenters;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Apptitle;
use App\Models\Footertext;
use App\Models\Seosetting;
use App\Models\Pages;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketComment;
use App\Models\Ticket\Category;
use App\Models\Groupsusers;
use Auth;
use Illuminate\Support\Facades\DB;
use App\Models\tickethistory;
use App\Models\Customer;

class AdminTicketViewController extends Controller
{
    public function customerprevioustickets($cust_id)
    {
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $users = Customer::find($cust_id);
        $data['users'] = $users;

        $total = Ticket::where('cust_id', $cust_id)->latest('updated_at')->get();
        $data['total'] = $total;

        $custsimillarticket = Ticket::where('cust_id', $cust_id)->latest('updated_at')->get();
        $data['custsimillarticket'] = $custsimillarticket;


        $active = Ticket::where('cust_id', $cust_id)->whereIn('status', ['New', 'Re-Open', 'Inprogress'])->get();
       $data['active'] = $active;

       $closed = Ticket::where('cust_id', $cust_id)->where('status', 'Closed')->get();
        $data['closed'] = $closed;

        $onhold = Ticket::where('cust_id', $cust_id)->where('status', 'On-Hold')->get();
        $data['onhold'] = $onhold;

        return view('admin.viewticket.customerprevioustickets')->with($data);
    }

    public function selfassignticketview()
    {
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $selfassignedtickets = Ticket::where('selfassignuser_id', auth()->id())->where('status', '!=' ,'Closed')->where('status', '!=' ,'Suspend')->latest('updated_at')->get();
        $data['selfassignedtickets'] = $selfassignedtickets;

        // ticket note
        $ticketnote = DB::table('ticketnotes')->pluck('ticketnotes.ticket_id')->toArray();
        $data['ticketnote'] = $ticketnote;


        $selfassignedticketsnew = Ticket::where('selfassignuser_id', auth()->id())->where('status', 'New')->count();
        $data['selfassignedticketsnew'] = $selfassignedticketsnew;

        $selfassignedticketsinprogress = Ticket::where('selfassignuser_id', auth()->id())->where('status', 'Inprogress')->count();
        $data['selfassignedticketsinprogress'] = $selfassignedticketsinprogress;

        $selfassignedticketsonhold = Ticket::where('selfassignuser_id', auth()->id())->where('status', 'On-Hold')->count();
        $data['selfassignedticketsonhold'] = $selfassignedticketsonhold;

        $selfassignedticketsreopen = Ticket::where('selfassignuser_id', auth()->id())->where('status', 'Re-Open')->count();
        $data['selfassignedticketsreopen'] = $selfassignedticketsreopen;

        $selfassignedticketsoverdue = Ticket::where('selfassignuser_id', auth()->id())->where('overduestatus', 'Overdue')->count();
        $data['selfassignedticketsoverdue'] = $selfassignedticketsoverdue;

        $selfassignedticketsclosed = Ticket::where('selfassignuser_id', auth()->id())->where('status', 'Closed')->count();
        $data['selfassignedticketsclosed'] = $selfassignedticketsclosed;

        return view('admin.superadmindashboard.mytickets.selfassignticket')->with($data);
    }

    public function myclosedtickets()
    {
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $myclosedbyuser = Ticket::where('closedby_user', auth()->id())->latest('updated_at')->get();
        $data['myclosedbyuser'] = $myclosedbyuser;

        return view('admin.assignedtickets.myclosedtickets')->with($data);
    }

    public function tickettrashed()
    {
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $tickettrashed = Ticket::onlyTrashed()->latest('updated_at')->get();
        $data['tickettrashed'] = $tickettrashed;

        return view('admin.assignedtickets.trashedticket')->with($data);
    }

    public function tickettrashedrestore(Request $request, $id)
    {
        $tickettrashedrestore = Ticket::onlyTrashed()->findOrFail($id);
        $commenttrashedrestore = $tickettrashedrestore->comments()->onlyTrashed()->get();

        if (count($commenttrashedrestore) > 0) {

            $commenttrashedrestore->each->restore();

            $tickethistory = new tickethistory();
            $tickethistory->ticket_id = $tickettrashedrestore->id;

            $output = '<div class="d-flex align-items-center">
                <div class="mt-0">
                    <p class="mb-0 fs-12 mb-1">Status
                ';
            if($tickettrashedrestore->ticketnote->isEmpty()){
                if($tickettrashedrestore->overduestatus != null){
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$tickettrashedrestore->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$tickettrashedrestore->overduestatus.'</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$tickettrashedrestore->status.'</span>
                    ';
                }

            }else{
                if($tickettrashedrestore->overduestatus != null){
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$tickettrashedrestore->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$tickettrashedrestore->overduestatus.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$tickettrashedrestore->status.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }
            }

            $output .= '
                <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(Ticket Restore)</span></p>
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





            foreach($tickettrashedrestore->ticket_history()->onlyTrashed()->get() as $deletetickethistory)
            {
                $deletetickethistory->restore();
            }


            $tickettrashedrestore->restore();
            return response()->json(['success'=>lang('The ticket was successfully restore.', 'alerts')]);
        }else{


            $tickettrashedrestore->restore();

            $tickethistory = new tickethistory();
            $tickethistory->ticket_id = $tickettrashedrestore->id;

            $output = '<div class="d-flex align-items-center">
                <div class="mt-0">
                    <p class="mb-0 fs-12 mb-1">Status
                ';
            if($tickettrashedrestore->ticketnote->isEmpty()){
                if($tickettrashedrestore->overduestatus != null){
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$tickettrashedrestore->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$tickettrashedrestore->overduestatus.'</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$tickettrashedrestore->status.'</span>
                    ';
                }

            }else{
                if($tickettrashedrestore->overduestatus != null){
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$tickettrashedrestore->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$tickettrashedrestore->overduestatus.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$tickettrashedrestore->status.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }
            }

            $output .= '
                <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(Ticket Restore)</span></p>
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





            foreach($tickettrashedrestore->ticket_history()->onlyTrashed()->get() as $deletetickethistory)
            {
                $deletetickethistory->restore();
            }

            return response()->json(['success'=> lang('The ticket was successfully restore.', 'alerts')]);

        }
    }

    public function tickettrasheddestroy($id)
    {
        $tickettrasheddelete = Ticket::onlyTrashed()->findOrFail($id);
        $commenttrasheddelete = $tickettrasheddelete->comments()->onlyTrashed()->get();


        if (count($commenttrasheddelete) > 0) {
            $media = $tickettrasheddelete->getMedia('ticket');

            foreach ($media as $medias) {

                    $medias->forceDelete();

            }
            $medias = $tickettrasheddelete->comments()->onlyTrashed()->get();

            foreach ($medias as $mediass) {
                foreach($mediass->getMedia('comments') as $mediasss){

                    $mediasss->forceDelete();
                }

            }
            $commenttrasheddelete->each->forceDelete();

            foreach($tickettrasheddelete->ticket_history()->onlyTrashed()->get() as $deletetickethistory)
            {
                $deletetickethistory->forceDelete();
            }
            $tickettrasheddelete->forceDelete();
            return response()->json(['success'=>lang('The ticket was successfully deleted.', 'alerts')]);
        }else{

            $media = $tickettrasheddelete->getMedia('ticket');

            foreach ($media as $medias) {

                    $medias->forceDelete();

            }

            foreach($tickettrasheddelete->ticket_history()->onlyTrashed()->get() as $deletetickethistory)
            {
                $deletetickethistory->forceDelete();
            }
            $tickettrasheddelete->forceDelete();

            return response()->json(['success'=> lang('The ticket was successfully deleted.', 'alerts')]);

        }
    }


    public function tickettrashedview($id)
    {
        $tickettrashedview = Ticket::onlyTrashed()->findOrFail($id);
        $data['tickettrashedview'] = $tickettrashedview;

        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        return view('admin.assignedtickets.trashedticketview')->with($data);
    }


    public function alltrashedticketrestore(Request $request)
    {

        $id_array = $request->input('id');

        $sendmails = Ticket::onlyTrashed()->whereIn('id', $id_array)->get();

        foreach($sendmails as $tickettrashedrestoreall){
            $commenttrashedrestorealls = $tickettrashedrestoreall->comments()->onlyTrashed()->get();
            foreach($commenttrashedrestorealls as $commenttrashedrestoreall){
                    $commenttrashedrestoreall->restore();
            }
            $tickettrashedrestoreall->restore();

        }
        return response()->json(['success'=> lang('The ticket was successfully restore.', 'alerts')]);

    }

    public function alltrashedticketdelete(Request $request)
    {
        $id_array = $request->input('id');

        $sendmails = Ticket::onlyTrashed()->whereIn('id', $id_array)->get();

        foreach($sendmails as $tickettrasheddeleteeall){

            $commenttrasheddeleteall = $tickettrasheddeleteeall->comments()->onlyTrashed()->get();


            if (count($commenttrasheddeleteall) > 0) {
                $media = $tickettrasheddeleteeall->getMedia('ticket');

                foreach ($media as $medias) {

                        $medias->forceDelete();

                }

                foreach ($commenttrasheddeleteall as $mediass) {
                    foreach($mediass->getMedia('comments') as $mediasss){

                        $mediasss->forceDelete();
                    }

                    $mediass->forceDelete();
                }

                foreach($tickettrasheddeleteeall->ticket_history()->onlyTrashed()->get() as $deletetickethistory)
                {
                    $deletetickethistory->forceDelete();
                }


                $sendmails->each->forceDelete();
                return response()->json(['success'=>lang('The ticket was successfully deleted.', 'alerts')]);
            }else{

                $media = $tickettrasheddeleteeall->getMedia('ticket');

                foreach ($media as $medias) {

                    $medias->forceDelete();

                }

                foreach($tickettrasheddeleteeall->ticket_history()->onlyTrashed()->get() as $deletetickethistory)
                {
                    $deletetickethistory->forceDelete();
                }


                $sendmails->each->forceDelete();

                return response()->json(['success'=> lang('The ticket was successfully deleted.', 'alerts')]);

            }

        }
    }


    public function suspend(Request $request)
    {
        if($request->unsuspend == 'Inprogress'){
            $ticketsuspend = Ticket::find($request->ticket_id);
            $ticketsuspend->status = 'Inprogress';
            $ticketsuspend->lastreply_mail = Auth::id();
            $ticketsuspend->update();

            $tickethistory = new tickethistory();
            $tickethistory->ticket_id = $ticketsuspend->id;

            $output = '<div class="d-flex align-items-center">
                <div class="mt-0">
                    <p class="mb-0 fs-12 mb-1">Status
                ';
            if($ticketsuspend->ticketnote->isEmpty()){
                if($ticketsuspend->overduestatus != null){
                    $output .= '
                    <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticketsuspend->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticketsuspend->overduestatus.'</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticketsuspend->status.'</span>
                    ';
                }

            }else{
                if($ticketsuspend->overduestatus != null){
                    $output .= '
                    <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticketsuspend->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticketsuspend->overduestatus.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticketsuspend->status.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }
            }

            $output .= '
                <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(Unsuspended Ticket)</span></p>
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



        }
        else{
            $ticketsuspend = Ticket::find($request->ticket_id);
            $ticketsuspend->status = 'Suspend';
            $ticketsuspend->lastreply_mail = Auth::id();
            $ticketsuspend->update();


            $tickethistory = new tickethistory();
            $tickethistory->ticket_id = $ticketsuspend->id;

            $output = '<div class="d-flex align-items-center">
                <div class="mt-0">
                    <p class="mb-0 fs-12 mb-1">Status
                ';
            if($ticketsuspend->ticketnote->isEmpty()){
                if($ticketsuspend->overduestatus != null){
                    $output .= '
                    <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticketsuspend->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticketsuspend->overduestatus.'</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticketsuspend->status.'</span>
                    ';
                }

            }else{
                if($ticketsuspend->overduestatus != null){
                    $output .= '
                    <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticketsuspend->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticketsuspend->overduestatus.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticketsuspend->status.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }
            }
            $output .= '
                <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(suspended Ticket)</span></p>
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


        }


        return response()->json(['success' => lang('Update Successfully')]);
    }


    public function mysuspendtickets()
    {
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $mysuspendtickets = Ticket::where('status', 'Suspend')->where('lastreply_mail', auth()->id())->latest('updated_at')->get();
        $data['mysuspendtickets'] = $mysuspendtickets;

        return view('admin.assignedtickets.mysuspendtickets')->with($data);
    }

    public function allactiveinprogresstickets()
    {
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $allactiveinprogresstickets = Ticket::where('status', 'Inprogress')->get();
        $data['allactiveinprogresstickets'] = $allactiveinprogresstickets;

        return view('admin.superadmindashboard.activetickets.activeinprogressticket')->with($data);
    }

    public function allactivereopentickets()
    {
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $allactivereopentickets = Ticket::whereIn('status', ['Re-Open'])->get();
        $data['allactivereopentickets'] = $allactivereopentickets;

        return view('admin.superadmindashboard.activetickets.activereopenticket')->with($data);
    }

    public function allactiveonholdtickets()
    {
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $allactiveonholdtickets = Ticket::whereIn('status', ['On-Hold'])->get();
        $data['allactiveonholdtickets'] = $allactiveonholdtickets;

        return view('admin.superadmindashboard.activetickets.activeonholdticket')->with($data);
    }

    public function allactiveassignedtickets()
    {
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $allactiveassignedtickets = Ticket::whereIn('status', ['Re-Open','Inprogress','On-Hold'])->leftjoin('ticketassignchildren', 'ticketassignchildren.ticket_id', 'tickets.id')->where(function($r){
            $r->whereNotNull('toassignuser_id')
            ->orWhereNotNull('selfassignuser_id');
        })->get();
        $data['allactiveassignedtickets'] = $allactiveassignedtickets;

        return view('admin.superadmindashboard.activetickets.activeassignedticket')->with($data);
    }

    public function tickethistory($id)
    {
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;


        $ticket = Ticket::where('ticket_id', $id)->firstOrFail();
        $data['ticket'] = $ticket;
        return view('admin.tickethistory.index')->with($data);
    }

}

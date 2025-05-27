<?php

namespace App\Http\Controllers\Callcenters;

use App\Jobs\Auth\Passwords\ResetPasswordMails;
use App\Models\Ticket\TicketStatus;
use App\Notifications\TicketCreateNotifications;
use App\Mail\Customers\Tickets\NotificatioCustomernMails;
use App\Mail\Customers\Tickets\NotificationCustomerReplayMails;
use App\Http\Controllers\Controller;
use App\Mail\mailmailablesend;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategorie;
use App\Models\Ticket\TicketComment;
use App\Models\Ticket\TicketHistory;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Mail;

class DashboardController extends Controller
{


    public function dashboard(Request $request){


        $categories = TicketCategorie::get();


        $user = Auth::user();
        $searchKey = null ?? $request->search;
        $status = null ?? $request->status;


        // Ticket Counting
        $tickets = Ticket::whereIn('status_id', [1])->latest('updated_at')->get();
        $totaltickets = Ticket::count();
        $totalactivetickets = Ticket::whereIn('status_id', [5, 6, 6])->count();
        $totalclosedtickets = Ticket::where('status_id', 3)->count();
        $replyrecent = Ticket::whereIn('status_id', [5, 6, 6])->where('replystatus', 'Replicado')->count();
        $selfassigncount = Ticket::where('toassignuser_id', $user->id)->where('status_id', '!=', 3)->where('status_id', '!=', 8)->count();
        $recentticketlist = Ticket::where('status_id', 1)->get();
        $recentticketcount = 0;

        foreach ($recentticketlist as $recent) {
            if ($recent->myassignuser_id == null && $recent->toassignuser_id == null && $recent->toassignuser_id == null) {
                $recentticketcount += 1;
            }
        }


        $myassignedticket = Ticket::leftJoin('ticket_assigns', 'ticket_assigns.ticket_id', 'tickets.id')->where('myassignuser_id', $user->id)->where('status_id', '!=', 3)->where('status_id', '!=', 8)->get();
        $myassignedticketcount = 0;
        foreach ($myassignedticket as $recent) {
            if ($recent->toassignuser_id != null) {
                $myassignedticketcount += 1;
            }
        }

       // $myclosedticketcount = Ticket::where('closedby_user', $user->id)->count();
        //$suspendedticketcount = Ticket::where('status_id', 8)->count();
        //$suspendticketcount = Ticket::where('status_id', 8)->where('lastreply_mail', $user->id)->count();

        $myclosedticketcount = 0;
        $suspendedticketcount = 0;
        $suspendticketcount = 0;



        if ($searchKey) {
            $tickets = $tickets->where('title', 'like', '%' . $searchKey . '%');
        }

        if ($request->status != null) {
            $tickets = $tickets->where('status_id', $status);
        }

        //$tickets = $tickets->paginate(paginationNumber());

        return view('callcenters.views.dashboard.index')->with([
            'tickets' => $tickets,
            'totalactivetickets' => $totalactivetickets,
            'totalclosedtickets' => $totalclosedtickets,
            'replyrecent' => $replyrecent,
            'selfassigncount' => $selfassigncount,
            'replyrecent' => $replyrecent,
            'totaltickets' => $totaltickets,
            'recentticketcount' => $recentticketcount,
            'myassignedticketcount' => $myassignedticketcount,
            'myclosedticketcount' => $myclosedticketcount,
            'suspendedticketcount' => $suspendedticketcount,
            'suspendticketcount' => $suspendticketcount,
        ]);

    }

    public function create()
    {

        if(setting('customer_ticket') == 'true'){

                $user = Auth::user();
                $tickets = $user->tikets;

                $categories = TicketCategorie::available()->get();
                $categories->prepend('' , '');
                $categories = $categories->pluck('title','id');

               // customers restrict to create tickets based on allowed to create.
               if(setting('restrict_to_create_ticket') == 'true' && setting('maximum_allow_tickets') > 0){

                   if(empty($tickets)){
                       if($tickets->latest('created_at')){
                           $tttt = $tic->latest('created_at')->first();
                           if($tttt->created_at->timezone(setting('default_timezone'))->format('Y-m-d') == now()->timezone(setting('default_timezone'))->format('Y-m-d')){
                               if($tttt->created_at->timezone(setting('default_timezone'))->subHour(setting('MAXIMUM_ALLOW_HOURS'))->format('H:i:s') <= $tttt->created_at->timezone(setting('default_timezone'))->format('H:i:s')){
                                   $ticketscount = Ticket::where('cust_id', Auth::guard('customers')->user()->id)->whereDate('created_at', Carbon::today())->count();
                                   if($ticketscount < setting('MAXIMUM_ALLOW_TICKETS')){
                                       return view('user.ticket.create', compact('categories', 'title', 'footertext'))->with($data);
                                   }else{
                                       return redirect()->back()->with('error','You have reached maximum allow tickets to create.');
                                   }
                               }
                           }else{
                               return view('callcenters.views.tickets.create')->with([
                                   'categories' => $categories
                               ]);
                           }
                       }
                   }else{
                       return view('callcenters.views.tickets.create')->with([
                           'categories' => $categories
                       ]);
                   }

            }else{
                return redirect()->back()->with('error','You cannot have access for this ticket create.');
            }

        }else{
            return redirect()->route('support.tickets');
        }


}

    public function store(Request $request)
    {

//        dd($request);
        $user = Auth::user();

        $status = TicketStatus::slug('new');
        $category = TicketCategorie::find($request->categorie);
        $ticket = new Ticket;
        $ticket->subject = $request->subject;
        $ticket->uid = $this->generate_uid('tickets');
        $ticket->cust_id = $user->id;
        $ticket->user_id = $user->id;
        $ticket->priority_id = $category->priority_id;
        $ticket->status_id = $status->id;
        $ticket->message = $request->description;
        $ticket->category_id = $request->categorie;
        $ticket->created_at = Carbon::now()->toDateTimeString();
        $ticket->updated_at = Carbon::now()->toDateTimeString();
        $ticket->toassignuser_id = 1;
        $ticket->myassignuser_id = 1;
        $ticket->ticket_id = setting('customer_ticketid') . '-' . $ticket->id;
        $ticket->save();


        if (setting('auto_overdue_ticket') == 'true') {
            $ticket->auto_overdue_ticket = null;
        } else {
            if (setting('auto_overdue_ticket_time') == '0') {
                $ticket->auto_overdue_ticket = null;
            } else {
                    if ($ticket->status->slug == 'closed') {
                        $ticket->auto_overdue_ticket = null;
                    } else {
                        $ticket->auto_overdue_ticket = now()->addDays(setting('AUTO_OVERDUE_TICKET_TIME'));
                    }
            }
        }


//        $history = new TicketHistory;
//        $history->ticket_id = $ticket->id;
//
//        $output = '<div class="d-flex align-items-center">
//            <div class="mt-0">
//                <p class="mb-0 fs-12 mb-1">Status
//            ';
//                if($ticket->notes->isEmpty()){
//                    if($ticket->overduestatus != null){
//                        $output .= '
//                        <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
//                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
//                        ';
//                    }else{
//                        $output .= '
//                        <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
//                        ';
//                    }
//
//                }else{
//                    if($ticket->overduestatus != null){
//                        $output .= '
//                        <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
//                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
//                        <span class="text-warning font-weight-semibold mx-1">Note</span>
//                        ';
//                    }else{
//                        $output .= '
//                        <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
//                        <span class="text-warning font-weight-semibold mx-1">Note</span>
//                        ';
//                    }
//                }
//
//        $output .= '
//            <p class="mb-0 fs-17 font-weight-semibold text-dark">'.$ticket->cust->username.'<span class="fs-11 mx-1 text-muted">(Created)</span></p>
//        </div>
//        <div class="ms-auto">
//        <span class="float-end badge badge-danger-light">
//            <span class="fs-11 font-weight-semibold">'.$ticket->cust->userType.'</span>
//        </span>
//        </div> </div>';
//        $history->ticketactions = $output;
//        $history->save();

//        // Create a New ticket reply
//        $notificationcat = $ticket->category->groupscategoryc()->get();
//        $icc = array();
//        if ($notificationcat->isNotEmpty()) {
//            foreach ($notificationcat as $igc) {
//
//                foreach ($igc->groupsc->groupsuser()->get() as $user) {
//                    $icc[] .= $user->users_id;
//                }
//            }
//
//            if (!$icc) {
//                $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
//                foreach ($admins as $admin) {
//                    $admin->notify(new TicketCreateNotifications($ticket));
//                }
//
//            } else {
//
//                $user = User::whereIn('id', $icc)->get();
//                foreach ($user as $users) {
//                    $users->notify(new TicketCreateNotifications($ticket));
//                }
//                $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
//                foreach ($admins as $admin) {
//                    if($admin->getRoleNames()[0] == 'superadmin'){
//                        $admin->notify(new TicketCreateNotifications($ticket));
//                    }
//                }
//
//            }
//        } else {
//            $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
//            foreach ($admins as $admin) {
//                $admin->notify(new TicketCreateNotifications($ticket));
//            }
//        }

        $request->session()->put('customerticket', $user->id);
//
//        $ticketData = [
//            'ticket_username' => $ticket->cust->username,
//            'ticket_id' => $ticket->ticket_id,
//            'ticket_title' => $ticket->subject,
//            'ticket_description' => $ticket->message,
//            'ticket_status' => $ticket->status,
//            'ticket_customer_url' => route('loadmore.load_data', $ticket->ticket_id),
//            'ticket_admin_url' => url('/admin/ticket-view/' . $ticket->ticket_id),
//        ];
//
//        try {
//            // Create a New ticket reply
//            $notificationcat = $ticket->category->groupscategoryc()->get();
//            $icc = array();
//            if ($notificationcat->isNotEmpty()) {
//
//                foreach ($notificationcat as $igc) {
//                    foreach ($igc->groupsc->groupsuser()->get() as $user) {
//                        $icc[] .= $user->users_id;
//                    }
//                }
//
//                if (!$icc) {
//                    $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
//                    foreach ($admins as $admin) {
//                        if($admin->usetting->emailnotifyon == 1){
//                            NotificationMails::dispatch($ticket)->onQueue('notifications');
//                        }
//                    }
//
//                } else {
//
//                    $user = User::whereIn('id', $icc)->get();
//                    foreach ($user as $users) {
//                        if($users->usetting->emailnotifyon == 1){
//                            NotificationMails::dispatch($ticket)->onQueue('notifications');
//                        }
//                    }
//                    $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
//
//                    foreach ($admins as $admin) {
//                        if($admin->getRoleNames()[0] == 'superadmin' && $admin->usetting->emailnotifyon == 1){
//                            NotificationMails::dispatch($ticket)->onQueue('notifications');
//                        }
//                    }
//
//                }
//            } else {
//                $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
//                foreach ($admins as $admin) {
//                    if($admin->usetting->emailnotifyon == 1){
//                        Mail::to($admin->email)
//                            ->send(new mailmailablesend('admin_send_email_ticket_created', $ticketData));
//                    }
//                }
//            }
//
//            Mail::to($ticket->cust->email)
//                ->send(new mailmailablesend('customer_send_ticket_created', $ticketData));
//
//            Mail::to($ccemailsend->ccemails)
//                ->send(new mailmailablesend('customer_send_ticket_created', $ticketData));
//
//        } catch (\Exception$e) {
//            return response()->json(['success' => lang('A ticket has been opened with the ticket ID', 'alerts') . $ticket->ticket_id], 200);
//        }

        return response()->json(['success' => "Se ha abierto un ticket con el ID del ticket" . $ticket->ticket_id], 200);

    }

    public function view($uid)
    {
        $ticket = Ticket::uid($uid);
        dd($ticket);
        $comments = $ticket->comments()->with('ticket')->paginate(5);
        $category = $ticket->category;

        // customers restrict to reply for the ticket.
        $commentsNull = $ticket->comments()->get();
        if(setting('RESTRICT_TO_REPLY_TICKET') == 'on' && $commentsNull->all() != null && setting('MAXIMUM_ALLOW_REPLIES') > 0){
            $latestone = $ticket->comments()->latest('created_at')->first();

            if($latestone->user_id == null){
                $ticComment = $ticket->comments()->where('cust_id', Auth::guard('customers')->user()->id)->latest('created_at')->first();
                if($ticComment != null){
                    if($ticComment->created_at->timezone(setting('default_timezone'))->format('Y-m-d') == now()->timezone(setting('default_timezone'))->format('Y-m-d')){

                        $star1 = $ticComment->created_at->timezone(setting('default_timezone'))->subHour(setting('REPLY_ALLOW_IN_HOURS'))->format('Y-m-d H:i:s');
                        $star2 = $ticComment->created_at->timezone(setting('default_timezone'))->format('Y-m-d H:i:s');
                        $star3 = $ticComment->created_at->timezone(setting('default_timezone'))->addHour(setting('REPLY_ALLOW_IN_HOURS'))->format('Y-m-d H:i:s');

                        if($star3 < now()->timezone(setting('default_timezone'))->format('Y-m-d H:i:s')){
                            if ($ticket->cust_id == Auth::guard('customers')->id()) {
                                $createdcount = '';
                                if (request()->ajax()) {
                                    $view = view('callcenters.views.tickets.viewdata', compact('comments', 'createdcount'))->render();
                                    return response()->json(['html' => $view]);
                                }

                                return view('callcenters.views.tickets.view', compact('ticket', 'category', 'comments', 'title', 'footertext', 'createdcount'))->with($data);
                            }else{
                                return back()->with('error', lang('Cannot Access This Ticket'));
                            }
                        }else{
                            $createdcount = $ticket->comments()->where('cust_id', Auth::guard('customers')->user()->id)->count();
                            if ($ticket->cust_id == Auth::guard('customers')->id()) {
                                if (request()->ajax()) {
                                    $view = view('callcenters.views.tickets.viewdata', compact('comments', 'createdcount'))->render();
                                    return response()->json(['html' => $view]);
                                }

                                return view('callcenters.views.tickets.view', compact('ticket', 'category', 'comments', 'title', 'footertext', 'createdcount'))->with($data);
                            }else{
                                return back()->with('error', lang('Cannot Access This Ticket'));
                            }
                        }
                    }else{
                        if ($ticket->cust_id == Auth::guard('customers')->id()) {
                            $createdcount = '';
                            if (request()->ajax()) {
                                $view = view('callcenters.views.tickets.viewdata', compact('comments', 'createdcount'))->render();
                                return response()->json(['html' => $view]);
                            }

                            return view('callcenters.views.tickets.view', compact('ticket', 'category', 'comments', 'title', 'footertext', 'createdcount'))->with($data);
                        }else{
                            return back()->with('error', lang('Cannot Access This Ticket'));
                        }
                    }
                }
            }else{
                if ($ticket->cust_id == Auth::guard('customers')->id()) {
                    $createdcount = '';
                    if (request()->ajax()) {
                        $view = view('callcenters.views.tickets.viewdata', compact('comments', 'createdcount'))->render();
                        return response()->json(['html' => $view]);
                    }

                    return view('callcenters.views.tickets.view', compact('ticket', 'category', 'comments', 'title', 'footertext', 'createdcount'))->with($data);
                }else{
                    return back()->with('error', lang('Cannot Access This Ticket'));
                }
            }
        }else{
            if ($ticket->cust_id == Auth::guard('customers')->id()) {
                $createdcount = '';
                if (request()->ajax()) {
                    $view = view('callcenters.views.tickets.viewdata', compact('comments', 'createdcount'))->render();
                    return response()->json(['html' => $view]);
                }

                return view('callcenters.views.tickets.view', compact('ticket', 'category', 'comments', 'title', 'footertext', 'createdcount'))->with($data);
            }else{
                return back()->with('error', lang('Cannot Access This Ticket'));
            }
        }

    }


    public function close($uid)
    {

        $status = TicketStatus::slug('re-open');
        $ticket = Ticket::uid($uid);

        $ticket->status = $status->id;
        $ticket->replystatus = null;
        $ticket->closedby_user = null;
        $ticket->update();

        $history = new TicketHistory();
        $history->ticket_id = $ticket->id;

        $output = '<div class="d-flex align-items-center">
            <div class="mt-0">
                <p class="mb-0 fs-12 mb-1">Status
            ';
        if($ticket->notes->isEmpty()){
            if($ticket->overduestatus != null){
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">'.$ticket->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                ';
            }else{
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">'.$ticket->status.'</span>
                ';
            }

        }else{
            if($ticket->overduestatus != null){
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">'.$ticket->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }else{
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">'.$ticket->status.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }
        }

        $output .= '
            <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::guard('customers')->user()->username.'<span class="fs-11 mx-1 text-muted">(Re-opened)</span></p>
        </div>
        <div class="ms-auto">
        <span class="float-end badge badge-danger-light">
            <span class="fs-11 font-weight-semibold">'.Auth::guard('customers')->user()->userType.'</span>
        </span>
        </div>

        </div>
        ';
        $history->ticketactions = $output;
        $history->save();

        // // Create a New ticket reply
        // if($ticket->category)
        // {
        //     $notificationcat = $ticket->category->groupscategoryc()->get();
        //     $icc = array();
        //     if ($notificationcat->isNotEmpty()) {

        //         foreach ($notificationcat as $igc) {

        //             foreach ($igc->groupsc->groupsuser()->get() as $user) {
        //                 $icc[] .= $user->users_id;
        //             }
        //         }

        //         if (!$icc) {
        //             $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
        //             foreach ($admins as $admin) {
        //                 $admin->notify(new TicketCreateNotifications($ticket));
        //             }

        //         } else {

        //             $user = User::whereIn('id', $icc)->get();
        //             foreach ($user as $users) {
        //                 $users->notify(new TicketCreateNotifications($ticket));
        //             }
        //             $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
        //             foreach ($admins as $admin) {
        //                 $admin->notify(new TicketCreateNotifications($ticket));
        //             }

        //         }
        //     } else {
        //         $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
        //         foreach ($admins as $admin) {
        //             $admin->notify(new TicketCreateNotifications($ticket));
        //         }
        //     }
        // }
        // // Notification category Empty
        // if(!$ticket->category)
        // {
        //     $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
        //     foreach($admins as $admin){
        //         if($admin->getRoleNames()[0] != 'superadmin' && $admin->usetting->emailnotifyon == 1){
        //             $admin->notify(new TicketCreateNotifications($ticket));
        //         }
        //     }

        // }
//
//        $ccemailsend = CCMAILS::where('ticket_id', $ticket->id)->first();

        $ticketData = [
            'ticket_username' => $ticket->cust->username,
            'ticket_id' => $ticket->ticket_id,
            'ticket_title' => $ticket->subject,
            'ticket_description' => $ticket->message,
            'ticket_status' => $ticket->status,
            'ticket_customer_url' => route('loadmore.load_data', $ticket->ticket_id),
            'ticket_admin_url' => url('/admin/ticket-view/' . $ticket->ticket_id),
        ];
//
//        try {
//
//            if($ticket->category)
//            {
//
//                $notificationcatss = $ticket->category->groupscategoryc()->get();
//                $icc = array();
//                if ($notificationcatss->isNotEmpty()) {
//
//                    foreach ($notificationcatss as $igc) {
//
//                        foreach ($igc->groupsc->groupsuser()->get() as $user) {
//                            $icc[] .= $user->users_id;
//                        }
//                    }
//
//                    if (!$icc) {
//                        $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
//                        foreach ($admins as $admin) {
//                            $admin->notify(new TicketCreateNotifications($ticket));
//                            if($admin->usetting->emailnotifyon == 1){
//                                Mail::to($admin->email)
//                                    ->send(new mailmailablesend('admin_sendemail_whenticketreopen', $ticketData));
//                            }
//                        }
//
//                    } else {
//
//                        if($ticket->myassignuser){
//                            $assignee = $ticket->ticketassignmutliples;
//                            foreach($assignee as $assignees){
//                                $user = User::where('id',$assignees->toassignuser_id)->get();
//                                foreach($user as $users){
//                                    if($users->id == $assignees->toassignuser_id && $users->getRoleNames()[0] != 'superadmin'){
//                                        $users->notify(new TicketCreateNotifications($ticket));
//                                        if($users->usetting->emailnotifyon == 1){
//                                            Mail::to($users->email)
//                                            ->send( new mailmailablesend( 'admin_sendemail_whenticketreopen', $ticketData ) );
//                                        }
//                                    }
//                                }
//                            }
//                        }else if ($ticket->toassignuser_id) {
//                            $self = User::findOrFail($ticket->toassignuser_id);
//                            if($self->getRoleNames()[0] != 'superadmin'){
//                                $self->notify(new TicketCreateNotifications($ticket));
//                                if($self->usetting->emailnotifyon == 1){
//                                    Mail::to($self->email)
//                                    ->send( new mailmailablesend( 'admin_sendemail_whenticketreopen', $ticketData ) );
//                                }
//                            }
//                        }else if($icc ){
//                            $user = User::whereIn('id', $icc)->get();
//                            foreach($user as $users){
//                                $users->notify(new TicketCreateNotifications($ticket));
//                                if($users->usetting->emailnotifyon == 1){
//                                    Mail::to($users->email)
//                                    ->send( new mailmailablesend( 'admin_sendemail_whenticketreopen', $ticketData ) );
//                                }
//                            }
//                        }else {
//                            $users = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
//                            foreach($users as $user){
//                                if($user->getRoleNames()[0] != 'superadmin'){
//                                    $user->notify(new TicketCreateNotifications($ticket));
//                                    if($user->usetting->emailnotifyon == 1){
//                                        Mail::to($user->email)
//                                        ->send( new mailmailablesend( 'admin_sendemail_whenticketreopen', $ticketData ) );
//                                    }
//                                }
//                            }
//                        }
//
//                    }
//                } else {
//                    if($ticket->myassignuser){
//                        $assignee = $ticket->ticketassignmutliples;
//                        foreach($assignee as $assignees){
//                            $user = User::where('id',$assignees->toassignuser_id)->get();
//                            foreach($user as $users){
//                                if($users->id == $assignees->toassignuser_id && $users->getRoleNames()[0] != 'superadmin'){
//                                    $users->notify(new TicketCreateNotifications($ticket));
//                                    if($users->usetting->emailnotifyon == 1){
//                                        Mail::to($users->email)
//                                        ->send( new mailmailablesend( 'admin_sendemail_whenticketreopen', $ticketData ) );
//                                    }
//                                }
//                            }
//                        }
//                    } else if ($ticket->toassignuser_id) {
//                        $self = User::findOrFail($ticket->toassignuser_id);
//                        if($self->getRoleNames()[0] != 'superadmin'){
//                            $self->notify(new TicketCreateNotifications($ticket));
//                            if($self->usetting->emailnotifyon == 1){
//                                Mail::to($self->email)
//                                ->send( new mailmailablesend( 'admin_sendemail_whenticketreopen', $ticketData ) );
//                            }
//                        }
//                    } else {
//
//                        $users = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
//                        foreach($users as $user){
//                            if($user->getRoleNames()[0] != 'superadmin'){
//                                $user->notify(new TicketCreateNotifications($ticket));
//                                if($user->usetting->emailnotifyon == 1){
//                                    Mail::to($user->email)
//                                    ->send( new mailmailablesend( 'admin_sendemail_whenticketreopen', $ticketData ) );
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//
//            $admins = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
//            foreach($admins as $admin){
//                if($admin->getRoleNames()[0] == 'superadmin'){
//                    $admin->notify(new TicketCreateNotifications($ticket));
//                    if($admin->usetting->emailnotifyon == 1){
//                        Mail::to($admin->email)
//                        ->send( new mailmailablesend( 'admin_sendemail_whenticketreopen', $ticketData ) );
//                    }
//                }
//            }
//
//            Mail::to($ticket->cust->email)
//                ->send(new mailmailablesend('customer_send_ticket_reopen', $ticketData));
//
//            Mail::to($ccemailsend->ccemails)
//                ->send(new mailmailablesend('customer_send_ticket_reopen', $ticketData));

//        } catch (\Exception$e) {
//            return redirect()->back()->with("success", lang('The ticket has been successfully reopened.', 'alerts'));
//        }

        return redirect()->route('support.tikects');

    }


    public function destroy($id)
    {

        $ticket = Ticket::findOrFail($id);
        $comment = $ticket->comments()->get();

        if (count($comment) > 0) {
            $media = $ticket->getMedia('ticket');
            foreach ($media as $media) {
                $media->delete();

            }
            $medias = $ticket->comments()->firstOrFail()->getMedia('comments');

            foreach ($medias as $mediass) {

                $mediass->delete();

            }
            $comment->each->delete();
            $ticket->delete();

            $history = new tickethistory();
            $history->ticket_id = $ticket->id;

            $output = '<div class="d-flex align-items-center">
                <div class="mt-0">
                    <p class="mb-0 fs-12 mb-1">Status
                ';
            if($ticket->notes->isEmpty()){
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">'.$ticket->status.'</span>
                    ';
                }

            }else{
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }
            }

            $output .= '
                <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::guard('customers')->user()->username.'<span class="fs-11 mx-1 text-muted">(Ticket Deleted)</span></p>
            </div>
            <div class="ms-auto">
            <span class="float-end badge badge-danger-light">
                <span class="fs-11 font-weight-semibold">'.Auth::guard('customers')->user()->userType.'</span>
            </span>
            </div>

            </div>
            ';
            $history->ticketactions = $output;
            $history->save();

            return response()->json(['success' => lang('The ticket was successfully deleted.', 'alerts')]);
        } else {

            $media = $ticket->getMedia('ticket');

            foreach ($media as $media) {

                $media->delete();

            }
            $ticket->delete();

            $history = new tickethistory();
            $history->ticket_id = $ticket->id;

            $output = '<div class="d-flex align-items-center">
                <div class="mt-0">
                    <p class="mb-0 fs-12 mb-1">Status
                ';
            if($ticket->notes->isEmpty()){
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">'.$ticket->status.'</span>
                    ';
                }

            }else{
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }
            }

            $output .= '
                <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::guard('customers')->user()->username.'<span class="fs-11 mx-1 text-muted">(Ticket Deleted)</span></p>
            </div>
            <div class="ms-auto">
            <span class="float-end badge badge-danger-light">
                <span class="fs-11 font-weight-semibold">'.Auth::guard('customers')->user()->userType.'</span>
            </span>
            </div>

            </div>
            ';
            $history->ticketactions = $output;
            $history->save();


            return response()->json(['success' => lang('The ticket was successfully deleted.', 'alerts')]);

        }
    }

    public function ticketmassdestroy(Request $request)
    {
        $student_id_array = $request->input('id');

        $tickets = Ticket::whereIn('id', $student_id_array)->get();

        foreach ($tickets as $ticket) {
            $comment = $ticket->comments()->get();

            if (count($comment) > 0) {
                $media = $ticket->getMedia('ticket');

                foreach ($media as $media) {

                    $media->delete();

                }
                $medias = $ticket->comments()->firstOrFail()->getMedia('comments');

                foreach ($medias as $mediass) {

                    $mediass->delete();

                }
                $comment->each->delete();
                $ticket->delete();

                $history = new tickethistory();
                $history->ticket_id = $ticket->id;

                $output = '<div class="d-flex align-items-center">
                    <div class="mt-0">
                        <p class="mb-0 fs-12 mb-1">Status
                    ';
                if($ticket->notes->isEmpty()){
                    if($ticket->overduestatus != null){
                        $output .= '
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                        ';
                    }else{
                        $output .= '
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                        ';
                    }

                }else{
                    if($ticket->overduestatus != null){
                        $output .= '
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                        <span class="text-warning font-weight-semibold mx-1">Note</span>
                        ';
                    }else{
                        $output .= '
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                        <span class="text-warning font-weight-semibold mx-1">Note</span>
                        ';
                    }
                }

                $output .= '
                    <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::guard('customers')->user()->username.'<span class="fs-11 mx-1 text-muted">(Ticket Deleted)</span></p>
                </div>
                <div class="ms-auto">
                <span class="float-end badge badge-primary-light">
                    <span class="fs-11 font-weight-semibold">'.Auth::guard('customers')->user()->userType.'</span>
                </span>
                </div>

                </div>
                ';
                $history->ticketactions = $output;
                $history->save();
                foreach($ticket->historys as $deletetickethistory)
                {
                    $deletetickethistory->delete();
                }

                return response()->json(['success' => lang('The ticket was successfully deleted.', 'alerts')]);
            } else {

                $media = $ticket->getMedia('ticket');

                foreach ($media as $media) {

                    $media->delete();

                }
                $ticket->delete();

                $history = new tickethistory();
                $history->ticket_id = $ticket->id;

                $output = '<div class="d-flex align-items-center">
                    <div class="mt-0">
                        <p class="mb-0 fs-12 mb-1">Status
                    ';
                if($ticket->notes->isEmpty()){
                    if($ticket->overduestatus != null){
                        $output .= '
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                        ';
                    }else{
                        $output .= '
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                        ';
                    }

                }else{
                    if($ticket->overduestatus != null){
                        $output .= '
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                        <span class="text-warning font-weight-semibold mx-1">Note</span>
                        ';
                    }else{
                        $output .= '
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                        <span class="text-warning font-weight-semibold mx-1">Note</span>
                        ';
                    }
                }

                $output .= '
                    <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::guard('customers')->user()->username.'<span class="fs-11 mx-1 text-muted">(Ticket Deleted)</span></p>
                </div>
                <div class="ms-auto">
                <span class="float-end badge badge-primary-light">
                    <span class="fs-11 font-weight-semibold">'.Auth::guard('customers')->user()->userType.'</span>
                </span>
                </div>

                </div>
                ';
                $history->ticketactions = $output;
                $history->save();
                foreach($ticket->historys as $deletetickethistory)
                {
                    $deletetickethistory->delete();
                }
            }
        }
        return response()->json(['success' => lang('The ticket was successfully deleted.', 'alerts')]);

    }



}

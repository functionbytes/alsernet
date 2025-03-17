<?php

namespace App\Http\Controllers\Callcenters\Tickets;


use App\Mail\Supports\Tickets\NotificationCustomerReplayMails;
use App\Mail\Supports\Tickets\NotificatioCustomernMails;
use App\Models\Ticket\TicketCanned;
use App\Notifications\TicketCreateNotifications;
use App\Mail\Supports\Tickets\Note\NoteMails;
use App\Models\Ticket\TicketCategorie;
use App\Models\Ticket\TicketHistory;
use App\Http\Controllers\Controller;
use App\Models\Ticket\TicketComment;
use App\Models\Ticket\TicketStatus;
use App\Models\Ticket\TicketNote;
use App\Models\Ticket\Ticket;
use Illuminate\Http\Request;
use App\Models\Group\Group;
use App\Models\User;
use Carbon\Carbon;
use Auth;

class TicketsController extends Controller
{
    public function index(Request $request)
    {

        $user = app('callcenters');
        $searchKey = null ?? $request->search;
        $status = null ?? $request->status;

        $tickets = Ticket::descending();

        if ($searchKey) {
            $tickets = $tickets->where('title', 'like', '%' . $searchKey . '%');
        }

        if ($request->status != null) {
            $tickets = $tickets->where('status_id', $status);
        }

        $tickets = $tickets->paginate(paginationNumber());

        return view('callcenters.views.tickets.index')->with([
            'tickets' => $tickets,
        ]);
    }


    public function previous(Request $request, $uid)
    {

        $user = User::uid($uid);
        $searchKey = null ?? $request->search;
        $status = null ?? $request->status;

        $tickets = $user->tikets();

        if ($searchKey) {
            $tickets = $tickets->where('title', 'like', '%' . $searchKey . '%');
        }

        if ($request->status != null) {
            $tickets = $tickets->where('status_id', $status);
        }

        $tickets = $tickets->paginate(paginationNumber());

        return view('callcenters.views.tickets.previous')->with([
            'tickets' => $tickets,
        ]);
    }
    public function store(Request $request)
    {

        //        dd($request);
        $user =  Auth::user();

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
        //                    $icc[] .= $user->user_id;
        //                }
        //            }
        //
        //            if (!$icc) {
        //                $admins = User::leftJoin('groups_users', 'groups_users.user_id', 'users.id')->whereNull('groups_users.group_id')->whereNull('groups_users.user_id')->get();
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
        //                $admins = User::leftJoin('groups_users', 'groups_users.user_id', 'users.id')->whereNull('groups_users.group_id')->whereNull('groups_users.user_id')->get();
        //                foreach ($admins as $admin) {
        //                    if($admin->getRoleNames()[0] == 'superadmin'){
        //                        $admin->notify(new TicketCreateNotifications($ticket));
        //                    }
        //                }
        //
        //            }
        //        } else {
        //            $admins = User::leftJoin('groups_users', 'groups_users.user_id', 'users.id')->whereNull('groups_users.group_id')->whereNull('groups_users.user_id')->get();
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
        //                        $icc[] .= $user->user_id;
        //                    }
        //                }
        //
        //                if (!$icc) {
        //                    $admins = User::leftJoin('groups_users', 'groups_users.user_id', 'users.id')->whereNull('groups_users.group_id')->whereNull('groups_users.user_id')->get();
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
        //                    $admins = User::leftJoin('groups_users', 'groups_users.user_id', 'users.id')->whereNull('groups_users.group_id')->whereNull('groups_users.user_id')->get();
        //
        //                    foreach ($admins as $admin) {
        //                        if($admin->getRoleNames()[0] == 'superadmin' && $admin->usetting->emailnotifyon == 1){
        //                            NotificationMails::dispatch($ticket)->onQueue('notifications');
        //                        }
        //                    }
        //
        //                }
        //            } else {
        //                $admins = User::leftJoin('groups_users', 'groups_users.user_id', 'users.id')->whereNull('groups_users.group_id')->whereNull('groups_users.user_id')->get();
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
        $user =  Auth::user();
        $ticket = Ticket::uid($uid);

        $category = $ticket->category;
        $comments = $ticket->comments()->latest()->get();
        $notes =  $ticket->notes()->latest()->get();

        $simillars = Ticket::where('cust_id', $ticket->cust->id)->count();

        $status = TicketStatus::available()->get()->pluck('title', 'id');

        $canneds = TicketCanned::available()->get();
        $canneds->prepend('', '');
        $canneds = $canneds->pluck('title', 'id');

        $cannedsjson = TicketCanned::details($ticket->uid);
        $allowreply = false;

        $finalassigne = [];
        $assigns = $ticket->assigns;

        foreach ($assigns as $assign) {
            array_push($finalassigne, $assign->toassignuser_id);
        }

        if ($user->role == 'manager' || in_array($user->id, $finalassigne) || $ticket->selfassignuser_id == $user->id) {
            $allowreply = true;
        } else {

            $category = $ticket->category;

            if ($category!=null) {

                $categories = TicketCategorie::with('groupscategoryc')->get();

                foreach ($categories as $individual) {
                    if ($individual->id == $ticket->category->id) {

                        foreach ($individual->groupscategoryc as $individualGroupc) {

                            $groupId = $individualGroupc->group_id;
                            $groupUser = Group::with('user')->get();

                            foreach ($groupUser as $individualGroup) {
                                foreach ($individualGroup->user as $groups) {

                                    if ($groups->group_id == $groupId) {

                                        if (($groups->user_id == $user->id)) {
                                            $allowreply = true;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {

                $admins = User::leftJoin('groups_users', 'groups_users.user_id', 'users.id')->whereNull('groups_users.group_id')->whereNull('groups_users.user_id')->get();

                foreach ($admins as $admin) {
                    if ($admin->id == $user->id) {
                        $allowreply = true;
                    }
                }
            }


        }

        if (request()->ajax()) {

            return view('callcenters.views.tickets.view')->with([
                'ticket' => $ticket,
                'simillars' => $simillars,
                'notes' => $notes,
                'category' => $category,
                'comments' => $comments,
                'allowreply' => $allowreply,
                'cannedsjson' => $cannedsjson,
                'canneds' => $canneds,
                'status' => $status,
             ]);

        }

        return view('callcenters.views.tickets.view')->with([
            'ticket' => $ticket,
            'simillars' => $simillars,
            'category' => $category,
            'notes' => $notes,
            'comments' => $comments,
            'allowreply' => $allowreply,
            'cannedsjson' => $cannedsjson,
            'canneds' => $canneds,
            'status' => $status,
        ]);


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
        if ($ticket->notes->isEmpty()) {
            if ($ticket->overduestatus != null) {
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">' . $ticket->status . '</span>
                <span class="text-danger font-weight-semibold mx-1">' . $ticket->overduestatus . '</span>
                ';
            } else {
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">' . $ticket->status . '</span>
                ';
            }
        } else {
            if ($ticket->overduestatus != null) {
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">' . $ticket->status . '</span>
                <span class="text-danger font-weight-semibold mx-1">' . $ticket->overduestatus . '</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            } else {
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">' . $ticket->status . '</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }
        }

        $output .= '
            <p class="mb-0 fs-17 font-weight-semibold text-dark">' . Auth::guard('customers')->user()->username . '<span class="fs-11 mx-1 text-muted">(Re-opened)</span></p>
        </div>
        <div class="ms-auto">
        <span class="float-end badge badge-danger-light">
            <span class="fs-11 font-weight-semibold">' . Auth::guard('customers')->user()->userType . '</span>
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
        //                 $icc[] .= $user->user_id;
        //             }
        //         }

        //         if (!$icc) {
        //             $admins = User::leftJoin('groups_users', 'groups_users.user_id', 'users.id')->whereNull('groups_users.group_id')->whereNull('groups_users.user_id')->get();
        //             foreach ($admins as $admin) {
        //                 $admin->notify(new TicketCreateNotifications($ticket));
        //             }

        //         } else {

        //             $user = User::whereIn('id', $icc)->get();
        //             foreach ($user as $users) {
        //                 $users->notify(new TicketCreateNotifications($ticket));
        //             }
        //             $admins = User::leftJoin('groups_users', 'groups_users.user_id', 'users.id')->whereNull('groups_users.group_id')->whereNull('groups_users.user_id')->get();
        //             foreach ($admins as $admin) {
        //                 $admin->notify(new TicketCreateNotifications($ticket));
        //             }

        //         }
        //     } else {
        //         $admins = User::leftJoin('groups_users', 'groups_users.user_id', 'users.id')->whereNull('groups_users.group_id')->whereNull('groups_users.user_id')->get();
        //         foreach ($admins as $admin) {
        //             $admin->notify(new TicketCreateNotifications($ticket));
        //         }
        //     }
        // }
        // // Notification category Empty
        // if(!$ticket->category)
        // {
        //     $admins = User::leftJoin('groups_users', 'groups_users.user_id', 'users.id')->whereNull('groups_users.group_id')->whereNull('groups_users.user_id')->get();
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
        //                            $icc[] .= $user->user_id;
        //                        }
        //                    }
        //
        //                    if (!$icc) {
        //                        $admins = User::leftJoin('groups_users', 'groups_users.user_id', 'users.id')->whereNull('groups_users.group_id')->whereNull('groups_users.user_id')->get();
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
        //                            $assignee = $ticket->assigns;
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
        //                        }else if ($ticket->selfassignuser_id) {
        //                            $self = User::findOrFail($ticket->selfassignuser_id);
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
        //                            $users = User::leftJoin('groups_users', 'groups_users.user_id', 'users.id')->whereNull('groups_users.group_id')->whereNull('groups_users.user_id')->get();
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
        //                        $assignee = $ticket->assigns;
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
        //                    } else if ($ticket->selfassignuser_id) {
        //                        $self = User::findOrFail($ticket->selfassignuser_id);
        //                        if($self->getRoleNames()[0] != 'superadmin'){
        //                            $self->notify(new TicketCreateNotifications($ticket));
        //                            if($self->usetting->emailnotifyon == 1){
        //                                Mail::to($self->email)
        //                                ->send( new mailmailablesend( 'admin_sendemail_whenticketreopen', $ticketData ) );
        //                            }
        //                        }
        //                    } else {
        //
        //                        $users = User::leftJoin('groups_users', 'groups_users.user_id', 'users.id')->whereNull('groups_users.group_id')->whereNull('groups_users.user_id')->get();
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
        //            $admins = User::leftJoin('groups_users','groups_users.user_id','users.id')->whereNull('groups_users.group_id')->whereNull('groups_users.user_id')->get();
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
            if ($ticket->notes->isEmpty()) {
                if ($ticket->overduestatus != null) {
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">' . $ticket->status . '</span>
                    <span class="text-danger font-weight-semibold mx-1">' . $ticket->overduestatus . '</span>
                    ';
                } else {
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">' . $ticket->status . '</span>
                    ';
                }
            } else {
                if ($ticket->overduestatus != null) {
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">' . $ticket->status . '</span>
                    <span class="text-danger font-weight-semibold mx-1">' . $ticket->overduestatus . '</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                } else {
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">' . $ticket->status . '</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }
            }

            $output .= '
                <p class="mb-0 fs-17 font-weight-semibold text-dark">' . Auth::guard('customers')->user()->username . '<span class="fs-11 mx-1 text-muted">(Ticket Deleted)</span></p>
            </div>
            <div class="ms-auto">
            <span class="float-end badge badge-danger-light">
                <span class="fs-11 font-weight-semibold">' . Auth::guard('customers')->user()->userType . '</span>
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
            if ($ticket->notes->isEmpty()) {
                if ($ticket->overduestatus != null) {
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">' . $ticket->status . '</span>
                    <span class="text-danger font-weight-semibold mx-1">' . $ticket->overduestatus . '</span>
                    ';
                } else {
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">' . $ticket->status . '</span>
                    ';
                }
            } else {
                if ($ticket->overduestatus != null) {
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">' . $ticket->status . '</span>
                    <span class="text-danger font-weight-semibold mx-1">' . $ticket->overduestatus . '</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                } else {
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">' . $ticket->status . '</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }
            }

            $output .= '
                <p class="mb-0 fs-17 font-weight-semibold text-dark">' . Auth::guard('customers')->user()->username . '<span class="fs-11 mx-1 text-muted">(Ticket Deleted)</span></p>
            </div>
            <div class="ms-auto">
            <span class="float-end badge badge-danger-light">
                <span class="fs-11 font-weight-semibold">' . Auth::guard('customers')->user()->userType . '</span>
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
                if ($ticket->notes->isEmpty()) {
                    if ($ticket->overduestatus != null) {
                        $output .= '
                        <span class="text-danger font-weight-semibold mx-1">' . $ticket->status . '</span>
                        <span class="text-danger font-weight-semibold mx-1">' . $ticket->overduestatus . '</span>
                        ';
                    } else {
                        $output .= '
                        <span class="text-danger font-weight-semibold mx-1">' . $ticket->status . '</span>
                        ';
                    }
                } else {
                    if ($ticket->overduestatus != null) {
                        $output .= '
                        <span class="text-danger font-weight-semibold mx-1">' . $ticket->status . '</span>
                        <span class="text-danger font-weight-semibold mx-1">' . $ticket->overduestatus . '</span>
                        <span class="text-warning font-weight-semibold mx-1">Note</span>
                        ';
                    } else {
                        $output .= '
                        <span class="text-danger font-weight-semibold mx-1">' . $ticket->status . '</span>
                        <span class="text-warning font-weight-semibold mx-1">Note</span>
                        ';
                    }
                }

                $output .= '
                    <p class="mb-0 fs-17 font-weight-semibold text-dark">' . Auth::guard('customers')->user()->username . '<span class="fs-11 mx-1 text-muted">(Ticket Deleted)</span></p>
                </div>
                <div class="ms-auto">
                <span class="float-end badge badge-primary-light">
                    <span class="fs-11 font-weight-semibold">' . Auth::guard('customers')->user()->userType . '</span>
                </span>
                </div>

                </div>
                ';
                $history->ticketactions = $output;
                $history->save();
                foreach ($ticket->historys as $deletetickethistory) {
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
                if ($ticket->notes->isEmpty()) {
                    if ($ticket->overduestatus != null) {
                        $output .= '
                        <span class="text-danger font-weight-semibold mx-1">' . $ticket->status . '</span>
                        <span class="text-danger font-weight-semibold mx-1">' . $ticket->overduestatus . '</span>
                        ';
                    } else {
                        $output .= '
                        <span class="text-danger font-weight-semibold mx-1">' . $ticket->status . '</span>
                        ';
                    }
                } else {
                    if ($ticket->overduestatus != null) {
                        $output .= '
                        <span class="text-danger font-weight-semibold mx-1">' . $ticket->status . '</span>
                        <span class="text-danger font-weight-semibold mx-1">' . $ticket->overduestatus . '</span>
                        <span class="text-warning font-weight-semibold mx-1">Note</span>
                        ';
                    } else {
                        $output .= '
                        <span class="text-danger font-weight-semibold mx-1">' . $ticket->status . '</span>
                        <span class="text-warning font-weight-semibold mx-1">Note</span>
                        ';
                    }
                }

                $output .= '
                    <p class="mb-0 fs-17 font-weight-semibold text-dark">' . Auth::guard('customers')->user()->username . '<span class="fs-11 mx-1 text-muted">(Ticket Deleted)</span></p>
                </div>
                <div class="ms-auto">
                <span class="float-end badge badge-primary-light">
                    <span class="fs-11 font-weight-semibold">' . Auth::guard('customers')->user()->userType . '</span>
                </span>
                </div>

                </div>
                ';
                $history->ticketactions = $output;
                $history->save();
                foreach ($ticket->historys as $deletetickethistory) {
                    $deletetickethistory->delete();
                }
            }
        }
        return response()->json(['success' => lang('The ticket was successfully deleted.', 'alerts')]);
    }
    public function notestore(Request $request){


        $ticket = Ticket::uid($request->uid);

        $note = new TicketNote();
        $note->ticket_id = $ticket->id;
        $note->user_id = Auth::user()->id;
        $note->notes = $request->notes;
        $note->save();

        $history = new TicketHistory();
        $history->ticket_id = $ticket->id;

        $output = '<div class="d-flex align-items-center">
            <div class="mt-0">
                <p class="mb-0 fs-12 mb-1">Status
            ';

        if($ticket->notes->isEmpty()){
            if($ticket->overduestatus != null){
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                ';
            }else{
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                ';
            }

        }else{
            if($ticket->overduestatus != null){
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }else{
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }
        }

        $output .= '
            <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(Note Created)</span></p>
        </div>
        <div class="ms-auto">
        <span class="float-end badge badge-primary-light">
            <span class="fs-11 font-weight-semibold">'.Auth::user()->role.'</span>
        </span>
        </div>

        </div>
        ';
        $history->ticketactions = $output;
        $history->save();

        $user = User::findOrFail($history->user_id);

        $ticketData = [
            'ticket_id' => $ticket->ticket_id,
            'note_username' => $user->name,
            'ticket_note' => $note->message,
            'ticket_admin_url' => url('/admin/ticket-view/'.$ticket->ticket_id),
        ];

        try{

            $admins = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.group_id')->whereNull('groups_users.user_id')->get();
            foreach($admins as $admin){
                if($admin->usetting->emailnotifyon == 1 && $admin->getRoleNames()[0] == 'superadmin' && setting('NOTE_CREATE_MAILS') == 'on' && $note->user_id != $admin->id){

//                 EmailMails::dispatch($note)->onQueue('tickets');

                }
            }
        }catch(\Exception $e){
            return response()->json(['success'=> 'The note was successfully submitted.']);
        }


        return response()->json(['success'=> 'The note was successfully submitted.']);
    }
    public function noteshow($ticket_id)
    {
        $ticket = Ticket::where('ticket_id', $ticket_id)->firstOrFail();
        $comments = $ticket->comments;
        $category = $ticket->category;

        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;


        return view('admin.viewticket.note', compact('ticket','category', 'comments', 'title','footertext'))->with($data);
    }
    public function notedestroy($id)
    {
        $notedelete = note::find($id);

        $ticket = Ticket::where('id', $notedelete->ticket_id)->firstOrFail();

            $tickethistory = new tickethistory();
            $tickethistory->ticket_id = $ticket->id;

            $output = '<div class="d-flex align-items-center">
                <div class="mt-0">
                    <p class="mb-0 fs-12 mb-1">Status
                ';
            if($ticket->note->isEmpty()){
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                    ';
                }

            }else{
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }
            }

            $output .= '
                <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(Note Deleted)</span></p>
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


        $notedelete->delete();

        return response()->json(['success'=> lang('The note was successfully deleted.', 'alerts')]);


    }

    public function selfassign(Request $request)
    {

        try {


            $callcenter = app('callcenters');

            $ticketselfassign = Ticket::uid($request->uid);

            if (!$ticketselfassign) {
                return response()->json([
                    'success' => false,
                    'message' => 'El ticket no existe.'
                ], 404);
            }

            $ticketselfassign->toassignuser_id = $callcenter->id;
            $ticketselfassign->myassignuser_id = null;
            $ticketselfassign->update();

            if ($request->assigned_userid) {
                $ticketselfassign->assign()->detach($request->assigned_userid);
            }

            $tickethistory = new TicketHistory();
            $tickethistory->ticket_id = $ticketselfassign->id;
            $tickethistory->ticketnote = $ticketselfassign->notes()->exists();
            $tickethistory->overdue = $ticketselfassign->overdue;
            $tickethistory->status = $ticketselfassign->status->title ?? 'Desconocido';
            $tickethistory->actions = 'Autoasignado';
            $tickethistory->username = $callcenter->firstname.' '.$callcenter->lastname;
            $tickethistory->type = $callcenter->roles()->first()->name ?? 'Sin rol';
            $tickethistory->save();

            return response()->json([
                'success' => true,
                'uid' => $ticketselfassign->uid,
                'message' => 'El ticket se asignó correctamente.'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error: ' . $e->getMessage()
           ]);

        }
    }


    public function ticketunassigns(Request $request)
    {
        try {

                $callcenter = app('callcenters');

                $calID = Ticket::uid($request->uid);
                $calID->toassignuser_id	 = null;
                $calID->myassignuser_id = null;
                $calID->save();
                $calID->assign()->detach($request->assigned_userid);

                $tickethistory = new TicketHistory();
                $tickethistory->ticket_id = $calID->id;
                $tickethistory->ticketnote = $calID->notes()->exists();
                $tickethistory->overdue = $calID->overdue;
                $tickethistory->status = $calID->status;
                $tickethistory->actions = 'UnAssigned Ticket';
                $tickethistory->username = $callcenter->firstname.' '.$callcenter->lastname;
                $tickethistory->type = $callcenter->roles()->first()->name ?? 'Sin rol';
                $tickethistory->save();

                return response()->json([
                    'success' => true,
                    'uid' => $calID->uid,
                    'message' => 'El ticket se asignó correctamente.'
                ]);

        } catch (\Exception $e) {

            return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error: ' . $e->getMessage()
            ]);

        }

    }


    public function ticketassigneds(Request $request)
    {
        if ($request->ajax()) {

            $assign = Ticket::uid($request->uid);

            if (!$assign) {
                return response()->json([
                    'success' => false,
                    'message' => 'El ticket no existe.'
                ], 404);
            }

            $assignedUserIds = $assign->assigns->pluck('toassignuser_id')->toArray();

            $users = User::role('callcenters')->get();

            $options = '<option label="Seleccionar agente"></option>';

            foreach ($users as $user) {

                if (Auth::id() === $user->id && !in_array($user->id, $assignedUserIds)) {
                    continue;
                }

                $selected = in_array($user->id, $assignedUserIds) ? 'selected' : '';
                $roleName = $user->getRoleNames()->first() ?? '';

                $options .= sprintf(
                    '<option value="%d" %s>%s (%s)</option>',
                    $user->id,
                    $selected,
                    e($user->firstname.' '.$user->lastname),
                    e($roleName)
                );
            }

            return response()->json([
                'success' => true,
                'assign_user_exist' => !empty($assignedUserIds) ? 'yes' : 'no',
                'assign_data' => $assign,
                'table_data' => $options,
                'total_data' => $users->count(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Solicitud inválida.'
        ], 400);
    }





}

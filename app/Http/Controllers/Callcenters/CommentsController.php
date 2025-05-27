<?php

namespace App\Http\Controllers\Callcenters;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Ticket\TicketComment;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\Category;
use App\Models\User;
use App\Models\Customer;
use Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Hash;
use App\Notifications\TicketCreateNotifications;
use App\Mail\mailmailablesend;
use Mail;
use App\Models\Ratingtoken;
use App\Models\CCMAILS;
use App\Models\tickethistory;

class CommentsController extends Controller
{
    public function postComment(Request $request,  $ticket_id)
    {

        if($request->status == 'Solved')
        {

            $this->validate($request, [
                'comment' => 'required'
            ]);
            $comment = TicketComment::create([
                'ticket_id' => $request->input('ticket_id'),
                'user_id' => Auth::user()->id,
                'cust_id' => null,
                'comment' => $request->input('comment')
            ]);
            foreach ($request->input('comments', []) as $file) {
                $comment->addMedia(public_path('uploads/comment/' . $file))->toMediaCollection('comments');
            }
            $ticket = Ticket::where('ticket_id', $ticket_id)->firstOrFail();
            $ticket->status = 'Closed';
            $ticket->replystatus = $request->input('status');
            // Auto Close Ticket
            $ticket->auto_close_ticket = null;
            // Auto Response Ticket
            $ticket->auto_replystatus = null;
            $ticket->last_reply = now();
            $ticket->closing_ticket = now();
            $ticket->auto_overdue_ticket = null;
            $ticket->overduestatus = null;
            $ticket->closedby_user = Auth::id();
            $ticket->lastreply_mail = Auth::id();
            $ticket->update();


            $tickethistory = new tickethistory();
            $tickethistory->ticket_id = $ticket->id;

            $output = '<div class="d-flex align-items-center">
                <div class="mt-0">
                    <p class="mb-0 fs-12 mb-1">Status
                ';
            if($ticket->ticketnote->isEmpty()){
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-success font-weight-semibold mx-1">'.$ticket->replystatus.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-success font-weight-semibold mx-1">'.$ticket->replystatus.'</span>
                    ';
                }

            }else{
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-success font-weight-semibold mx-1">'.$ticket->replystatus.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-success font-weight-semibold mx-1">'.$ticket->replystatus.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }
            }

            $output .= '
                <p class="mb-0 fs-17 font-weight-semibold text-dark">'.$comment->user->name.'<span class="fs-11 mx-1 text-muted">(Closed)</span></p>
            </div>
            <div class="ms-auto">
            <span class="float-end badge badge-primary-light">
                <span class="fs-11 font-weight-semibold">'.$comment->user->getRoleNames()[0].'</span>
            </span>
            </div>

            </div>
            ';
            $tickethistory->ticketactions = $output;
            $tickethistory->save();

            $cust = Customer::find($ticket->cust_id);
            $cust->notify(new TicketCreateNotifications($ticket));

            // create ticket notification
            if($ticket->category)
            {
                $notificationcat = $ticket->category->groupscategoryc()->get();
                $icc = array();
                if($notificationcat->isNotEmpty()){

                    foreach($notificationcat as $igc){

                        foreach($igc->groupsc->groupsuser()->get() as $user){
                            $icc[] .= $user->users_id;
                        }
                    }

                    if(!$icc){
                        $admins = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                        foreach($admins as $admin){
                            $admin->notify(new TicketCreateNotifications($ticket));
                        }

                    }else{

                        $user = User::whereIn('id', $icc)->get();
                        foreach($user as $users){
                            $users->notify(new TicketCreateNotifications($ticket));
                        }
                        $admins = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                        foreach($admins as $admin){
                            if($admin->getRoleNames()[0] == 'superadmin'){
                                $admin->notify(new TicketCreateNotifications($ticket));
                            }
                        }


                    }
                }else{
                    $admins = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                    foreach($admins as $admin){
                        $admin->notify(new TicketCreateNotifications($ticket));
                    }
                }
            }
            // Notification category Empty
            if(!$ticket->category)
            {
                $admins = User::get();
                foreach($admins as $admin){
                    $admin->notify(new TicketCreateNotifications($ticket));
                }

            }


            $ratingtoken =  Ratingtoken::create([

                'token' => str_random(64),
                'ticket_id' => $ticket->id,
            ]);

            $closed_agent = User::findOrFail(Auth::id());
            $ccemailsend = CCMAILS::where('ticket_id', $ticket->id)->first();

            if(setting('ticketrating') == 'on')
            {
                if($ticket->cust->userType == 'Guest'){
                    $ticketData = [
                        'closed_agent_name' => $closed_agent->name,
                        'closed_agent_role' => $closed_agent->getRoleNames()[0],
                        'ticket_username' => $ticket->cust->username,
                        'ticket_title' => $ticket->subject,
                        'ticket_id' => $ticket->ticket_id,
                        'comment' => $comment->comment,
                        'ticket_status' => $ticket->status,
                        'ticket_customer_url' => route('guest.rating', $ratingtoken->token),
                        'ratinglink' => route('gusetticket', $ticket->ticket_id),
                        'ticket_admin_url' => url('/admin/ticket-view/'.$ticket->ticket_id),
                    ];
                }
                if($ticket->cust->userType == 'Customer'){
                    $ticketData = [
                        'closed_agent_name' => $closed_agent->name,
                        'closed_agent_role' => $closed_agent->getRoleNames()[0],
                        'ticket_username' => $ticket->cust->username,
                        'ticket_title' => $ticket->subject,
                        'ticket_id' => $ticket->ticket_id,
                        'comment' => $comment->comment,
                        'ticket_status' => $ticket->status,
                        'ticket_customer_url' => route('guest.rating', $ratingtoken->token),
                        'ratinglink' => route('loadmore.load_data', $ticket->ticket_id),
                        'ticket_admin_url' => url('/admin/ticket-view/'.$ticket->ticket_id),
                    ];
                }
            }else{
                if($request->rating_on_off){
                    if($ticket->cust->userType == 'Guest'){
                        $ticketData = [
                            'closed_agent_name' => $closed_agent->name,
                            'closed_agent_role' => $closed_agent->getRoleNames()[0],
                            'ticket_username' => $ticket->cust->username,
                            'ticket_title' => $ticket->subject,
                            'ticket_id' => $ticket->ticket_id,
                            'comment' => $comment->comment,
                            'ticket_status' => $ticket->status,
                            'ticket_customer_url' => route('gusetticket', $ticket->ticket_id),
                            'ticket_admin_url' => url('/admin/ticket-view/'.$ticket->ticket_id),
                        ];
                    }
                    if($ticket->cust->userType == 'Customer'){
                        $ticketData = [
                            'closed_agent_name' => $closed_agent->name,
                            'closed_agent_role' => $closed_agent->getRoleNames()[0],
                            'ticket_username' => $ticket->cust->username,
                            'ticket_title' => $ticket->subject,
                            'ticket_id' => $ticket->ticket_id,
                            'comment' => $comment->comment,
                            'ticket_status' => $ticket->status,
                            'ticket_customer_url' => route('loadmore.load_data', $ticket->ticket_id),
                            'ticket_admin_url' => url('/admin/ticket-view/'.$ticket->ticket_id),
                        ];
                    }
                }else{
                    if($ticket->cust->userType == 'Guest'){
                        $ticketData = [
                            'closed_agent_name' => $closed_agent->name,
                            'closed_agent_role' => $closed_agent->getRoleNames()[0],
                            'ticket_username' => $ticket->cust->username,
                            'ticket_title' => $ticket->subject,
                            'ticket_id' => $ticket->ticket_id,
                            'comment' => $comment->comment,
                            'ticket_status' => $ticket->status,
                            'ratinglink' => route('guest.rating', $ratingtoken->token),
                            'ticket_customer_url' => route('gusetticket', $ticket->ticket_id),
                            'ticket_admin_url' => url('/admin/ticket-view/'.$ticket->ticket_id),
                        ];
                    }
                    if($ticket->cust->userType == 'Customer'){
                        $ticketData = [
                            'closed_agent_name' => $closed_agent->name,
                            'closed_agent_role' => $closed_agent->getRoleNames()[0],
                            'ticket_username' => $ticket->cust->username,
                            'ticket_title' => $ticket->subject,
                            'ticket_id' => $ticket->ticket_id,
                            'comment' => $comment->comment,
                            'ticket_status' => $ticket->status,
                            'ratinglink' => route('guest.rating', $ratingtoken->token),
                            'ticket_customer_url' => route('loadmore.load_data', $ticket->ticket_id),
                            'ticket_admin_url' => url('/admin/ticket-view/'.$ticket->ticket_id),
                        ];
                    }
                }
            }

            try{

                if($ticket->category)
                {
                    $notificationcatss = $ticket->category->groupscategoryc()->get();
                    $icc = array();
                    if($notificationcatss->isNotEmpty()){

                        foreach($notificationcatss as $igc){

                            foreach($igc->groupsc->groupsuser()->get() as $user){
                                $icc[] .= $user->users_id;
                            }
                        }

                        if(!$icc){
                            $admins = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                            foreach($admins as $admin){
                                if($admin->getRoleNames()[0] != 'superadmin' && $admin->usetting->emailnotifyon == 1){
                                    Mail::to($admin->email)
                                    ->send( new mailmailablesend( 'send_mail_to_agent_when_ticket_closed_by_admin_or_agent', $ticketData ) );
                                }
                            }

                        }else{

                            if($ticket->myassignuser){
                                $assignee = $ticket->ticketassignmutliples;
                                foreach($assignee as $assignees){
                                    $user = User::where('id',$assignees->toassignuser_id)->get();
                                    foreach($user as $users){
                                        if($users->id == $assignees->toassignuser_id && $users->getRoleNames()[0] != 'superadmin' && $users->usetting->emailnotifyon == 1){
                                            Mail::to($users->email)
                                            ->send( new mailmailablesend( 'send_mail_to_agent_when_ticket_closed_by_admin_or_agent', $ticketData ) );
                                        }
                                    }
                                }
                            }
                            else if ($ticket->selfassignuser_id) {
                                $self = User::findOrFail($ticket->selfassignuser_id);
                                if($self->getRoleNames()[0] != 'superadmin' && $self->usetting->emailnotifyon == 1){
                                    Mail::to($self->email)
                                    ->send( new mailmailablesend( 'send_mail_to_agent_when_ticket_closed_by_admin_or_agent', $ticketData ) );
                                }
                            }
                            else if($icc ){
                                $user = User::whereIn('id', $icc)->get();
                                foreach($user as $users){
                                    if($users->usetting->emailnotifyon == 1){
                                        Mail::to($users->email)
                                        ->send( new mailmailablesend( 'send_mail_to_agent_when_ticket_closed_by_admin_or_agent', $ticketData ) );
                                    }
                                }
                            }
                            else {
                                $users = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                                foreach($users as $user){
                                    if($user->getRoleNames()[0] != 'superadmin' && $user->usetting->emailnotifyon == 1){
                                        Mail::to($user->email)
                                        ->send( new mailmailablesend( 'send_mail_to_agent_when_ticket_closed_by_admin_or_agent', $ticketData ) );
                                    }
                                }
                            }

                        }
                    }else{

                        if($ticket->myassignuser){
                            $assignee = $ticket->ticketassignmutliples;
                            foreach($assignee as $assignees){
                                $user = User::where('id',$assignees->toassignuser_id)->get();
                                foreach($user as $users){
                                    if($users->id == $assignees->toassignuser_id && $users->getRoleNames()[0] != 'superadmin' && $users->usetting->emailnotifyon == 1){
                                        Mail::to($users->email)
                                        ->send( new mailmailablesend( 'send_mail_to_agent_when_ticket_closed_by_admin_or_agent', $ticketData ) );
                                    }
                                }
                            }
                        } else if ($ticket->selfassignuser_id) {
                            $self = User::findOrFail($ticket->selfassignuser_id);
                            if($self->getRoleNames()[0] != 'superadmin' && $self->usetting->emailnotifyon == 1){
                                Mail::to($self->email)
                                ->send( new mailmailablesend( 'send_mail_to_agent_when_ticket_closed_by_admin_or_agent', $ticketData ) );
                            }
                        } else {

                            $users = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                            foreach($users as $user){
                                if($user->getRoleNames()[0] != 'superadmin' && $user->usetting->emailnotifyon == 1){
                                    Mail::to($user->email)
                                    ->send( new mailmailablesend( 'send_mail_to_agent_when_ticket_closed_by_admin_or_agent', $ticketData ) );
                                }
                            }
                        }
                    }
                }
                if(!$ticket->category)
                {

                    if($ticket->myassignuser){
                        $assignee = $ticket->ticketassignmutliples;
                        foreach($assignee as $assignees){
                            $user = User::where('id',$assignees->toassignuser_id)->get();
                            foreach($user as $users){
                                if($users->id == $assignees->toassignuser_id && $users->getRoleNames()[0] != 'superadmin' && $users->usetting->emailnotifyon == 1){
                                    Mail::to($users->email)
                                    ->send( new mailmailablesend( 'send_mail_to_agent_when_ticket_closed_by_admin_or_agent', $ticketData ) );
                                }
                            }
                        }
                    } else if ($ticket->selfassignuser_id) {
                        $self = User::findOrFail($ticket->selfassignuser_id);
                        if($self->getRoleNames()[0] != 'superadmin' && $self->usetting->emailnotifyon == 1){
                            Mail::to($self->email)
                            ->send( new mailmailablesend( 'send_mail_to_agent_when_ticket_closed_by_admin_or_agent', $ticketData ) );
                        }
                    } else {

                        $users = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                        foreach($users as $user){
                            if($user->getRoleNames()[0] != 'superadmin' && $user->usetting->emailnotifyon == 1){
                                Mail::to($user->email)
                                ->send( new mailmailablesend( 'send_mail_to_agent_when_ticket_closed_by_admin_or_agent', $ticketData ) );
                            }
                        }
                    }

                }

                $admins = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                foreach($admins as $admin){
                    if($admin->getRoleNames()[0] == 'superadmin' && $admin->usetting->emailnotifyon == 1){
                        Mail::to($admin->email)
                        ->send( new mailmailablesend( 'send_mail_to_agent_when_ticket_closed_by_admin_or_agent', $ticketData ) );
                    }
                }

                if($request->rating_on_off){
                    Mail::to($ticket->cust->email)
                    ->send( new mailmailablesend( 'send_mail_to_customer_when_ticket_closed_by_admin', $ticketData) );
                }else{
                    Mail::to($ticket->cust->email)
                    ->send( new mailmailablesend( 'customer_rating', $ticketData) );
                }

                 Mail::to($ccemailsend->ccemails)
                ->send( new mailmailablesend( 'CCmail_sendemail_whenticketclosed', $ticketData) );

            }catch(\Exception $e){
                return redirect()->back()->with("success", lang('The response to the ticket was successful.', 'alerts'));
            }

            return redirect()->back()->with("success", lang('The response to the ticket was successful.', 'alerts'));

        }else{
            $this->validate($request, [
                'comment' => 'required'
            ]);
            $tic = Ticket::where('ticket_id', $ticket_id)->firstOrFail();
            if($tic->comments()->get() != null){
                $comm = $tic->comments()->update([
                    'display' => null
                ]);
            }

            $comment = TicketComment::create([
                'ticket_id' => $request->input('ticket_id'),
                'user_id' => Auth::user()->id,
                'cust_id' => null,
                'comment' => $request->input('comment'),
                'display' => 1,
            ]);
            foreach ($request->input('comments', []) as $file) {
                $comment->addMedia(public_path('uploads/comment/' . $file))->toMediaCollection('comments');
            }
            $ticket = Ticket::where('ticket_id', $ticket_id)->firstOrFail();
            $ticket->status = $request->input('status');
            $ticket->replystatus = 'Waiting';
            if($request->status == 'On-Hold'){
                $ticket->note = $request->input('note');
                // Auto Close Ticket
                $ticket->auto_close_ticket = null;
                // Auto Response Ticket
                $ticket->auto_replystatus = null;
                //Auto Overdue Ticket
                $ticket->auto_overdue_ticket = null;
                $ticket->overduestatus = null;
            }
            else{
                // Auto Closing Ticket
                if(setting('AUTO_CLOSE_TICKET') == 'no'){
                    $ticket->auto_close_ticket = null;
                }else{
                    if(setting('AUTO_CLOSE_TICKET_TIME') == '0'){
                        $ticket->auto_close_ticket = null;
                    }else{
                        if(Auth::check() && Auth::user()){
                            if($ticket->status == 'Closed'){
                                $ticket->auto_close_ticket = null;
                            }
                            else{
                                $ticket->auto_close_ticket = now()->addHours(setting('AUTO_RESPONSETIME_TICKET_TIME'))->addDays(setting('AUTO_CLOSE_TICKET_TIME'));
                            }
                        }
                    }
                }
                // End Auto Close Ticket

                // Auto Response Ticket

                if(setting('AUTO_RESPONSETIME_TICKET') == 'no'){
                    $ticket->auto_replystatus = null;
                }else{
                    if(setting('AUTO_RESPONSETIME_TICKET_TIME') == '0'){
                        $ticket->auto_replystatus = null;
                    }else{
                        if(Auth::check() && Auth::user()){
                            $ticket->auto_replystatus = now()->addHours(setting('AUTO_RESPONSETIME_TICKET_TIME'));
                        }
                    }
                }
                // End Auto Response Ticket

                 // Auto Overdue Ticket
                 if(setting('AUTO_OVERDUE_TICKET') == 'no'){
                    $ticket->auto_overdue_ticket = null;
                    $ticket->overduestatus = null;
                }else{
                    if(setting('AUTO_OVERDUE_TICKET_TIME') == '0'){
                        $ticket->auto_overdue_ticket = null;
                        $ticket->overduestatus = null;
                    }else{
                        if(Auth::check() && Auth::user()){
                            if($ticket->status == 'Closed'){
                                $ticket->auto_overdue_ticket = null;
                                $ticket->overduestatus = null;
                            }
                            else{
                                $ticket->auto_overdue_ticket = null;
                                $ticket->overduestatus = null;
                            }
                        }
                    }
                }
                // End Auto Overdue Ticket
            }
            $ticket->last_reply = now();
            $ticket->lastreply_mail = Auth::id();
            $ticket->update();


            $tickethistory = new tickethistory();
            $tickethistory->ticket_id = $ticket->id;

            $output = '<div class="d-flex align-items-center">
                <div class="mt-0">
                    <p class="mb-0 fs-12 mb-1">Status
                ';
            if($ticket->ticketnote->isEmpty()){
                if($ticket->overduestatus != null){

                    if($ticket->status == 'On-Hold'){
                        $output .= '
                        <span class="text-warning font-weight-semibold mx-1">'.$ticket->status.'</span>
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                        ';
                    }else{
                        $output .= '
                        <span class="text-info font-weight-semibold mx-1">'.$ticket->status.'</span>
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                        ';
                    }


                }else{

                    if($ticket->status == 'On-Hold'){
                        $output .= '
                        <span class="text-warning font-weight-semibold mx-1">'.$ticket->status.'</span>
                        ';
                    }else{
                        $output .= '
                        <span class="text-info font-weight-semibold mx-1">'.$ticket->status.'</span>
                        ';
                    }

                }

            }else{
                if($ticket->overduestatus != null){
                    if($ticket->status == 'On-Hold'){
                        $output .= '
                        <span class="text-warning font-weight-semibold mx-1">'.$ticket->status.'</span>
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                        <span class="text-warning font-weight-semibold mx-1">Note</span>
                        ';
                    }else{
                        $output .= '
                        <span class="text-info font-weight-semibold mx-1">'.$ticket->status.'</span>
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                        <span class="text-warning font-weight-semibold mx-1">Note</span>
                        ';
                    }

                }else{

                    if($ticket->status == 'On-Hold'){
                        $output .= '
                        <span class="text-warning font-weight-semibold mx-1">'.$ticket->status.'</span>
                        <span class="text-warning font-weight-semibold mx-1">Note</span>
                        ';
                    }else{
                        $output .= '
                        <span class="text-info font-weight-semibold mx-1">'.$ticket->status.'</span>
                        <span class="text-warning font-weight-semibold mx-1">Note</span>
                        ';
                    }

                }
            }

            $output .= '
                <p class="mb-0 fs-17 font-weight-semibold text-dark">'.$comment->user->name.'<span class="fs-11 mx-1 text-muted">(Responded)</span></p>
            </div>
            <div class="ms-auto">
            <span class="float-end badge badge-primary-light">
                <span class="fs-11 font-weight-semibold">'.$comment->user->getRoleNames()[0].'</span>
            </span>
            </div>

            </div>
            ';
            $tickethistory->ticketactions = $output;
            $tickethistory->save();

            $cust = Customer::find($ticket->cust_id);
            $cust->notify(new TicketCreateNotifications($ticket));

            $ccemailsend = CCMAILS::where('ticket_id', $ticket->id)->first();

            if($ticket->cust->userType == 'Guest'){
                $ticketData = [
                    'ticket_username' => $ticket->cust->username,
                    'ticket_title' => $ticket->subject,
                    'ticket_id' => $ticket->ticket_id,
                    'ticket_status' => $ticket->status,
                    'comment' => $comment->comment,
                    'ratinglink' => route('guest.rating', $ticket->ticket_id),
                    'ticket_customer_url' => route('guest.ticketdetailshow', $ticket->ticket_id),
                    'ticket_admin_url' => url('/admin/ticket-view/'.$ticket->ticket_id),
                ];
            }
            if($ticket->cust->userType == 'Customer'){
                $ticketData = [
                    'ticket_username' => $ticket->cust->username,
                    'ticket_title' => $ticket->subject,
                    'ticket_id' => $ticket->ticket_id,
                    'ticket_status' => $ticket->status,
                    'comment' => $comment->comment,
                    'ratinglink' => route('guest.rating', $ticket->ticket_id),
                    'ticket_customer_url' => route('loadmore.load_data', $ticket->ticket_id),
                    'ticket_admin_url' => url('/admin/ticket-view/'.$ticket->ticket_id),
                ];
            }

            try{

                Mail::to($ticket->cust->email)
                ->send( new mailmailablesend( 'customer_send_ticket_reply', $ticketData) );

                Mail::to($ccemailsend->ccemails)
                ->send( new mailmailablesend( 'customer_send_ticket_reply', $ticketData) );

            }catch(\Exception $e){
                return redirect()->back()->with("success", lang('The response to the ticket was successful.', 'alerts'));
            }

            return redirect()->back()->with("success", lang('The response to the ticket was successful.', 'alerts'));
        }

    }

    public function storeMedia(Request $request)
    {
        $path = public_path('uploads/comment');

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $file = $request->file('file');

        $name = $file->getClientOriginalName();

        $file->move($path, $name);

        return response()->json([
            'name'          => $name,
            'original_name' => $file->getClientOriginalName(),
        ]);
    }


    public function updateedit(Request $request, $id){
        if ($request->has('message')) {

            $this->validate($request, [
                'message' => 'required'
            ]);
            $ticket = Ticket::findOrFail($id);
            $ticket->message = $request->input('message');

            $ticket->update();
            return redirect()->back();

        }else{
            $this->validate($request, [
                'editcomment' => 'required'
            ]);
            $comment = TicketComment::findOrFail($id);
            $comment->comment = $request->input('editcomment');

            $comment->update();


            $tickethistory = new tickethistory();
            $tickethistory->ticket_id = $comment->ticket->id;

            $output = '<div class="d-flex align-items-center">
                <div class="mt-0">
                    <p class="mb-0 fs-12 mb-1">Status
                ';
            if($comment->ticket->ticketnote->isEmpty()){
                if($comment->ticket->overduestatus != null){
                    $output .= '
                    <span class="text-primary font-weight-semibold mx-1">'.$comment->ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$comment->ticket->overduestatus.'</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-primary font-weight-semibold mx-1">'.$comment->ticket->status.'</span>
                    ';
                }

            }else{
                if($comment->ticket->overduestatus != null){
                    $output .= '
                    <span class="text-primary font-weight-semibold mx-1">'.$comment->ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$comment->ticket->overduestatus.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-info font-weight-semibold mx-1">'.$comment->ticket->status.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }
            }

            $output .= '
                <p class="mb-0 fs-17 font-weight-semibold text-dark">'.$comment->user->name.'<span class="fs-11 mx-1 text-muted">(Comment Modified)</span></p>
            </div>
            <div class="ms-auto">
            <span class="float-end badge badge-primary-light">
                <span class="fs-11 font-weight-semibold">'.$comment->user->getRoleNames()[0].'</span>
            </span>
            </div>

            </div>
            ';
            $tickethistory->ticketactions = $output;
            $tickethistory->save();

            return redirect()->back();
        }


    }

    public function deletecomment(Request $request, $id)
    {
        $comment = TicketComment::findOrFail($id);
        $comment->delete();

        $tickethistory = new tickethistory();
        $tickethistory->ticket_id = $comment->ticket->id;

        $output = '<div class="d-flex align-items-center">
            <div class="mt-0">
                <p class="mb-0 fs-12 mb-1">Status
            ';
        if($comment->ticket->ticketnote->isEmpty()){
            if($comment->ticket->overduestatus != null){
                $output .= '
                <span class="text-primary font-weight-semibold mx-1">'.$comment->ticket->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$comment->ticket->overduestatus.'</span>
                ';
            }else{
                $output .= '
                <span class="text-primary font-weight-semibold mx-1">'.$comment->ticket->status.'</span>
                ';
            }

        }else{
            if($comment->ticket->overduestatus != null){
                $output .= '
                <span class="text-primary font-weight-semibold mx-1">'.$comment->ticket->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$comment->ticket->overduestatus.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }else{
                $output .= '
                <span class="text-info font-weight-semibold mx-1">'.$comment->ticket->status.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }
        }

        $output .= '
            <p class="mb-0 fs-17 font-weight-semibold text-dark">'.$comment->user->name.'<span class="fs-11 mx-1 text-muted">(Comment Deleted)</span></p>
        </div>
        <div class="ms-auto">
        <span class="float-end badge badge-primary-light">
            <span class="fs-11 font-weight-semibold">'.$comment->user->getRoleNames()[0].'</span>
        </span>
        </div>

        </div>
        ';
        $tickethistory->ticketactions = $output;
        $tickethistory->save();

        return response()->json(['success' => lang('The ticket comment has been deleted successfully.', 'alerts'),]);
    }

        /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($ticket_id)
    {

    }

    public function imagedestroy($id)
    {   //For Deleting Users
        $commentss = Media::findOrFail($id);
        $commentss->delete();
        return response()->json([
            'success' => lang('Deleted Successfully', 'alerts')
        ]);
    }

    public function reopenticket(Request $req){

        $reopenticket = Ticket::find($req->reopenid);
        $reopenticket->status = 'Re-Open';
        $reopenticket->replystatus = null;
        $reopenticket->closedby_user = null;
        $reopenticket->lastreply_mail = Auth::id();
        $reopenticket->update();


        $tickethistory = new tickethistory();
        $tickethistory->ticket_id = $reopenticket->id;

        $output = '<div class="d-flex align-items-center">
            <div class="mt-0">
                <p class="mb-0 fs-12 mb-1">Status
            ';
        if($reopenticket->ticketnote->isEmpty()){
            if($reopenticket->overduestatus != null){
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">'.$reopenticket->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$reopenticket->overduestatus.'</span>
                ';
            }else{
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">'.$reopenticket->status.'</span>
                ';
            }

        }else{
            if($reopenticket->overduestatus != null){
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">'.$reopenticket->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$reopenticket->overduestatus.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }else{
                $output .= '
                <span class="text-teal font-weight-semibold mx-1">'.$reopenticket->status.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }
        }

        $output .= '
            <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(Re-opened)</span></p>
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





        $cust = Customer::with('custsetting')->find($reopenticket->cust_id);
        $cust->notify(new TicketCreateNotifications($reopenticket));

        if($reopenticket->cust->userType == 'Guest'){
            $ticketData = [
                'ticket_username' => $reopenticket->cust->username,
                'ticket_title' => $reopenticket->subject,
                'ticket_id' => $reopenticket->ticket_id,
                'ticket_status' => $reopenticket->status,
                // 'comment' => $comment->comment,
                'ratinglink' => route('guest.rating', $reopenticket->ticket_id),
                'ticket_customer_url' => route('gusetticket', $reopenticket->ticket_id),
                'ticket_admin_url' => url('/admin/ticket-view/'.$reopenticket->ticket_id),
            ];
        }
        if($reopenticket->cust->userType == 'Customer'){
            $ticketData = [
                'ticket_username' => $reopenticket->cust->username,
                'ticket_title' => $reopenticket->subject,
                'ticket_id' => $reopenticket->ticket_id,
                'ticket_status' => $reopenticket->status,
                // 'comment' => $comment->comment,
                'ratinglink' => route('guest.rating', $reopenticket->ticket_id),
                'ticket_customer_url' => route('loadmore.load_data', $reopenticket->ticket_id),
                'ticket_admin_url' => url('/admin/ticket-view/'.$reopenticket->ticket_id),
            ];
        }

        try{

            Mail::to($ccemailsend->ccemails)
            ->send( new mailmailablesend( 'customer_send_ticket_reopen', $ticketData) );

            Mail::to($reopenticket->cust->email)
            ->send(new mailmailablesend('customer_send_ticket_reopen', $ticketData ) );



        }catch(\Exception $e){
            return response()->json([
                'success' => lang('The ticket has been successfully reopened.', 'alerts'),
            ]);
        }
        return response()->json([
            'success' => lang('The ticket has been successfully reopened.', 'alerts'),
        ]);

    }

}

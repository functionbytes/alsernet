<?php

namespace App\Http\Controllers\Callcenters\Tickets;

use App\Mail\Customers\Tickets\Supports\ReplayMails as ManagerReplayMails;
use App\Mail\Customers\Tickets\Customer\ReplayMails as CustomerReplayMails;
use App\Notifications\TicketCreateNotifications;
use App\Http\Controllers\Controller;
use App\Models\Ticket\TicketHistory;
use App\Models\Ticket\TicketComment;
use App\Models\Ticket\Ticket;
use Illuminate\Http\Request;
use App\Models\User;
use Auth;

class CommentsController extends Controller
{
    public function comment(Request $request,  $uid)
    {

        $ticket = Ticket::uid($uid);

        if($ticket->status->slug == "Closed"){

             return redirect()->back()->with("error", 'El billete ya ha sido cerrado.');
        }
        else{

            if($ticket->comments()->get() != null){
                $comm = $ticket->comments()->update([
                    'display' => null
                ]);
            }

            $comment = TicketComment::create([
                'ticket_id' => $request->input('ticket_id'),
                'cust_id' => Auth::user()->id,
                'user_id' => null,
                'display' => 1,
                'comment' => $request->input('comment')
            ]);

            foreach ($request->input('comments', []) as $file) {
                $comment->addMedia(public_path('uploads/comment/' . $file))->toMediaCollection('comments');
            }

            if(request()->has(['status'])){

                $ticket->status = $request->input('status');
                $ticket->closing_ticket = now();
                $ticket->update();

                $ticketOwner = $ticket->user;

            }

            $ticket->last_reply = now();
            // Auto Overdue Ticket

            if(setting('auto_overdue_ticket') == 'no'){
                $ticket->auto_overdue_ticket = null;
                $ticket->overduestatus = null;
            }else{
                if(setting('auto_overdue_ticket_time') == '0'){
                    $ticket->auto_overdue_ticket = null;
                    $ticket->overduestatus = null;
                }else{
                    if(Auth::check() && Auth::user()){
                        if($ticket->status->slug == 'closed'){
                            $ticket->auto_overdue_ticket = null;
                            $ticket->overduestatus = null;
                        }
                        else{
                            $ticket->auto_overdue_ticket = now()->addDays(setting('auto_overdue_ticket_time'));
                            $ticket->overduestatus = null;
                        }
                    }
                }
            }
            // Auto Overdue Ticket

            // Auto Closing Ticket

            if(setting('auto_close_ticket') == 'no'){
                $ticket->auto_close_ticket = null;
            }else{
                if(setting('auto_close_ticket_time') == '0'){
                    $ticket->auto_close_ticket = null;
                }else{

                    if(Auth::check() && Auth::user()){
                        $ticket->auto_close_ticket = null;
                    }
                }
            }
            // End Auto Close Ticket

            // Auto Response Ticket

            if(setting('auto_responsetime_ticket') == 'no'){
                $ticket->auto_replystatus = null;
            }else{
                if(setting('auto_responsetime_ticket_time') == '0'){
                    $ticket->auto_replystatus = null;
                }else{
                    if(Auth::check() && Auth::user()){
                        $ticket->auto_replystatus = null;
                    }
                }
            }
            // End Auto Response Ticket

            if(request()->input(['status']) == 'Closed'){
                $ticket->replystatus = 'Solved';
            }else{
                $ticket->replystatus = 'Replied';
            }
            $ticket->update();

            if(request()->input(['status']) == 'Closed')
            {

                $history = new TicketHistory();
                $history->ticket_id = $ticket->id;

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
                    <p class="mb-0 fs-17 font-weight-semibold text-dark">'.$comment->cust->username.'<span class="fs-11 mx-1 text-muted">(Closed)</span></p>
                </div>
                <div class="ms-auto">
                <span class="float-end badge badge-danger-light">
                    <span class="fs-11 font-weight-semibold">'.$comment->cust->userType.'</span>
                </span>
                </div>

                </div>
                ';
                $history->ticketactions = $output;
                $history->save();

                /**** End Close Ticket notificaton ****/

                $ticketData = [
                    'ticket_username' => $ticket->cust->username,
                    'ticket_id' => $ticket->ticket_id,
                    'ticket_title' => $ticket->subject,
                    'ticket_description' => $ticket->message,
                    'ticket_status' => $ticket->status,
                    'ticket_email' => $ticket->cust->email,
                    'comment' => $comment->comment,
                    'ticket_customer_url' => route('loadmore.load_data', $ticket->ticket_id),
                    'ticket_admin_url' => url('/admin/ticket-view/'.$ticket->ticket_id),
                ];

                $this->sendNotificacionEmail($ticketData, $ticket);


            }else{


                $history = new TicketHistory();
                $history->ticket_id = $ticket->id;

                $output = '<div class="d-flex align-items-center">
                    <div class="mt-0">
                        <p class="mb-0 fs-12 mb-1">Status
                    ';
                if($ticket->ticketnote->isEmpty()){
                    if($ticket->overduestatus != null){
                        $output .= '
                        <span class="text-info font-weight-semibold mx-1">'.$ticket->status.'</span>
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                        ';
                    }else{
                        $output .= '
                        <span class="text-info font-weight-semibold mx-1">'.$ticket->status.'</span>
                        ';
                    }

                }else{
                    if($ticket->overduestatus != null){
                        $output .= '
                        <span class="text-info font-weight-semibold mx-1">'.$ticket->status.'</span>
                        <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                        <span class="text-warning font-weight-semibold mx-1">Note</span>
                        ';
                    }else{
                        $output .= '
                        <span class="text-info font-weight-semibold mx-1">'.$ticket->status.'</span>
                        <span class="text-warning font-weight-semibold mx-1">Note</span>
                        ';
                    }
                }

                $output .= '
                    <p class="mb-0 fs-17 font-weight-semibold text-dark">'.$comment->cust->username.'<span class="fs-11 mx-1 text-muted">(Responded)</span></p>
                </div>
                <div class="ms-auto">
                <span class="float-end badge badge-danger-light">
                    <span class="fs-11 font-weight-semibold">'.$comment->cust->userType.'</span>
                </span>
                </div>

                </div>
                ';
                $history->ticketactions = $output;
                $history->save();


                $ticketData = [
                    'ticket_username' => $ticket->cust->username,
                    'ticket_title' => $ticket->subject,
                    'ticket_id' => $ticket->ticket_id,
                    'ticket_status' => $ticket->status,
                    'comment' => $comment->comment,
                    'ticket_admin_url' => url('/admin/ticket-view/'.$ticket->ticket_id),
                ];


                $this->sendNotificacionEmail($ticketData, $ticket);

                return redirect()->back()->with("success", 'La respuesta al ticket fue exitosa.');

            }
        }

    }
    public function update(Request $request, $uid){

        if ($request->has('message')) {

            $ticket = Ticket::uid($uid);
            $ticket->message = $request->input('message');
            $ticket->update();
            return redirect()->back()->with('success', 'Actualizado con éxito');

        }else{

            $comment = TicketComment::uid($uid);
            $comment->comment = $request->input('editcomment');
            $comment->update();

            $history = new TicketHistory();
            $history->ticket_id = $comment->ticket->id;

            $output = '<div class="d-flex align-items-center">
                <div class="mt-0">
                    <p class="mb-0 fs-12 mb-1">Status
                ';
            if($comment->ticket->ticketnote->isEmpty()){
                if($comment->ticket->overduestatus != null){
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">'.$comment->ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$comment->ticket->overduestatus.'</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$comment->ticket->status.'</span>
                    ';
                }

            }else{
                if($comment->ticket->overduestatus != null){
                    $output .= '
                    <span class="text-teal font-weight-semibold mx-1">'.$comment->ticket->status.'</span>
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
            <p class="mb-0 fs-17 font-weight-semibold text-dark">'.$comment->cust->username.'<span class="fs-11 mx-1 text-muted">(Comment Modified)</span></p>
            </div>
                <div class="ms-auto">
                    <span class="float-end badge badge-danger-light">
                        <span class="fs-11 font-weight-semibold">'.$comment->cust->userType.'</span>
                    </span>
                </div>
            </div>';

            $history->ticketactions = $output;
            $history->save();

            return redirect()->back()->with('success', 'Actualizado con éxito');

        }


    }
    public function destroy($id){

            $commentss = Media::findOrFail($id);
            $commentss->delete();
            return response()->json([
                'success' => '¡La imagen se ha eliminado correctamente!'
            ]);
    }
    public function storage(Request $request)
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
    public function sendNotificacionEmail($ticketData, $ticket){

        try {


            /* customers reply to ticket notification and mail */
            if ($ticket->lastreply_mail == null) {

                $notificationcatss = $ticket->category->groupscategoryc()->get();
                $icc = array();

                if ($notificationcatss->isNotEmpty()) {

                    foreach ($notificationcatss as $igc) {

                        foreach ($igc->groupsc->groupsuser()->get() as $user) {
                            $icc[] .= $user->users_id;
                        }
                    }

                    if (!$icc) {
                        $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                        foreach ($admins as $admin) {
                            if ($admin->getRoleNames()[0] == 'superadmin') {
                                $admin->notify(new TicketCreateNotifications($ticket));
                                if ($admin->usetting->emailnotifyon == 1) {
                                    ManagerReplayMails::dispatch($ticketData,$admin->email)->onQueue('notifications');
                                }
                            }
                        }
                    } else {
                        if ($ticket->myassignuser) {
                            $assignee = $ticket->ticketassignmutliples;
                            foreach ($assignee as $assignees) {
                                $user = User::where('id', $assignees->toassignuser_id)->get();
                                foreach ($user as $users) {
                                    if ($users->id == $assignees->toassignuser_id) {
                                        $users->notify(new TicketCreateNotifications($ticket));
                                        if ($users->usetting->emailnotifyon == 1) {
                                            ManagerReplayMails::dispatch($ticketData, $users->email)->onQueue('notifications');
                                        }
                                    }
                                }
                            }
                            $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                            foreach ($admins as $admin) {
                                if ($admin->getRoleNames()[0] == 'superadmin') {
                                    $admin->notify(new TicketCreateNotifications($ticket));
                                    if ($admin->usetting->emailnotifyon == 1) {
                                        ManagerReplayMails::dispatch($ticketData, $admin->email)->onQueue('notifications');
                                    }
                                }
                            }
                        } else if ($ticket->selfassignuser_id) {
                            $self = User::findOrFail($ticket->selfassignuser_id);
                            $self->notify(new TicketCreateNotifications($ticket));
                            if ($self->usetting->emailnotifyon == 1) {
                                ManagerReplayMails::dispatch($ticketData, $self->email)->onQueue('notifications');
                            }
                            $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                            foreach ($admins as $admin) {
                                if ($admin->getRoleNames()[0] == 'superadmin') {
                                    $admin->notify(new TicketCreateNotifications($ticket));
                                    if ($admin->usetting->emailnotifyon == 1) {
                                        ManagerReplayMails::dispatch($ticketData, $admin->email)->onQueue('notifications');
                                    }
                                }
                            }
                        } else if ($icc) {
                            $user = User::whereIn('id', $icc)->get();
                            foreach ($user as $users) {
                                $users->notify(new TicketCreateNotifications($ticket));
                                if ($users->usetting->emailnotifyon == 1) {
                                    ManagerReplayMails::dispatch($ticketData, $users->email)->onQueue('notifications');
                                }
                            }
                            $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                            foreach ($admins as $admin) {
                                if ($admin->getRoleNames()[0] == 'superadmin') {
                                    $admin->notify(new TicketCreateNotifications($ticket));
                                    if ($admin->usetting->emailnotifyon == 1) {
                                        ManagerReplayMails::dispatch($ticketData, $admin->email)->onQueue('notifications');
                                    }
                                }
                            }
                        } else {
                            $users = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                            foreach ($users as $user) {
                                $user->notify(new TicketCreateNotifications($ticket));
                                if ($user->usetting->emailnotifyon == 1) {
                                    ManagerReplayMails::dispatch($ticketData, $user->email)->onQueue('notifications');
                                }
                            }
                        }
                    }
                } else {
                    if ($ticket->myassignuser) {
                        $assignee = $ticket->ticketassignmutliples;
                        foreach ($assignee as $assignees) {
                            $user = User::where('id', $assignees->toassignuser_id)->get();
                            foreach ($user as $users) {
                                if ($users->id == $assignees->toassignuser_id) {
                                    $users->notify(new TicketCreateNotifications($ticket));
                                    if ($users->usetting->emailnotifyon == 1) {
                                        ManagerReplayMails::dispatch($ticketData, $users->email)->onQueue('notifications');
                                    }
                                }
                            }
                        }
                        $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                        foreach ($admins as $admin) {
                            if ($admin->getRoleNames()[0] == 'superadmin') {
                                $admin->notify(new TicketCreateNotifications($ticket));
                                if ($admin->usetting->emailnotifyon == 1) {
                                    ManagerReplayMails::dispatch($ticketData, $admin->email)->onQueue('notifications');
                                }
                            }
                        }
                    } else if ($ticket->selfassignuser_id) {
                        $self = User::findOrFail($ticket->selfassignuser_id);
                        $self->notify(new TicketCreateNotifications($ticket));
                        if ($self->usetting->emailnotifyon == 1) {
                            ManagerReplayMails::dispatch($ticketData, $self->email)->onQueue('notifications');

                        }
                        $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                        foreach ($admins as $admin) {
                            if ($admin->getRoleNames()[0] == 'superadmin') {
                                $admin->notify(new TicketCreateNotifications($ticket));
                                if ($admin->usetting->emailnotifyon == 1) {
                                    ManagerReplayMails::dispatch($ticketData, $admin->email)->onQueue('notifications');
                                }
                            }
                        }
                    } else {
                        $users = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                        foreach ($users as $user) {
                            $user->notify(new TicketCreateNotifications($ticket));
                            if ($user->usetting->emailnotifyon == 1) {
                                ManagerReplayMails::dispatch($ticketData, $user->email)->onQueue('notifications');
                            }
                        }
                    }
                }
            }
            if ($ticket->lastreply_mail != null) {
                if ($ticket->category) {
                    $notificationcatss = $ticket->category->groupscategoryc()->get();
                    $icc = array();
                    if ($notificationcatss->isNotEmpty()) {

                        foreach ($notificationcatss as $igc) {

                            foreach ($igc->groupsc->groupsuser()->get() as $user) {
                                $icc[] .= $user->users_id;
                            }
                        }

                        if (!$icc) {
                            $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                            foreach ($admins as $admin) {
                                if ($admin->getRoleNames()[0] == 'superadmin') {
                                    $admin->notify(new TicketCreateNotifications($ticket));
                                    if ($admin->usetting->emailnotifyon == 1) {
                                        ManagerReplayMails::dispatch($ticketData, $admin->email)->onQueue('notifications');
                                    }
                                }
                            }
                        } else {
                            if ($ticket->myassignuser_id) {
                                $assignee = $ticket->ticketassignmutliples;
                                foreach ($assignee as $assignees) {
                                    $user = User::where('id', $assignees->toassignuser_id)->get();
                                    foreach ($user as $users) {
                                        if ($users->id == $assignees->toassignuser_id) {
                                            $users->notify(new TicketCreateNotifications($ticket));
                                            if ($users->usetting->emailnotifyon == 1) {
                                                ManagerReplayMails::dispatch($ticketData, $users->email)->onQueue('notifications');
                                            }
                                        }
                                    }
                                }
                                $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                                foreach ($admins as $admin) {
                                    if ($admin->getRoleNames()[0] == 'superadmin') {
                                        $admin->notify(new TicketCreateNotifications($ticket));
                                        if ($admin->usetting->emailnotifyon == 1) {
                                            ManagerReplayMails::dispatch($ticketData, $admin->email)->onQueue('notifications');
                                        }
                                    }
                                }
                            } else if ($ticket->selfassignuser_id) {

                                $self = User::findOrFail($ticket->selfassignuser_id);
                                $self->notify(new TicketCreateNotifications($ticket));
                                if ($self->usetting->emailnotifyon == 1) {

                                    ManagerReplayMails::dispatch($ticketData, $self->email)->onQueue('notifications');
                                }
                                $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                                foreach ($admins as $admin) {
                                    if ($admin->getRoleNames()[0] == 'superadmin') {
                                        $admin->notify(new TicketCreateNotifications($ticket));
                                        if ($admin->usetting->emailnotifyon == 1) {
                                            ManagerReplayMails::dispatch($ticketData, $admin->email)->onQueue('notifications');
                                        }
                                    }
                                }
                            } else if ($icc) {
                                $user = User::whereIn('id', $icc)->get();
                                foreach ($user as $users) {
                                    $users->notify(new TicketCreateNotifications($ticket));
                                    if ($users->usetting->emailnotifyon == 1) {
                                        ManagerReplayMails::dispatch($ticketData, $users->email)->onQueue('notifications');
                                    }
                                }
                                $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                                foreach ($admins as $admin) {
                                    if ($admin->getRoleNames()[0] == 'superadmin') {
                                        $admin->notify(new TicketCreateNotifications($ticket));
                                        if ($admin->usetting->emailnotifyon == 1) {
                                            ManagerReplayMails::dispatch($ticketData, $users->email)->onQueue('notifications');
                                        }
                                    }
                                }
                            } else {
                                $users = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                                foreach ($users as $user) {
                                    $user->notify(new TicketCreateNotifications($ticket));
                                    if ($user->usetting->emailnotifyon == 1) {
                                        ManagerReplayMails::dispatch($ticketData, $user->email)->onQueue('notifications');
                                    }
                                }
                            }
                        }
                    } else {
                        if ($ticket->myassignuser) {
                            $assignee = $ticket->ticketassignmutliples;
                            foreach ($assignee as $assignees) {
                                $user = User::where('id', $assignees->toassignuser_id)->get();
                                foreach ($user as $users) {
                                    if ($users->id == $assignees->toassignuser_id) {
                                        $users->notify(new TicketCreateNotifications($ticket));
                                        if ($users->usetting->emailnotifyon == 1) {
                                            ManagerReplayMails::dispatch($ticketData, $users->email)->onQueue('notifications');
                                        }
                                    }
                                }
                            }
                            $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                            foreach ($admins as $admin) {
                                if ($admin->getRoleNames()[0] == 'superadmin') {
                                    $admin->notify(new TicketCreateNotifications($ticket));
                                    if ($admin->usetting->emailnotifyon == 1) {
                                        ManagerReplayMails::dispatch($ticketData, $admin->email)->onQueue('notifications');
                                    }
                                }
                            }
                        } else if ($ticket->selfassignuser_id) {
                            $self = User::findOrFail($ticket->selfassignuser_id);
                            $self->notify(new TicketCreateNotifications($ticket));
                            if ($self->usetting->emailnotifyon == 1) {
                                ManagerReplayMails::dispatch($ticketData, $self->email)->onQueue('notifications');
                            }
                            $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                            foreach ($admins as $admin) {
                                if ($admin->getRoleNames()[0] == 'superadmin') {
                                    $admin->notify(new TicketCreateNotifications($ticket));
                                    if ($admin->usetting->emailnotifyon == 1) {
                                        ManagerReplayMails::dispatch($ticketData, $admin->email)->onQueue('notifications');
                                    }
                                }
                            }
                        } else {

                            $users = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                            foreach ($users as $user) {
                                $user->notify(new TicketCreateNotifications($ticket));
                                if ($user->usetting->emailnotifyon == 1) {
                                    ManagerReplayMails::dispatch($ticketData, $user->email)->onQueue('notifications');
                                }
                            }
                        }
                    }
                } else {
                    $user = User::where('id', $ticket->lastreply_mail)->get();
                    foreach ($user as $users) {
                        $users->notify(new TicketCreateNotifications($ticket));
                        if ($users->usetting->emailnotifyon == 1) {
                            ManagerReplayMails::dispatch($ticketData, $users->email)->onQueue('notifications');
                        }
                    }
                }
            }

            CustomerReplayMails::dispatch($ticketData, $ticketData->ticket_email)->onQueue('notifications');
            /* End customers reply to ticket notification and mail */
        } catch (\Exception $e) {
            return redirect()->back()->with("success", 'La respuesta al ticket fue exitosa.');
        }

    }

}

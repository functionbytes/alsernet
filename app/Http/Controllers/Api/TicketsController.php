<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\V1\Auth;
use App\Http\Controllers\Api\V1\CCMAILS;
use App\Http\Controllers\Api\V1\Holiday;
use App\Models\Group\Group;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategorie;
use App\Models\Ticket\TicketCustomField;
use App\Models\Ticket\TicketField;
use App\Models\Ticket\TicketHistory;
use App\Models\Ticket\TicketMail;
use App\Models\User;
use App\Notifications\TicketCreateNotifications;
use Carbon\Carbon;
use Illuminate\Http\Request;
use function App\Http\Controllers\Api\V1\lang;
use function App\Http\Controllers\Api\V1\usersdata;

class TicketsController extends ApiController
{



    public function store(Request $request)
    {
        // Validar los datos básicos
        $this->validate($request, [
            'subject' => 'required|max:255',
            'category' => 'required|exists:categories,id',
            'message' => 'required|no_script_tags',
        ]);


        $details = $request->except([
            'subject', 'category', 'message',
        ]);

        // Crear el ticket
        $ticket = Ticket::create([
            'subject' => $request->input('subject'),
            'cust_id' => Auth::guard('customer')->user()->id,
            'category_id' => $request->input('category'),
            'message' => $request->input('message'),
            'status_id' => 'New',
            'details' => $details,
        ]);

        $ticket->ticket_id = setting('CUSTOMER_TICKETID') . '-' . $ticket->id;
        $ticket->save();

        if (setting('AUTO_OVERDUE_TICKET') == 'no') {
            $ticket->auto_overdue_ticket = null;
        } else {
            if (setting('AUTO_OVERDUE_TICKET_TIME') == '0') {
                $ticket->auto_overdue_ticket = null;
            } else {
                if (Auth::guard('customer')->check() && Auth::guard('customer')->user()) {
                    if ($ticket->status == 'Closed') {
                        $ticket->auto_overdue_ticket = null;
                    } else {
                        $ticket->auto_overdue_ticket = now()->addDays(setting('AUTO_OVERDUE_TICKET_TIME'));
                    }
                }
            }
        }

        $category = TicketCategorie::find($request->category);
        $ticket->priority = $category->priority;
        if ($request->subscategory) {
            $ticket->subcategory = $request->subscategory;
        }
        $ticket->update();



        // Procesar cualquier lógica adicional específica, como notificaciones o adjuntos
        $this->processCustomFields($ticket, $request);

        $ccmails = new TicketMail();
        $ccmails->ticket_id = $ticket->id;
        $ccmails->ccemails = $request->ccmail;
        $ccmails->save();


        $tickethistory = new TicketHistory();
        $tickethistory->ticket_id = $ticket->id;
        $tickethistory->ticketnote = $ticket->ticketnote->isNotEmpty();
        $tickethistory->overduestatus = $ticket->overduestatus;
        $tickethistory->status = $ticket->status;
        $tickethistory->currentAction = 'Created';
        $tickethistory->username = $ticket->cust->username;
        $tickethistory->type = $ticket->cust->userType;
        $tickethistory->save();

        //foreach ($request->input('ticket', []) as $file) {
         //   $provider =  storage()->provider;
         //   $provider::mediaupload($ticket, 'uploads/ticket/' . $file, 'ticket');
        //}

        $request->session()->put('customerticket', Auth::guard('customer')->id());
        $ccemailsend = CCMAILS::where('ticket_id', $ticket->id)->first();

        $ticketData = [
            'ticket_username' => $ticket->cust->username,
            'ticket_id' => $ticket->ticket_id,
            'ticket_title' => $ticket->subject,
            'ticket_description' => $ticket->message,
            'ticket_status' => $ticket->status,
            'ticket_customer_url' => route('loadmore.load_data', encrypt($ticket->ticket_id)),
            'ticket_admin_url' => url('/admin/ticket-view/' . encrypt($ticket->ticket_id)),
        ];

        try {

            if($ticket->cust->phonesmsenable == 1 && $ticket->cust->phoneVerified == 1 && setting('twilioenable') == 'on'){
                dispatch((new SendSMS($ticket->cust->phone, 'created_ticket', $ticketData)));
            }

            $today = Carbon::today();
            $holidays = Holiday::whereDate('startdate', '<=', $today)->whereDate('enddate', '>=', $today)->where('status', '1')->get();

            if ($holidays->isNotEmpty() && setting('24hoursbusinessswitch') != 'on') {
                dispatch((new MailSend($ticket->cust->email, 'customer_send_ticket_created_that_holiday_or_announcement', $ticketData)));
                if($ccemailsend->ccemails != null){
                    dispatch((new MailSend($ccemailsend->ccemails, 'customer_send_ticket_created_that_holiday_or_announcement', $ticketData)));
                }
            } else {
                dispatch((new MailSend($ticket->cust->email, 'customer_send_ticket_created', $ticketData)));
                if($ccemailsend->ccemails != null){
                    dispatch((new MailSend($ccemailsend->ccemails, 'customer_send_ticket_created', $ticketData)));
                }
            }


            $notificationcat = $ticket->category->groupscategoryc()->get();
            $groupIds = $notificationcat->pluck('group_id')->toArray();
            $groupstatus = false;
            foreach ($groupIds as $groupid) {
                $groupexist = Group::where('groupstatus', '1')->find($groupid);
                if ($groupexist) {
                    $groupstatus = true;
                }
            }

            $icc = array();

            if ($groupstatus) {

                foreach ($notificationcat as $igc) {
                    $groups = $igc->groupsc()
                        ->where('groupstatus', 1)
                        ->with('groupsuser')
                        ->get();

                    foreach ($groups as $group) {
                        $users = $group->groupsuser;

                        foreach ($users as $user) {
                            $icc[] = $user->users_id;
                        }
                    }
                }

                if (!$icc) {
                    $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->where('users.status', 1)->get();
                    foreach ($admins as $admin) {
                        $admin->notify(new TicketCreateNotifications($ticket));
                        if ($admin->usetting->emailnotifyon == 1) {
                            dispatch((new MailSend($admin->email, 'admin_send_email_ticket_created', $ticketData)));
                        }
                    }
                } else {

                    $user = User::whereIn('id', $icc)->where('status', 1)->get();
                    foreach ($user as $users) {
                        $users->notify(new TicketCreateNotifications($ticket));
                        if ($users->usetting->emailnotifyon == 1) {
                            dispatch((new MailSend($users->email, 'admin_send_email_ticket_created', $ticketData)));
                        }
                    }
                    $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->where('users.status', 1)->get();
                    foreach ($admins as $admin) {
                        $admin->notify(new TicketCreateNotifications($ticket));
                        if ($admin->getRoleNames()[0] == 'superadmin' && $admin->usetting->emailnotifyon == 1) {
                            dispatch((new MailSend($admin->email, 'admin_send_email_ticket_created', $ticketData)));
                        }
                    }
                }
            } else {
                foreach (usersdata() as $admin) {
                    $admin->notify(new TicketCreateNotifications($ticket));
                    if ($admin->usetting->emailnotifyon == 1) {
                        dispatch((new MailSend($admin->email, 'admin_send_email_ticket_created', $ticketData)));
                    }
                }
            }
        } catch (\Exception $e) {

            return response()->json([
                'status' => true,
                'message' => lang('A ticket has been opened with the ticket ID', 'alerts') . $ticket->ticket_id,
                'ticket' => $ticket->id
            ], 200);
        }

        return response()->json([
            'status' => true,
            'message' => lang('A ticket has been opened with the ticket ID', 'alerts') . $ticket->ticket_id,
            'ticket' => $ticket->id
        ], 200);

    }


    private function processCustomFields(Ticket $ticket, $request)
    {
        $customFields = TicketField::where('status', 1)->whereIn('displaytypes', ['both', 'createticket'])->get();

        foreach ($customFields as $customfield) {
            $ticketcustomfield = new TicketCustomField();
            $ticketcustomfield->ticket_id = $ticket->id;
            $ticketcustomfield->fieldnames = $customfield->fieldnames;
            $ticketcustomfield->fieldtypes = $customfield->fieldtypes;
            $ticketcustomfield->fieldoptions = $customfield->fieldoptions;
            if ($customfield->fieldtypes == 'checkbox') {
                if ($request->input('custom_' . $customfield->id) != null) {

                    $string = implode(',', $request->input('custom_' . $customfield->id));
                    $ticketcustomfield->values = $string;
                }
            }
            if ($customfield->fieldtypes != 'checkbox') {
                if ($customfield->fieldprivacy == '1') {
                    $ticketcustomfield->privacymode = $customfield->fieldprivacy;
                    $ticketcustomfield->values = encrypt($request->input('custom_' . $customfield->id));
                } else {

                    $ticketcustomfield->values = $request->input('custom_' . $customfield->id);
                }
            }
            $ticketcustomfield->save();
        }


    }


}

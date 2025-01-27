<?php

namespace App\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TicketsSettingsController extends Controller
{

   public function index(){
        return view('managers.views.settings.tickets.setting')->with([
        ]);
   }


    public function update(Request $request){

        $data['customer_ticketid']  =  $request->customer_ticketid;
        $data['user_reopen_time']  =  $request->user_reopen_time;
        $data['maximum_allow_tickets']  =  $request->maximum_allow_tickets;
        $data['maximum_allow_hours']  =  $request->maximum_allow_hours;
        $data['maximum_allow_replies']  =  $request->maximum_allow_replies;
        $data['reply_allow_in_hours']  =  $request->reply_allow_in_hours;
        $data['auto_responsetime_ticket_time']  =  $request->auto_responsetime_ticket_time;
        $data['auto_close_ticket_time']  =  $request->auto_close_ticket_time;
        $data['auto_overdue_ticket_time']  =  $request->auto_overdue_ticket_time;
        $data['reply_edit_with_in_time']  =  $request->reply_edit_with_in_time;
        $data['trashed_ticket_delete_time']  =  $request->trashed_ticket_delete_time;
        $data['auto_notification_delete_days']  =  $request->auto_notification_deauto_notification_delete_dayslete_days;
        $data['ticket_character']  =  $request->ticket_character;
        $data['employee_protect_name']  =  $request->employee_protect_name;
        $data['restrict_to_create_ticket']  =  $request->restrict_to_create_ticket;
        $data['restrict_to_reply_ticket']  =  $request->restrict_to_reply_ticket;
        $data['auto_responsetime_ticket']  =  $request->auto_responsetime_ticket;
        $data['auto_close_ticket']  =  $request->auto_close_ticket;
        $data['user_reopen_issue']  =  $request->user_reopen_issue;
        $data['auto_overdue_ticket']  =  $request->auto_overdue_ticket;
        $data['restrict_reply_edit']  =  $request->restrict_reply_edit;
        $data['trashed_ticket_autodelete']  =  $request->trashed_ticket_autodelete;
        $data['auto_overdue_customer']  =  $request->auto_overdue_customer;
        $data['auto_notification_delete_enable']  =  $request->auto_notification_delete_enable;
        $data['customer_panel_employee_protect']  =  $request->customer_panel_employee_protect;
        $data['guest_ticket']  =  $request->guest_ticket;
        $data['note_create_mails']  =  $request->note_create_mails;
        $data['restict_to_delete_ticket']  =  $request->restict_to_delete_ticket;
        $data['user_file_upload_enable']  =  $request->user_file_upload_enable;
        $data['guest_file_upload_enable']  =  $request->guest_file_upload_enable;
        $data['guest_ticket_otp']  =  $request->guest_ticket_otp;
        $data['customer_ticket']  =  $request->customer_ticket;
        $data['ticket_rating']  =  $request->ticket_rating;
        $data['cc_email']  =  $request->cc_email;

        updateSettings($data);

        return response()->json([
            'success' => true,
            'message' => 'Se actualizado la configuración del ticket',
        ]);

    }

}

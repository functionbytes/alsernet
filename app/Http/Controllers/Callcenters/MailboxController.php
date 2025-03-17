<?php

namespace App\Http\Controllers\Callcenters;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Apptitle;
use App\Models\Footertext;
use App\Models\Seosetting;
use App\Models\User;
use App\Models\Customer;
use App\Models\Sendmail;
use App\Models\senduserlist;
use Auth;
use Session;
use DataTables;
use App\Notifications\CustomerCustomNotifications;
use App\Mail\AppMailer;
use Illuminate\Support\Str;

use Mail;
use App\Mail\mailmailablesend;


class MailboxController extends Controller
{
    public function index()
    {

        $this->authorize('Custom Notifications Access');

        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $customnotify = Sendmail::latest()->get();
        $data['customnotify'] = $customnotify;

        return view('admin.custom-notification.index')->with($data);
    }

    public function customercompose()
    {
        $this->authorize('Custom Notifications Customer');
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $user = Customer::where('userType','Customer')->get();
        $data['users'] = $user;

        return view('admin.custom-notification.customercompose')->with($data);
    }

    public function customercomposesend(Request $request)
    {
        $this->authorize('Custom Notifications Customer');
        $request->validate([
            'message' => 'required|max:60000',
            'subject' => 'required|max:255',
            'users' => 'required',
            'tag' => 'required|max:255',
            'selecttagcolor' => 'required',
        ]);
        $mailsend = new Sendmail();
        $mailsend->user_id = Auth::id();
        $mailsend->mailsubject = $request->input('subject');
        $mailsend->mailtext = $request->input('message');
        $mailsend->tag = $request->input('tag');
        $mailsend->selecttagcolor = $request->input('selecttagcolor');
        $mailsend->save();

        foreach($request->users as $value){
            senduserlist::create([
                'mail_id' => $mailsend->id,
                'tocust_id' => $value,
            ]);
        }

        $cust = Customer::find($request->users);

        foreach($cust as $value){

            $value->notify(new CustomerCustomNotifications($mailsend));
        }

        $ticketData = [
            'notification_subject' => $mailsend->mailsubject,
            'notification_message' => $mailsend->mailtext,
            'notification_tag' => $mailsend->tag,
        ];
        foreach($cust as $value){
            try{

                Mail::to($value->email)
                ->send( new mailmailablesend('when_send_customnotify_email_to_selected_member', $ticketData) );

            }catch(\Exception $e){
                return redirect('admin/customnotification')->with('success', lang('A custom notification was successfully sent to the support.', 'alerts'));
            }
        }


        return redirect('admin/customnotification')->with('success', lang('A custom notification was successfully sent to the support.', 'alerts'));

    }

    public function employeecompose()
    {
        $this->authorize('Custom Notifications Employee');
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $user = User::get();
        $data['users'] = $user;

        return view('admin.custom-notification.employeecompose')->with($data);
    }


    public function employeecomposesend(Request $request)
    {
        $this->authorize('Custom Notifications Employee');
        $request->validate([
            'message' => 'required|max:60000',
            'subject' => 'required|max:255',
            'users' => 'required',
            'tag' => 'required|max:255',
            'selecttagcolor' => 'required',
        ]);
        $mailsend = new Sendmail();
        $mailsend->user_id = Auth::id();
        $mailsend->mailsubject = $request->input('subject');
        $mailsend->mailtext = $request->input('message');
        $mailsend->tag = $request->input('tag');
        $mailsend->selecttagcolor = $request->input('selecttagcolor');
        $mailsend->save();

        foreach($request->users as $value){
            senduserlist::create([
                'mail_id' => $mailsend->id,
                'touser_id' => $value,
            ]);
        }
        $cust = User::find($request->users);
        foreach($cust as $value){

            $value->notify(new CustomerCustomNotifications($mailsend));
        }


        $ticketData = [
            'notification_subject' => $mailsend->mailsubject,
            'notification_message' => $mailsend->mailtext,
            'notification_tag' => $mailsend->tag,
        ];
        foreach($cust as $value){
            try{

                if($value->usetting->emailnotifyon == 1){
                    Mail::to($value->email)
                    ->send( new mailmailablesend('when_send_customnotify_email_to_selected_member', $ticketData) );
                }

            }catch(\Exception $e){
                return redirect('admin/customnotification')->with('success', lang('A custom notification was successfully sent to the employee.', 'alerts'));
            }
        }



        return redirect('admin/customnotification')->with('success', lang('A custom notification was successfully sent to the employee.', 'alerts'));


    }

    public function mailsent()
    {

        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $sendmails = Sendmail::latest()->get();
        $data['sendmails'] = $sendmails;

        return view('admin.custom-notification.sendmail')->with($data);
    }

    public function show($id){
        $this->authorize('Custom Notifications View');
        $custom = Sendmail::find($id);

        return response()->json($custom);
    }

    public function destroy($id)
    {
        $this->authorize('Custom Notifications Delete');
        $customdelete = Sendmail::find($id);
        $customdelete->touser()->delete();
        $customdelete->delete();

      return response()->json(['success'=> lang('"Custom notification" was successfully deleted.', 'alerts')]);
    }

    public function allnotifydelete(Request $request)
    {
        $id_array = $request->input('id');

        $sendmails = Sendmail::whereIn('id', $id_array)->get();

        foreach($sendmails as $sendmail){
            $sendmail->touser()->delete();
            $sendmail->delete();

        }
        return response()->json(['success'=> lang('"Custom notification" was successfully deleted.', 'alerts')]);

    }
}

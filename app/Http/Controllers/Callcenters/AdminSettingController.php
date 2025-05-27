<?php

namespace App\Http\Controllers\Callcenters;

use App\Http\Controllers\Controller;
use App\Http\Requests\SocialAuthRequest;
use App\Models\Apptitle;
use App\Models\Footertext;
use App\Models\Pages;
use App\Models\Seosetting;
use App\Models\Setting\Setting;
use App\Models\SocialAuthSetting;
use App\Models\Template;
use Illuminate\Http\Request;
use Mail;

class AdminSettingController extends Controller
{

    /**
     * Social Login Settings.
     *
     * @return \Illuminate\Http\Response
     */

    public function sociallogin() {
        $this->authorize('Social Logins Access');
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $credentials = SocialAuthSetting::first();
        $data['credentials'] = $credentials;

        return view('admin.generalsetting.socialloginsetting')->with($data);
    }

    /**
     * Social Login Settings.
     *
     * @return \Illuminate\Http\Response
     */

    public function socialloginupdate(SocialAuthRequest $request) {

        $socialAuth = SocialAuthSetting::first();

        $socialAuth->twitter_client_id = $request->twitter_client_id;
        $socialAuth->twitter_secret_id = $request->twitter_secret_id;
        ($request->twitter_status) ? $socialAuth->twitter_status = 'enable' : $socialAuth->twitter_status = 'disable';

        $socialAuth->facebook_client_id = $request->facebook_client_id;
        $socialAuth->facebook_secret_id = $request->facebook_secret_id;
        ($request->facebook_status) ? $socialAuth->facebook_status = 'enable' : $socialAuth->facebook_status = 'disable';

        $socialAuth->google_client_id = $request->google_client_id;
        $socialAuth->google_secret_id = $request->google_secret_id;
        ($request->google_status)  ? $socialAuth->google_status = 'enable' : $socialAuth->google_status = 'disable';

        $socialAuth->envato_client_id = $request->envato_client_id;
        $socialAuth->envato_secret_id = $request->envato_secret_id;
        ($request->envato_status) ? $socialAuth->envato_status = 'enable' : $socialAuth->envato_status = 'disable';

        $socialAuth->save();

        return back()->with('success', lang('Updated successfully', 'alerts'));
    }

    /**
     * Captcha Settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function captcha()
    {
        $this->authorize('Captcha Setting Access');
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        return view('admin.generalsetting.captchasetting')->with($data);
    }

    /**
     * Captcha Settings Save/Update.
     *
     * @return \Illuminate\Http\Response
     */
    public function captchastore(Request $request)
    {

        $this->validate($request, [
            'googlerecaptchakey' => 'required|max:10000',
            'googlerecaptchasecret' => 'required|max:10000',
        ]);
        $data['GOOGLE_RECAPTCHA_KEY'] = $request->googlerecaptchakey;
        $data['GOOGLE_RECAPTCHA_SECRET'] = $request->googlerecaptchasecret;

        $this->updateSettings($data);

        return back()->with('success',lang('Updated successfully', 'alerts'));
    }

    public function captchatypestore(Request $request){

        $data['captchatype'] = $request->captchatype;
        $this->updateSettings($data);
        return response()->json(['success' => lang('Updated successfully', 'alerts')]);
    }
    /**
     * Email Settings.
     *
     * @return \Illuminate\Http\Response
     */

    public function email(){

        $this->authorize('Email Setting Access');
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;


        return view('admin.email.email')->with($data);

    }
    /**
     * Ticket Settings.
     *
     * @return \Illuminate\Http\Response
     */

    public function ticketsetting()
    {
        $this->authorize('Ticket Setting Access');

        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;


        return view('admin.generalsetting.ticketsetting')->with($data);

    }

        /**
     * Ticket Settings Save/Update.
     *
     * @return \Illuminate\Http\Response
     */
    public function ticketsettingstore(Request $request)
    {
        $request->validate([
            'ticketid' => 'required',

        ]);
        if($request->userreopentime){
            $request->validate([
                'userreopentime' => 'required|numeric|gte:0'
            ]);
        }
        if($request->autoclosetickettime){
            $request->validate([
                'autoclosetickettime' => 'required|numeric|gte:0'
            ]);
        }
        if($request->autooverduetickettime){
            $request->validate([
                'autooverduetickettime' => 'required|numeric|gte:0'
            ]);
        }
        if($request->autoresponsetickettime){
            $request->validate([
                'autoresponsetickettime' => 'required|numeric|gte:0'
            ]);
        }
        if($request->autonotificationdeletedays){
            $request->validate([
                'autonotificationdeletedays' => 'required|numeric|gte:0'
            ]);
        }

        if($request->ticketcharacter){
            $request->validate([
                'ticketcharacter' => 'required|integer|between:10,255'
            ]);
        }
        if($request->employeeprotectname){
            $request->validate([
                'employeeprotectname' => 'required|max:255'
            ]);
        }

        $data['RESTRICT_TO_CREATE_TICKET']  =  $request->has('RESTRICT_TO_CREATE_TICKET') ? 'on' : 'off';
        $data['MAXIMUM_ALLOW_TICKETS']  =  $request->input('MAXIMUM_ALLOW_TICKETS') ;
        $data['MAXIMUM_ALLOW_HOURS']  =  $request->input('MAXIMUM_ALLOW_HOURS') ;
        $data['RESTRICT_TO_REPLY_TICKET']  =  $request->has('RESTRICT_TO_REPLY_TICKET') ? 'on' : 'off';
        $data['MAXIMUM_ALLOW_REPLIES']  =  $request->input('MAXIMUM_ALLOW_REPLIES') ;
        $data['REPLY_ALLOW_IN_HOURS']  =  $request->input('REPLY_ALLOW_IN_HOURS') ;
        $data['USER_REOPEN_ISSUE']  =  $request->has('USER_REOPEN_ISSUE') ? 'yes' : 'no';
        $data['USER_REOPEN_TIME']  =  $request->input('userreopentime') ;
        $data['AUTO_CLOSE_TICKET']  =  $request->has('AUTO_CLOSE_TICKET') ? 'yes' : 'no';
        $data['AUTO_CLOSE_TICKET_TIME']  =  $request->input('autoclosetickettime') ;
        $data['AUTO_OVERDUE_TICKET']  =  $request->has('AUTO_OVERDUE_TICKET') ? 'yes' : 'no';
        $data['AUTO_OVERDUE_TICKET_TIME']  =  $request->input('autooverduetickettime');
        $data['trashed_ticket_autodelete']  =  $request->has('trashed_ticket_autodelete') ? 'on' : 'off';
        $data['trashed_ticket_delete_time']  =  $request->input('trashed_ticket_delete_time');
        $data['AUTO_RESPONSETIME_TICKET']  =  $request->has('AUTO_RESPONSETIME_TICKET') ? 'yes' : 'no';
        $data['AUTO_RESPONSETIME_TICKET_TIME']  =  $request->input('autoresponsetickettime') ;
        $data['AUTO_NOTIFICATION_DELETE_ENABLE']  =  $request->has('AUTO_NOTIFICATION_DELETE_ENABLE') ? 'on' : 'off';
        $data['AUTO_NOTIFICATION_DELETE_DAYS']  =  $request->input('autonotificationdeletedays') ;
        $data['CUSTOMER_TICKETID']  =  $request->input('ticketid') ;
        $data['CUSTOMER_RESTICT_TO_DELETE_TICKET']  =  $request->has('CUSTOMER_RESTICT_TO_DELETE_TICKET') ? 'on' : 'off';
        $data['GUEST_TICKET']  =  $request->has('GUEST_TICKET') ? 'yes' : 'no';
        $data['NOTE_CREATE_MAILS']  =  $request->has('NOTE_CREATE_MAILS') ? 'on' : 'off';
        $data['PRIORITY_ENABLE']  =  $request->has('PRIORITY_ENABLE') ? 'yes' : 'no';
        $data['USER_FILE_UPLOAD_ENABLE']  =  $request->has('USER_FILE_UPLOAD_ENABLE') ? 'yes' : 'no';
        $data['GUEST_FILE_UPLOAD_ENABLE']  =  $request->has('GUEST_FILE_UPLOAD_ENABLE') ? 'yes' : 'no';
        $data['GUEST_TICKET_OTP']  =  $request->has('GUEST_TICKET_OTP') ? 'yes' : 'no';
        $data['CUSTOMER_TICKET']  =  $request->has('CUSTOMER_TICKET') ? 'yes' : 'no';
        $data['TICKET_CHARACTER']  =  $request->input('ticketcharacter');
        $data['customer_panel_employee_protect']  =  $request->has('customer_panel_employee_protect') ? 'on' : 'off';
        $data['employeeprotectname']  =  $request->input('employeeprotectname');
        $data['admin_reply_mail']  =  $request->has('admin_reply_mail') ? 'yes' : 'no';
        $data['ticketrating']  =  $request->has('ticket_rating') ? 'on' : 'off';
        $data['cc_email']  =  $request->has('cc_email') ? 'on' : 'off';

        $this->updateSettings($data);

        return back()->with('success', lang('Updated successfully', 'alerts'));
    }
    /**
     * Email Settings Save/Update.
     *
     * @return \Illuminate\Http\Response
     */

    public function emailStore(Request $request)
    {

        if($request->ajax()){

            if($request->mail_driver == 'sendmail'){
                $request->validate([
                    'mail_from_name' => 'required|max:10000',
                    'mail_from_address' => 'required|max:10000'
                ]);
            }
            if($request->mail_driver == 'smtp'){
                $request->validate([
                    'mail_host' => 'required|max:10000',
                    'mail_port' => 'required|numeric',
                    'mail_encryption' => 'required|max:10000',
                    'mail_username' => 'required|max:10000',
                    'mail_password' => 'required|max:10000',
                    'mail_from_name' => 'required|max:10000',
                    'mail_from_address' => 'required|max:10000'
                ]);
            }

            $data = $request->only(['mail_driver', 'mail_host', 'mail_port', 'mail_from_address', 'mail_from_name', 'mail_encryption', 'mail_username', 'mail_password']);

            $this->updateSettings($data);
            return response()->json(['code'=>200, 'success'=> lang('Updated successfully', 'alerts')], 200);
        }
    }

    /**
     * Email Settings Save/Update.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendTestMail(Request $request)
    {

        $email = $request->get('email');
        try{

            Mail::send('admin.email.template', [ 'emailBody' => "This is a test email sent by system" ], function($message) use ($email) {
            $message->to($email)->subject('Test Email');
            });

        return back()->with('success', lang('A test email was sent successfully.', 'alerts'));


        }catch(\Exception $e){
          return back()->with('error',  lang('The test email couldn’t be sent.', 'alerts'));
        }

    }


    /**
     * Email Settings.
     *
     * @return \Illuminate\Http\Response
     */

    public function emailtemplates()
    {
        $this->authorize('Email Template Access');
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $emailtemplates = Template::all();
        $data['emailtemplates'] = $emailtemplates;

        return view('admin.email.index')->with($data);
    }

     /**
     * Email Settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function emailtemplatesEdit($id)
    {
        $this->authorize('Email Template Edit');
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $template = Template::find($id);
        $data['template'] = $template;

        return view('admin.email.edit')->with($data);
    }

    public function emailtemplatesUpdate(Request $request, $id)
    {

        $request->validate([
            'subject' => 'required|max:255',
            'body' => 'required'
        ]);

        $template = Template::find($id)->update($request->only(['subject', 'body']));

        return redirect('/admin/emailtemplates')->with('success', lang('Updated successfully', 'alerts'));

    }

    public function announcementsetting(Request $request)
    {
        $data['ANNOUNCEMENT_USER']  =  $request->ANNOUNCEMENT_USER;

        $this->updateSettings($data);

        return back()->with('success', lang('Updated successfully', 'alerts'));
    }

    public function registerpopup(Request $request)
    {

        $socialAuth = SocialAuthSetting::first();

        $data['only_social_logins'] = $request->defaultsocialloginon;
        if($request->defaultsocialloginon == 'on'){
            if($socialAuth->twitter_status == 'enable' || $socialAuth->facebook_status == 'enable'|| $socialAuth->google_status == 'enable'|| $socialAuth->envato_status == 'enable'){
                $data['REGISTER_DISABLE'] = 'off';
                $data['REGISTER_POPUP'] = 'no';
            }else{
                return response()->json(['code'=>500, 'error'=> lang('Social logins are not enabled please enable it first', 'alerts')], 500);
            }
        }

        $data['REGISTER_POPUP'] = $request->status;
        $data['REGISTER_DISABLE'] = $request->registerdisable;
        $data['GOOGLEFONT_DISABLE'] = $request->googledisable;
        $data['FORCE_SSL'] = $request->forcessl;
        $data['DARK_MODE'] = $request->darkmode;
        $data['SPRUKOADMIN_P'] = $request->sprukoadminp;
        $data['SPRUKOADMIN_C'] = $request->sprukocustp;
        $data['ENVATO_ON'] = $request->envatoon;
        $data['purchasecode_on'] = $request->purchasecodeon;
        $data['defaultlogin_on'] = $request->defaultloginon;
        $data['article_count'] = $request->articlecount;
        $data['sidemenu_icon_style'] = $request->sidemenustyle;
        $data['login_disable'] = $request->logindisable;
        $data['cust_profile_delete_enable'] = $request->custdeleteprofile;
        $data['MAINTENANCE_MODE'] = $request->maintanancemode;

        $this->updateSettings($data);


        return response()->json(['code'=>200, 'success'=> lang('Updated successfully', 'alerts')], 200);

    }


    public function filesettingstore(Request $request)
    {

        $request->validate([
            'fileuploadmax' => 'required|numeric|gt:0',
            'fileuploadtypes' => 'required'
        ]);

        $data['MAX_FILE_UPLOAD']  =  $request->input('maxfileupload') ;
        $data['FILE_UPLOAD_MAX']  =  $request->input('fileuploadmax') ;
        $data['FILE_UPLOAD_TYPES']  =  $request->input('fileuploadtypes') ;

        $this->updateSettings($data);

        return back()->with('success', lang('Updated successfully', 'alerts'));
    }


    public function knowledge(Request $request)
    {

        $data['KNOWLEDGE_ENABLE']  =  $request->KNOWLEDGE_ENABLE;
        $data['FAQ_ENABLE']  =  $request->FAQ_ENABLE;
        $data['CONTACT_ENABLE']  =  $request->CONTACT_ENABLE;
        $data['enable_gpt']  =  $request->enable_gpt;

        $this->updateSettings($data);


        return response()->json(['code'=>200, 'success'=> lang('Updated successfully', 'alerts')], 200);
    }

    public function enablechatgpt(Request $request)
    {
        $data['openai_api'] = $request->openai_api;
        $this->updateSettings($data);

        return back()->with('success', lang('Updated successfully', 'alerts'));
    }

    public function profileuser(Request $request)
    {

        $data['PROFILE_USER_ENABLE']  =  $request->PROFILE_USER_ENABLE;

        $this->updateSettings($data);


        return response()->json(['code'=>200, 'success'=> lang('Updated successfully', 'alerts')], 200);
    }
    public function profileagent(Request $request)
    {

        $data['PROFILE_AGENT_ENABLE']  =  $request->PROFILE_AGENT_ENABLE;

        $this->updateSettings($data);


        return response()->json(['code'=>200, 'success'=> lang('Updated successfully', 'alerts')], 200);
    }

    public function captchacontact(Request $request)
    {

        $data['RECAPTCH_ENABLE_CONTACT']  =  $request->RECAPTCH_ENABLE_CONTACT;

        $this->updateSettings($data);


        return response()->json(['code'=>200, 'success'=> lang('Updated successfully', 'alerts')], 200);
    }

    public function captcharegister(Request $request)
    {

        $data['RECAPTCH_ENABLE_REGISTER']  =  $request->RECAPTCH_ENABLE_REGISTER;

        $this->updateSettings($data);


        return response()->json(['code'=>200, 'success'=> lang('Updated successfully', 'alerts')], 200);
    }
    public function captchalogin(Request $request)
    {

        $data['RECAPTCH_ENABLE_LOGIN']  =  $request->RECAPTCH_ENABLE_LOGIN;;

        $this->updateSettings($data);


        return response()->json(['code'=>200, 'success'=> lang('Updated successfully', 'alerts')], 200);
    }
    public function captchaadminlogin(Request $request)
    {

        $data['RECAPTCH_ENABLE_ADMIN_LOGIN']  =  $request->RECAPTCH_ENABLE_ADMIN_LOGIN;

        $this->updateSettings($data);


        return response()->json(['code'=>200, 'success'=> lang('Updated successfully', 'alerts')], 200);
    }

    public function captchaguest(Request $request)
    {

        $data['RECAPTCH_ENABLE_GUEST']  =  $request->RECAPTCH_ENABLE_GUEST;

        $this->updateSettings($data);


        return response()->json(['code'=>200, 'success'=> lang('Updated successfully', 'alerts')], 200);
    }


    /**
     * Frontend Settings Save/Update.
     *
     * @return \Illuminate\Http\Response
     */
    public function frontendStore(Request $request)
    {

        $request->validate([
            'theme_color' => 'required',
            'theme_color_dark' => 'required',
        ]);

        $data = $request->only(['theme_color', 'theme_color_dark']);

        $this->updateSettings($data);

        return back()->with('success',  lang('Updated successfully', 'alerts'));
    }


    public function googleanalytics()
    {
        $this->authorize('Google Analytics Access');
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;


        return view('admin.generalsetting.googleanalytics')->with($data);

    }

    /**
     * Googleanalytics Settings Save/Update.
     *
     * @return \Illuminate\Http\Response
     */
    public function googleanalyticsStore(Request $request)
    {

        $request->validate([
            'GOOGLE_ANALYTICS' => 'required',
        ]);
        $data['GOOGLE_ANALYTICS_ENABLE']  =  $request->has('GOOGLE_ANALYTICS_ENABLE') ? 'yes' : 'no';
        $data['GOOGLE_ANALYTICS'] = $request->input(['GOOGLE_ANALYTICS']);

        $this->updateSettings($data);

        return back()->with('success', lang('Updated successfully', 'alerts'));
    }

    public function languagesettingstore(Request $request)
    {

        $data = $request->only(['default_lang']);

        $this->updateSettings($data);

        return back()->with('success', lang('Updated successfully', 'alerts'));

    }

    public function seturl(Request $request)
    {

        $request->validate([
            'terms_url' => 'required',
        ]);

        $data = $request->only(['terms_url']);

        $this->updateSettings($data);

        return back()->with('success',  lang('Updated successfully', 'alerts'));

    }

    public function envatosetting()
    {

        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        return view('admin.envato.envatosetting')->with($data);
    }

    public function expiredsupport(Request $request)
    {
        $request->validate([
            'SUPPORT_POLICY_URL' => 'required|url',
        ]);

        $data['purchasecode_on']  =  $request->has('purchasecode_on') ? 'on' : 'off';
        $data['ENVATO_EXPIRED_BLOCK']  =  $request->has('ENVATO_EXPIRED_BLOCK') ? 'on' : 'off';
        $data['SUPPORT_POLICY_URL']  =  $request->input(['SUPPORT_POLICY_URL']);
        $this->updateSettings($data);

        return back()->with('success',  lang('Updated successfully', 'alerts'));

    }

    public function datetimeformatstore(Request $request)
    {

        $data['date_format']= $request->date_format;
        $data['time_format'] = $request->time_format;

        $this->updateSettings($data);

        return back()->with('success', lang('Updated successfully', 'alerts'));

    }

    public function startweekstore(Request $request)
    {

        $data['start_week']= $request->start_week;

        $this->updateSettings($data);

        return back()->with('success', lang('Updated successfully', 'alerts'));

    }


    public function timezoneupdate(Request $request)
    {
        $data['default_timezone']= $request->timezones;

        $this->updateSettings($data);

        return back()->with('success', lang('Updated successfully', 'alerts'));
    }

    public function bussinesshourtitle(Request $request)
    {
        $request->validate([
            'businesshourstitle' => 'required|max:255',
        ]);
        if($request->businesshourssubtitle)
        {
            $request->validate([
                'businesshourssubtitle' => 'max:255',
            ]);
        }
        $data['businesshourstitle'] = $request->businesshourstitle;
        $data['businesshourssubtitle'] = $request->businesshourssubtitle;
        $data['businesshoursswitch'] = $request->businesshoursswitch ? 'on' :'off';

        if($request->file('supporticon'))
        {
            $supportimage = $request->file('supporticon');
            $request->validate([
                'supporticon' => 'required|mimes:jpg,jpeg,png,svg|max:512',
            ]);
            //delete old file
            $supporticon = setting('supporticonimage');
            $imagepath = public_path() . "" . '/uploads/support/'. $supporticon;
            if(\File::exists($imagepath)){
                \File::delete($imagepath);
            }
            //insert new file
            $destinationPath = public_path() . "" . '/uploads/support/'; // upload path
            $profileImage = date('YmdHis') . "." . $supportimage->getClientOriginalExtension();
            $supportimage->move($destinationPath, $profileImage);
            $data['supporticonimage'] = "$profileImage";
        }
        $this->updateSettings($data);

        return back()->with('success', lang('Updated successfully', 'alerts'));
    }

    public function contactemail(Request $request)
    {

        $data['contact_form_mail']= $request->contact_form_mail;
        $this->updateSettings($data);

        return back()->with('success', lang('Updated successfully', 'alerts'));
    }

    public function logindisable(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
        ]);

        $data['login_disable_statement'] = $request->name;
        $this->updateSettings($data);

        return response()->json(['success' =>  lang('Updated successfully', 'alerts')], 200);

    }

    public function customerprofiledelete(Request $request)
    {
        $request->validate([
            'customer_inactive_notify_month' => 'required',
            'customer_inactive_days' => 'required',
        ]);

        $data['customer_inactive_notify']  =  $request->has('customer_inactive_notify') ? 'on' : 'off';
        $data['customer_inactive_notify_date']  =  $request->input('customer_inactive_notify_month');
        $data['customer_inactive_week_date']  =  $request->input('customer_inactive_days');
        $data['guest_inactive_notify']  =  $request->has('guest_inactive_notify') ? 'on' : 'off';
        $data['guest_inactive_notify_date']  =  $request->input('guest_inactive_notify_month');
        $data['guest_inactive_week_date']  =  $request->input('guest_inactive_days');

        $this->updateSettings($data);

        return back()->with('success', lang('Updated successfully', 'alerts'));

    }


    public function bussinesslogodelete(Request $request)
    {
        $data['supporticonimage']  =  null;

        $this->updateSettings($data);
        return response()->json(['success' =>  lang('Updated successfully', 'alerts')], 200);
    }



    /**
     *  Settings Save/Update.
     *
     * @return \Illuminate\Http\Response
     */
    private function updateSettings($data)
    {

        foreach($data as $key => $val){
        	$setting = Setting::where('key', $key);
        	if( $setting->exists() )
        		$setting->first()->update(['value' => $val]);
        }

    }
}

<?php

namespace App\Http\Controllers\Managers\Maillists;

use App\Events\MailListSubscription;
use App\Events\MailListUpdated;
use App\Http\Controllers\Controller;
use App\Models\Campaign\CampaignMaillist;
use App\Models\EmailVerificationServer;
use App\Models\Setting;
use App\Models\Subscriber\Subscriber;
use Exception;
use Illuminate\Http\Request;

class MaillistController extends Controller
{

    public function index(Request $request)
    {
        $maillists = CampaignMaillist::all();

        return view('managers.views.maillists.maillists.index', [
            'maillists' => $maillists,
        ]);
    }

    public function create(Request $request)
    {

        $list = new CampaignMaillist(['all_sending_servers' => true]);
        $list->subscribe_confirmation = false;
        $list->send_welcome_email = false;
        $list->unsubscribe_notification = false;

        if (null !== $request->old()) {
            $list->fill($request->old());
        }
        if (isset($request->old()['contact'])) {
            $list->contact->fill($request->old()['contact']);
        }

        // Sending servers
        // if (isset($request->old()['sending_servers'])) {
        //     $list->mailListsSendingServers = collect([]);
        //     foreach ($request->old()['sending_servers'] as $key => $param) {
        //         if ($param['check']) {
        //            $server = SendingServer::findByUid($key);
        //            $row = new MailListsSendingServer();
        //            $row->mail_list_id = $list->id;
        //            $row->sending_server_id = $server->id;
        //            $row->fitness = $param['fitness'];
        //            $list->mailListsSendingServers->push($row);
        //         }
        //     }
        // }

        $allowedSingleOptin = Setting::isYes('list.allow_single_optin');

        return view('managers.views.maillists.maillists.create', [
            'list' => $list,
            'allowedSingleOptin' => $allowedSingleOptin
        ]);

    }

    public function store(Request $request)
    {

        $list = new CampaignMaillist();

        // validate and save posted data
        if ($request->isMethod('post')) {

            //$this->validate($request, MailList::$rules);

            //$rules = [];
            //if (isset($request->sending_servers)) {
            //    foreach ($request->sending_servers as $key => $param) {
            //        if ($param['check']) {
            //            $rules['sending_servers.'.$key.'.fitness'] = 'required';
            //        }
            //    }
            // }
            //$this->validate($request, $rules);

            // Save contact
            $list->fill($request->all());
            $list->save();

            // For sending servers
            //if (isset($request->sending_servers)) {
            //   $list->updateSendingServers($request->sending_servers);
            //}

            // Trigger updating related campaigns cache
            event(new MailListUpdated($list));

            return response()->json([
                'success' => true,
                'uid' => $list->uid,
                'message' => 'Se creado al lista correctamente',
            ]);

        }
    }

    public function edit(Request $request, $uid)
    {

        $list = CampaignMaillist::findByUid($uid);

        if (null !== $request->old()) {
            $list->fill($request->old());
        }
        if (isset($request->old()['contact'])) {
            $list->contact->fill($request->old()['contact']);
        }

        // Sending servers
//        if (isset($request->old()['sending_servers'])) {
//            $list->mailListsSendingServers = collect([]);
//            foreach ($request->old()['sending_servers'] as $key => $param) {
//                if ($param['check']) {
//                    $server = SendingServer::findByUid($key);
//                    $row = new MailListsSendingServer();
//                    $row->mail_list_id = $list->id;
//                    $row->sending_server_id = $server->id;
//                    $row->fitness = $param['fitness'];
//                    $list->mailListsSendingServers->push($row);
//                }
//            }
//        }

        $allowedSingleOptin = Setting::isYes('list.allow_single_optin');

        return view('managers.views.maillists.maillists.edit', [
            'list' => $list,
            'allowedSingleOptin' => $allowedSingleOptin
        ]);
    }

    public function update(Request $request, $id)
    {

        $list = CampaignMaillist::findByUid($request->uid);

        if ($request->isMethod('post')) {
//            $this->validate($request, CampaignMaillist::$rules);

//            $rules = [];
//            if (isset($request->sending_servers)) {
//                foreach ($request->sending_servers as $key => $param) {
//                    if ($param['check']) {
//                        $rules['sending_servers.'.$key.'.fitness'] = 'required';
//                    }
//                }
//            }
//            $this->validate($request, $rules);

            $list->fill($request->all());
            $list->update();
//
//            if (isset($request->sending_servers)) {
//                $list->updateSendingServers($request->sending_servers);
//            }

            // Log
//           $list->log('updated', $request->user()->customer);


        }


        return response()->json([
            'success' => true,
            'uid' => $list->uid,
            'message' => 'Se actualizado al lista correctamente',
        ]);


    }

    public function deleteConfirm(Request $request)
    {
        $lists = CampaignMaillist::whereIn( 'uid',is_array($request->uids) ? $request->uids : explode(',', $request->uids));

        return view('managers.views.maillists.maillists.delete_confirm', [
            'lists' => $lists,
        ]);
    }

    public function delete(Request $request)
    {

        $lists = CampaignMaillist::whereIn('uid', is_array($request->uids) ? $request->uids : explode(',', $request->uids));

        foreach ($lists->get() as $item) {
                $item->delete();
                event(new MailListUpdated($item));
        }

        echo trans('messages.lists.deleted');
    }

    public function overview(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->uid);
        event(new MailListUpdated($list));

        return view('managers.views.maillists.maillists.overview', [
            'list' => $list,
        ]);
    }

    public function listGrowthChart(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->uid);

        if ($list) {
            $list_id = $list->id;
        } else {
            $list_id = null;
            $list = new CampaignMaillist();
            $list->customer_id = $request->user()->customer->id;
        }

        $result = [
            'columns' => [],
            'data' => [],
            'bar_names' => [trans('messages.subscriber_growth')],
        ];

        // columns
        for ($i = 4; $i >= 0; --$i) {
            $result['columns'][] = \Carbon\Carbon::now()->subMonthsNoOverflow($i)->format('m/Y');
        }

        // datas
        foreach ($result['bar_names'] as $bar) {
            for ($i = 4; $i >= 0; --$i) {
                $result['data'][] = Customer::subscribersCountByTime(
                    \Carbon\Carbon::now()->subMonthsNoOverflow($i)->startOfMonth(),
                    \Carbon\Carbon::now()->subMonthsNoOverflow($i)->endOfMonth(),
                    $request->user()->customer->id,
                    $list_id
                );
            }
        }

        return response()->json($result);
    }

    public function statisticsChart(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->uid);
        $customer = $request->user()->customer;

        if ($list) {
            $list_id = $list->id;
        } else {
            $list_id = null;
            $list = new CampaignMaillist();
            $list->customer_id = $request->user()->customer->id;
        }

        $result = [
            'data' => [],
        ];

        if (isset($list->id)) {
            if ($list->readCache('SubscribeCount', 0)) {
                $result['data'][] = ['value' => $list->readCache('SubscribeCount', 0), 'name' => trans('messages.subscribed')];
            }

            if ($list->readCache('UnsubscribeCount', 0)) {
                $result['data'][] = ['value' => $list->readCache('UnsubscribeCount', 0), 'name' => trans('messages.unsubscribed')];
            }

            if ($list->readCache('UnconfirmedCount', 0)) {
                $result['data'][] = ['value' => $list->readCache('UnconfirmedCount', 0), 'name' => trans('messages.unconfirmed')];
            }

            if ($list->readCache('BlacklistedCount', 0)) {
                $result['data'][] = ['value' => $list->readCache('BlacklistedCount', 0), 'name' => trans('messages.blacklisted')];
            }

            if ($list->readCache('SpamReportedCount', 0)) {
                $result['data'][] = ['value' => $list->readCache('SpamReportedCount', 0), 'name' => trans('messages.spam_reported')];
            }
        } else {
            if ($customer->readCache('SubscribedCount', 0)) {
                $result['data'][] = ['value' => $customer->readCache('SubscribedCount', 0), 'name' => trans('messages.subscribed')];
            }

            if ($customer->readCache('UnsubscribedCount', 0)) {
                $result['data'][] = ['value' => $customer->readCache('UnsubscribedCount', 0), 'name' => trans('messages.unsubscribed')];
            }

            if ($customer->readCache('UnconfirmedCount', 0)) {
                $result['data'][] = ['value' => $customer->readCache('UnconfirmedCount', 0), 'name' => trans('messages.unconfirmed')];
            }

            if ($customer->readCache('BlacklistedCount', 0)) {
                $result['data'][] = ['value' => $customer->readCache('BlacklistedCount', 0), 'name' => trans('messages.blacklisted')];
            }

            if ($customer->readCache('SpamReportedCount', 0)) {
                $result['data'][] = ['value' => $customer->readCache('SpamReportedCount', 0), 'name' => trans('messages.spam_reported')];
            }
        }

        // // datas
        // $result['data'][] = [
        //     'name' => trans('messages.statistics'),
        //     'type' => 'pie',
        //     'radius' => '70%',
        //     'center' => ['50%', '57.5%'],
        //     'data' => $datas
        // ];

        // $result['pie'] = 1;
        return response()->json($result);
    }

    public function quickView(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->uid);

        if (!$list) {
            $list = new CampaignMaillist();
            $list->uid = '000';
            $list->customer_id = $request->user()->customer->id;
        }

        return view('managers.views.maillists.maillists._quick_view', [
            'list' => $list,
        ]);
    }

    public function copy(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->copy_list_uid);


        if ($request->isMethod('post')) {
            // make validator
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
            ]);

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('managers.views.maillists.maillists.copy', [
                    'list' => $list,
                    'errors' => $validator->errors(),
                ], 400);
            }

            $list->copy($request->name);
            return trans('messages.list.copied');
        }

        return view('managers.views.maillists.maillists.copy', [
            'list' => $list,
        ]);
    }

    public function embeddedForm(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->uid);

        if ($request->isMethod('post')) {
            $list->setEmbeddedFormOptions($request->options);
        }

        return view('managers.views.maillists.maillists.embedded_form', [
            'list' => $list,
        ]);
    }

    public function embeddedFormFrame(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->uid);

        return view('managers.views.maillists.maillists.embedded_form_frame', [
            'list' => $list,
        ]);
    }

    public function embeddedFormCaptcha(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->uid);
        $request->session()->put('form_url', AppUrl::previous());

        return view('managers.views.maillists.maillists.embedded_form_captcha', [
            'list' => $list,
        ]);
    }

    public function embeddedFormSubscribe(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->uid);

        if (Setting::get('embedded_form_recaptcha') == 'yes') {
            if ($request->hasCaptcha) {
                // @hCaptcha
                if (Setting::getCaptchaProvider() == 'hcaptcha') {
                    $hcaptcha = \Acelle\Hcaptcha\Client::initialize();
                    $success = $hcaptcha->check($request);
                } else {
                    $success = \Acelle\Library\Tool::checkReCaptcha($request);
                }
            } else {
                return view('managers.views.maillists.maillists.embedded_form_captcha', [
                    'list' => $list,
                ]);
            }
        } else {
            $success = true;
        }

        // Check if list does exist
        if (!$list) {
            return view('somethingWentWrong', ['message' => trans('messages.embedded_form.list_not_exsit')]);
        }

        if (!$success) {
            $url = $request->session()->pull('form_url');
            $errs = [trans("messages.invalid_captcha")];
            return view('managers.views.maillists.maillists.embedded_form_captcha_invalid', [
                'errors' => collect($errs),
                'list' => $list,
                'back_link' => $url,
            ]);
        }

        try {
            // Create subscriber
            list($validator, $subscriber) = $list->subscribe($request, CampaignMaillist::SOURCE_EMBEDDED_FORM);
        } catch (\Exception $ex) {
            return view('somethingWentWrong', ['message' => $ex->getMessage()]);
        }


        if ($validator->fails()) {
            $url = $request->session()->pull('form_url');
            // $validator->errors()
            $errs = [];
            foreach ($validator->errors()->toArray() as $key => $error) {
                $field = $list->getFieldByTag($key);
                $errs[] = $error[0];
            }

            if (strpos($url, '?') !== false) {
                $url = $url . "&" . implode('&', $errs);
            } else {
                $url = $url . "?" . implode('&', $errs);
            }

            // return redirect()->away($url);
            return view('managers.views.maillists.maillists.embedded_form_errors', [
                'errors' => collect($errs),
                'list' => $list,
                'back_link' => $url,
            ]);
        }

        // custom redirect
        if ($request->redirect_url) {
            return redirect()->away($request->redirect_url);
        } elseif ($list->subscribe_confirmation && !$subscriber->isSubscribed()) {
            // tell subscriber to check email for confirmation
            return redirect()->action('PageController@signUpThankyouPage', ['list_uid' => $list->uid, 'subscriber_uid' => $subscriber->uid]);
        } else {
            // All done, confirmed
            return redirect()->action(
                'PageController@signUpConfirmationThankyou',
                [
                    'list_uid' => $list->uid,
                    'uid' => $subscriber->uid,
                    'code' => 'empty',
                ]
            );
        }
    }

    public function verification(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->uid);
        $currentJob = $list->verificationJobs()->first();

        if ($currentJob) {
            return view('managers.views.maillists.maillists.email_verification', [
                'list' => $list,
                'currentJobUid' => $currentJob->uid,
                'cancelUrl' => action('MailListController@stopVerification', [ 'uid' => $list->uid, 'job_uid' => $currentJob->uid ]),
                'progressCheckUrl' => action('MailListController@verificationProgress', [ 'uid' => $list->uid, 'job_uid' => $currentJob->uid ]),
            ]);
        } else {
            return view('managers.views.maillists.maillists.email_verification', [
                'list' => $list,
            ]);
        }
    }

    public function startVerification(Request $request)
    {
        // Get list & server
        $list = CampaignMaillist::findByUid($request->uid);
        $server = EmailVerificationServer::findByUid($request->email_verification_server_id);
        $customer = $request->user()->customer;

        // Dispatch
        if (config('app.saas')) {
            $subscription = $customer->getCurrentActiveGeneralSubscription(); // saas safe
        } else {
            $subscription = null;
        }

        $job = $list->dispatchVerificationJob($server, $subscription);

        // Return
        return response()->json([
            'currentJobUid' => $job->uid,
            'cancelUrl' => action('MailListController@stopVerification', [ 'uid' => $list->uid, 'job_uid' => $job->uid ]),
            'progressCheckUrl' => action('MailListController@verificationProgress', [ 'uid' => $list->uid, 'job_uid' => $job->uid ]),
        ]);
    }

    public function stopVerification(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->uid);
        $job = $list->verificationJobs()->where('uid', $request->job_uid)->first();

        if (is_null($job)) {
            throw new Exception(sprintf('Verification job #%s does not exist', $request->job_uid));
        }

        $job->cancel();
        return response()->json();
    }

    public function resetVerification(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->uid);
        $list->resetVerification();
        return redirect()->action('MailListController@verification', $list->uid);
    }

    public function verificationProgress(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->uid);
        $job = $list->verificationJobs()->where('uid', $request->job_uid)->first();

        if (is_null($job)) {
            throw new Exception(sprintf('Verification job #%s does not exist', $request->job_uid));
        }

        $progress = $list->getProgress($job);

        return response()->json($progress);
    }

    public function checkEmail(Request $request)
    {
        header("Access-Control-Allow-Origin: *");

        $list = CampaignMaillist::findByUid($request->uid);
        $subscriber = $list->subscribers()->where('email', '=', strtolower(trim($request->EMAIL)))->first();

        if ($subscriber && $subscriber->status == Subscriber::STATUS_SUBSCRIBED) {
            $result = trans('messages.email_already_subscribed');
        } else {
            $result = true;
        }

        return response()->json($result);
    }


    public function selectList(Request $request)
    {
        if ($request->isMethod('post')) {
            // make validator
            $validator = \Validator::make($request->all(), [
                'list_uid' => 'required',
            ]);

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('managers.views.maillists.maillists.selectList', [
                    'errors' => $validator->errors(),
                ], 400);
            }

            $url = str_replace('list_uid', $request->list_uid, $request->redirect);
            return response()->json([
                'url' => $url,
            ]);
        }

        return view('managers.views.maillists.maillists.selectList');
    }

    public function emailVerificationChart(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->uid);


        $result = [
            'data' => [
                [
                    'name' => trans('messages.email_verification_result_deliverable'),
                    'value' => $list->subscribers()->deliverable()->count(),
                ],
                [
                    'name' => trans('messages.email_verification_result_undeliverable'),
                    'value' => $list->subscribers()->undeliverable()->count(),
                ],
                [
                    'name' => trans('messages.email_verification_result_risky'),
                    'value' =>  $list->subscribers()->risky()->count(),
                ],
                [
                    'name' => trans('messages.email_verification_result_unverified'),
                    'value' => $list->subscribers()->unverified()->count(),
                ],
                [
                    'name' => trans('messages.email_verification_result_unknown'),
                    'value' => $list->subscribers()->unknown()->count(),
                ],
            ],
        ];

        // usort($result['data'], function ($a, $b) {
        //     return strcmp($a['value'], $b['value']);
        // });
        // $result['data'] = array_reverse($result['data']);

        return response()->json($result);
    }

}

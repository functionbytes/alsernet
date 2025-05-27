<?php

namespace App\Http\Controllers\Managers\Maillists;

use App\Http\Controllers\Controller;
use App\Models\Campaign\CampaignMaillist;
use App\Models\Subscriber\Subscriber;
use App\Models\Subscriber\SubscriberList;
use Illuminate\Http\Request;
use App\Models\EmailVerificationServer;
use App\Models\Campaign\CampaignMaillistsSubscriber;
use App\Models\Segment;
use App\Models\JobMonitor;
use App\Models\Setting;
use App\Library\Facades\Hook;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriberController extends Controller
{

    public function search($list, $request)
    {
        $customer = $request->user()->customer;
        $subscribers = $customer->subscribers()
            ->search($request->keyword)
            ->filter($request)
            ->orderBy($request->sort_order ? $request->sort_order : 'created_at', $request->sort_direction ? $request->sort_direction : 'asc')
            ->where('mail_list_id', '=', $list->id);

        return $subscribers;
    }

    public function index(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);
        $subscribers = $list->subscribers();

        if (!$list) {
            return redirect()->route('SubscriberController@noList');
        }

        $subscribers = $subscribers->paginate(100);

        return view('managers.views.maillists.subscribers.index', [
            'list' => $list,
            'subscribers' => $subscribers
        ]);

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listing(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);

        // authorize
        if (\Gate::denies('read', $list)) {
            return;
        }

        $subscribers = $this->search($list, $request);
        // $total = distinctCount($subscribers);
        $total = $subscribers->count();
        $subscribers->with(['mailList', 'subscriberFields']);
        $subscribers = $subscribers->paginate($request->per_page ? $request->per_page : 50);

        $fields = $list->getFields->whereIn('uid', $request->columns);

        return view('managers.views.maillists.subscribers._list', [
            'subscribers' => $subscribers,
            'total' => $total,
            'list' => $list,
            'fields' => $fields,
        ]);
    }

    public function create(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);
        $subscriber = new Subscriber();
        $subscriber->mail_list_id = $list->id;

        $values = [];
        if (null !== $request->old()) {
            foreach ($request->old() as $key => $value) {
                if (is_array($value)) {
                    $values[str_replace('[]', '', $key)] = implode(',', $value);
                } else {
                    $values[$key] = $value;
                }
            }
        }

        return view('managers.views.maillists.subscribers.create', [
            'list' => $list,
            'subscriber' => $subscriber,
            'values' => $values,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);
        $customer = $request->user()->customer;

        if (!$customer->user->can('addMoreSubscribers', [ $list, $more = 1 ])) {
            return $this->noMoreItem();
        }

        // Validate & and create subscriber
        // Throw ValidationError exception in case of failure
        list($validator, $subscriber) = $list->subscribe($request, Subscriber::SUBSCRIPTION_TYPE_ADDED);

        // @IMPORTANT: do not use $validator->fails() again,
        // if validation runs again, it is now TRUE! subscriber's email inserted => no longer unique
        if (is_null($subscriber)) {
            return back()->withInput()->withErrors($validator);
        }

        // Redirect to my lists page
        $request->session()->flash('alert-success', trans('messages.subscriber.created'));
        return redirect()->route('SubscriberController@index', $list->uid);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);
        $subscriber = Subscriber::findByUid($request->uid);

        // Get old post values
        $values = [];
        foreach ($list->getFields as $key => $field) {
            $values[$field->tag] = $subscriber->getValueByField($field);
        }
        if (null !== $request->old()) {
            foreach ($request->old() as $key => $value) {
                if (is_array($value)) {
                    $values[str_replace('[]', '', $key)] = implode(',', $value);
                } else {
                    $values[$key] = $value;
                }
            }
        }

        return view('managers.views.maillists.subscribers.edit', [
            'list' => $list,
            'subscriber' => $subscriber,
            'values' => $values,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $customer = $request->user()->customer;
        $list = CampaignMaillist::findByUid($request->list_uid);
        $subscriber = Subscriber::findByUid($request->uid);


        // validate and save posted data
        if ($request->isMethod('patch')) {
            $this->validate($request, $subscriber->getRules());

            // Upload
            if ($request->hasFile('image')) {
                if ($request->file('image')->isValid()) {
                    // Remove old images
                    $subscriber->uploadImage($request->file('image'));
                }
            }
            // Remove image
            if ($request->_remove_image == 'true') {
                $subscriber->removeImage();
            }

            // Update field
            $subscriber->updateFields($request->all());

            event(new \Acelle\Events\MailListUpdated($subscriber->mailList));

            // Log
            $subscriber->log('updated', $customer);

            // Redirect to my lists page
            $request->session()->flash('alert-success', trans('messages.subscriber.updated'));

            return redirect()->route('SubscriberController@index', $list->uid);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $customer = $request->user()->customer;
        $uids = $request->uids;

        if (!is_array($request->uids)) {
            $uids = explode(',', $request->uids);
        }
        $subscribers = Subscriber::whereIn('uid', $uids);
        $list = CampaignMaillist::findByUid($request->list_uid);

        // Select all items
        if ($request->select_tool == 'all_items') {
            $subscribers = $this->search($list, $request);
        }

        // get related mail lists to update the cached information
        $lists = $subscribers->get()->map(function ($e) {
            return MailList::find($e->mail_list_id);
        })->unique();

        // actually delete the subscriber
        foreach ($subscribers->get() as $subscriber) {
            // authorize
            if (\Gate::allows('delete', $subscriber)) {
                $subscriber->delete();

                // Log
                $subscriber->log('deleted', $customer);
            }
        }

        foreach ($lists as $list) {
            event(new \Acelle\Events\MailListUpdated($list));
        }

        // Redirect to my lists page
        return response()->json([
            "status" => 'success',
            "message" => trans('messages.subscribers.deleted'),
        ]);
    }

    /**
     * Subscribe subscriber.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function subscribe(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);
        $customer = $request->user()->customer;

        if ($request->select_tool == 'all_items') {
            $subscribers = $this->search($list, $request);
        } else {
            $subscribers = Subscriber::whereIn(
                'uid',
                is_array($request->uids) ? $request->uids : explode(',', $request->uids)
            );
        }

        foreach ($subscribers->get() as $subscriber) {
            // authorize
            if (\Gate::allows('subscribe', $subscriber)) {
                $subscriber->subscribe([
                    'message_id' => null,
                    'user_agent' => array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : '#unknown',
                ]);

                // update MailList cache
                event(new \Acelle\Events\MailListUpdated($subscriber->mailList));

                // Log
                $subscriber->log('subscribed', $customer);
            }
        }

        // Redirect to my lists page
        echo trans('messages.subscribers.subscribed');
    }

    /**
     * Unsubscribe subscriber.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function unsubscribe(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);
        $customer = $request->user()->customer;

        if ($request->select_tool == 'all_items') {
            $subscribers = $this->search($list, $request);
        } else {
            $subscribers = Subscriber::whereIn(
                'uid',
                is_array($request->uids) ? $request->uids : explode(',', $request->uids)
            );
        }

        foreach ($subscribers->get() as $subscriber) {
            // authorize
            if (\Gate::allows('unsubscribe', $subscriber)) {
                $subscriber->unsubscribe([
                    'message_id' => null,
                    'user_agent' => array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : '#unknown',
                ]);

                // Log
                $subscriber->log('unsubscribed', $customer);

                // update MailList cache
                event(new \Acelle\Events\MailListUpdated($subscriber->mailList));
            }
        }

        // Redirect to my lists page
        echo trans('messages.subscribers.unsubscribed');
    }

    public function import(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);
        $currentJob = $list->importJobs()->first();

        $importNotifications = Hook::execute('list_import_notifications');

        if ($currentJob) {
            return view('managers.views.maillists.subscribers.import', [
                'list' => $list,
                'currentJobUid' => $currentJob->uid,
                'progressCheckUrl' => route('manager.campaigns.maillists.import.progress', ['job_uid' => $currentJob->uid, 'list_uid' => $list->uid]),
                'cancelUrl' => route('manager.campaigns.maillists.import.cancel', ['job_uid' => $currentJob->uid]),
                'logDownloadUrl' => route('manager.campaigns.maillists.import.log.download', ['job_uid' => $currentJob->uid]),
                'importNotifications' => $importNotifications,
            ]);
        } else {
            return view('managers.views.maillists.subscribers.import', [
                'list' => $list,
                'importNotifications' => $importNotifications,
            ]);
        }
    }


    public function dispatchImportJob(Request $request)
    {

        $list = CampaignMaillist::findByUid($request->list_uid);

        $filepath = $list->uploadCsv($request->file('file'));

        Hook::registerIfEmpty('dispatch_list_import_job', function ($list, $filepath) use ($request) {
            return $list->dispatchImportJob($filepath, $map = $request->input('mapping'));
        });

        $currentJob = Hook::perform('dispatch_list_import_job', [$list, $filepath]);

        return response()->json([
            'currentJobUid' => $currentJob->uid,
            'progressCheckUrl' => route('manager.campaigns.maillists.import.progress', ['job_uid' => $currentJob->uid, 'list_uid' => $list->uid]),
            'cancelUrl' => route('manager.campaigns.maillists.import.cancel', ['job_uid' => $currentJob->uid]),
            'logDownloadUrl' => route('manager.campaigns.maillists.import.log.download', ['job_uid' => $currentJob->uid]),
        ]);

    }

    public function cancelImport(Request $request)
    {
        $job = JobMonitor::findByUid($request->job_uid);

        try {
            $job->cancel();
            return response()->json(['status' => 'done']);
        } catch (\Exception $ex) {
            $job->delete(); // delete anyway if already done or failed, to make it simple to user
            return response()->json(['status' => '']);
        }
    }

    public function cancelExport(Request $request)
    {
        $job = JobMonitor::findByUid($request->job_uid);

        try {
            $job->cancel();
            return response()->json(['status' => 'done']);
        } catch (\Exception $ex) {
            $job->delete(); // delete anyway if already done or failed, to make it simple to user
            return response()->json(['status' => '']);
        }
    }

    public function downloadImportLog(Request $request)
    {
        $job = JobMonitor::findByUid($request->job_uid);

        // Only available if job has moved out of queued status
        return response()->download($job->getJsonData()['logfile']);
    }



    public function lists(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);
        $currentJob = $list->importJobs()->first();

        $importNotifications = Hook::execute('list_import_notifications');
        $listssubscribers = SubscriberList::orderBy('title' , 'desc')->pluck('title','id');

        if ($currentJob) {
            return view('managers.views.maillists.subscribers.importlists', [
                'list' => $list,
                'listssubscribers' => $listssubscribers,
                'currentJobUid' => $currentJob->uid,
                'progressCheckUrl' => route('manager.campaigns.maillists.import.progress', ['job_uid' => $currentJob->uid, 'list_uid' => $list->uid]),
                'cancelUrl' => route('manager.campaigns.maillists.import.cancel', ['job_uid' => $currentJob->uid]),
                'logDownloadUrl' => route('manager.campaigns.maillists.import.log.download', ['job_uid' => $currentJob->uid]),
                'importNotifications' => $importNotifications,
            ]);
        } else {
            return view('managers.views.maillists.subscribers.importlists', [
                'list' => $list,
                'listssubscribers' => $listssubscribers,
                'importNotifications' => $importNotifications,
            ]);
        }
    }


    public function dispatchImportListsJobs(Request $request)
    {

        $list = CampaignMaillist::findByUid($request->list_uid);

        $filepath = $request->hasFile('file') ? $list->uploadCsv($request->file('file')) : null;
        $lists = $request->input('lists', []);

        Hook::registerIfEmpty('dispatch_list_import_lists_job', function ($list, $filepath) use ($lists) {
            return $list->dispatchImportListsJob($filepath, $lists);
        });

        $currentJob = Hook::perform('dispatch_list_import_lists_job', [$list, $filepath]);

        return response()->json([
            'currentJobUid' => $currentJob->uid,
            'progressCheckUrl' => route('manager.campaigns.maillists.import.progress.lists', ['job_uid' => $currentJob->uid, 'list_uid' => $list->uid]),
            'cancelUrl' => route('manager.campaigns.maillists.import.cancel.lists', ['job_uid' => $currentJob->uid]),
            'logDownloadUrl' => route('manager.campaigns.maillists.import.log.download.lists', ['job_uid' => $currentJob->uid]),
        ]);

    }

    public function cancelImportLists(Request $request)
    {
        $job = JobMonitor::findByUid($request->job_uid);

        try {
            $job->cancel();
            return response()->json(['status' => 'done']);
        } catch (\Exception $ex) {
            $job->delete(); // delete anyway if already done or failed, to make it simple to user
            return response()->json(['status' => '']);
        }
    }

    public function cancelExportLists(Request $request)
    {
        $job = JobMonitor::findByUid($request->job_uid);

        try {
            $job->cancel();
            return response()->json(['status' => 'done']);
        } catch (\Exception $ex) {
            $job->delete(); // delete anyway if already done or failed, to make it simple to user
            return response()->json(['status' => '']);
        }
    }

    public function downloadImportListsLog(Request $request)
    {
        $job = JobMonitor::findByUid($request->job_uid);
        return response()->download($job->getJsonData()['logfile']);
    }

    public function downloadExportedFile(Request $request)
    {
        $job = JobMonitor::findByUid($request->job_uid);
        return response()->download($job->getJsonData()['filepath']);
    }

    /**
     * Check import proccessing.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function importProgress(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);
        $job = $list->importJobs()->first();

        $progress = $list->getProgress($job);

        // Get progress updated by the import process and status of the final job monitor
        return response()->json($progress);
    }

    public function importListsProgress(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);
        $job = $list->importListsJobs()->first();

        $progress = $list->getProgress($job);

        // Get progress updated by the import process and status of the final job monitor
        return response()->json($progress);
    }

    /**
     * Export to csv.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);

        $currentJob = $list->exportJobs()->first();

        // GET, has a current job
        if ($currentJob) {
            return view('managers.views.maillists.subscribers.export', [
                'list' => $list,
                'currentJobUid' => $currentJob->uid,
                'progressCheckUrl' => route('manager.campaigns.maillists.export.progress', ['job_uid' => $currentJob->uid]),
                'cancelUrl' => route('manager.campaigns.maillists.export.cancel', ['job_uid' => $currentJob->uid]),
                'downloadUrl' => route('manager.campaigns.maillists.export.log.download', ['job_uid' => $currentJob->uid]),
            ]);
            // GET, do not have any job
        } else {
            return view('managers.views.maillists.subscribers.export', [
                'list' => $list
            ]);
        }
    }

    public function dispatchExportJob(Request $request)
    {
        // Get the list
        $list = CampaignMaillist::findByUid($request->list_uid);

        $segmentUid = $request->input('segment_uid');
        $segment = (is_null($segmentUid)) ? null : Segment::findByUid($segmentUid);

        $currentJob = $list->dispatchExportJob($segment);

        return response()->json([
            'currentJobUid' => $currentJob->uid,
            'progressCheckUrl' => route('manager.campaigns.maillists.export.progress', ['job_uid' => $currentJob->uid]),
            'cancelUrl' => route('manager.campaigns.maillists.export.cancel', ['job_uid' => $currentJob->uid]),
            'downloadUrl' => route('manager.campaigns.maillists.export.log.download', ['job_uid' => $currentJob->uid]),
        ]);
    }

    /**
     * Check export proccessing.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function exportProgress(Request $request)
    {
        $job = JobMonitor::findByUid($request->job_uid);

        // Get progress updated by the import process and status of the final job monitor
        $progress = $job->getJsonData();
        $progress['status'] = $job->status;
        $progress['error'] = $job->error;

        return response()->json($progress);
    }

    /**
     * Copy subscribers to lists.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function copy(Request $request)
    {
        $from_list = CampaignMaillist::findByUid($request->from_uid);
        $to_list = CampaignMaillist::findByUid($request->to_uid);

        if ($request->select_tool == 'all_items') {
            $subscribers = $this->search($from_list, $request)->select('subscribers.*');
        } else {
            $subscribers = Subscriber::whereIn(
                'uid',
                is_array($request->uids) ? $request->uids : explode(',', $request->uids)
            );
        }

        foreach ($subscribers->get() as $subscriber) {
            // authorize
            if (\Gate::allows('update', $to_list)) {
                $subscriber->copy($to_list);
            }
        }

        // Trigger updating related campaigns cache
        event(new \Acelle\Events\MailListUpdated($to_list));

        // Log
        $to_list->log('copied', $request->user()->customer, [
            'count' => $subscribers->count(),
            'from_uid' => $from_list->uid,
            'to_uid' => $to_list->uid,
            'from_name' => $from_list->name,
            'to_name' => $to_list->name,
        ]);

        // Redirect to my lists page
        echo trans('messages.subscribers.copied');
    }

    /**
     * Move subscribers to lists.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function move(Request $request)
    {
        $from_list = CampaignMaillist::findByUid($request->from_uid);
        $to_list = CampaignMaillist::findByUid($request->to_uid);

        if ($request->select_tool == 'all_items') {
            $subscribers = $this->search($from_list, $request)->select('subscribers.*');
        } else {
            $subscribers = Subscriber::whereIn(
                'uid',
                is_array($request->uids) ? $request->uids : explode(',', $request->uids)
            );
        }

        foreach ($subscribers->get() as $subscriber) {
            // authorize
            if (\Gate::allows('update', $to_list)) {
                $subscriber->move($to_list);
            }
        }

        // Trigger updating related campaigns cache
        event(new \Acelle\Events\MailListUpdated($from_list));
        event(new \Acelle\Events\MailListUpdated($to_list));

        // Log
        $to_list->log('moved', $request->user()->customer, [
            'count' => $subscribers->count(),
            'from_uid' => $from_list->uid,
            'to_uid' => $to_list->uid,
            'from_name' => $from_list->name,
            'to_name' => $to_list->name,
        ]);

        // Redirect to my lists page
        echo trans('messages.subscribers.moved');
    }

    /**
     * Copy Move subscribers form.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function copyMoveForm(Request $request)
    {
        $from_list = CampaignMaillist::findByUid($request->from_uid);

        if ($request->select_tool == 'all_items') {
            $subscribers = $this->search($from_list, $request);
        } else {
            $subscribers = Subscriber::whereIn(
                'uid',
                is_array($request->uids) ? $request->uids : explode(',', $request->uids)
            );
        }

        return view('managers.views.maillists.subscribers.copy_move_form', [
            'subscribers' => $subscribers,
            'from_list' => $from_list
        ]);
    }

    /**
     * Start the verification process
     *
     */
    public function startVerification(Request $request)
    {
        $subscriber = Subscriber::findByUid($request->uid);
        $server = EmailVerificationServer::findByUid($request->email_verification_server_id);
        try {
            $subscriber->verify($server);

            // success message
            $request->session()->flash('alert-success', trans('messages.verification.finish'));

            // update MailList cache
            event(new \Acelle\Events\MailListUpdated($subscriber->mailList));

            return redirect()->route('SubscriberController@edit', ['list_uid' => $request->list_uid, 'uid' => $subscriber->uid]);
        } catch (\Exception $e) {
            return view('managers.views.maillists.somethingWentWrong', ['message' => sprintf("Something went wrong while verifying %s (%s). Error message: %s", $subscriber->email, $subscriber->id, $e->getMessage())]);
        }
    }

    /**
     * Reset the verification data
     *
     */
    public function resetVerification(Request $request)
    {
        $subscriber = Subscriber::findByUid($request->uid);

        try {
            $subscriber->resetVerification();
            // success message
            $request->session()->flash('alert-success', trans('messages.verification.reset'));

            return redirect()->route('SubscriberController@edit', ['list_uid' => $request->list_uid, 'uid' => $subscriber->uid]);
        } catch (\Exception $e) {
            return view('managers.views.maillists.somethingWentWrong', ['message' => sprintf("Something went wrong while cleaning up verification data for %s (%s). Error message: %s", $subscriber->email, $subscriber->id, $e->getMessage())]);
        }
    }

    /**
     * Render customer image.
     */
    public function avatar(Request $request)
    {
        // Get current customer
        if ($request->uid != '0') {
            $subscriber = Subscriber::findByUid($request->uid);
        } else {
            $subscriber = new \App\Models\Subscriber();
        }

        if (is_file($subscriber->getImagePath())) {
            $img = \Image::make($subscriber->getImagePath());
        } else {
            $img = \Image::make(public_path('images/subscriber-icon.jpg'));
        }

        return $img->response();
    }

    public function avatarOrigin(Request $request)
    {
        // Get current customer
        $subscriber = Subscriber::findByUid($request->uid);

        if (is_file($subscriber->getImageOriginPath())) {
            $img = \Image::make($subscriber->getImageOriginPath());
        } else {
            if (is_file($subscriber->getImagePath())) {
                $img = \Image::make($subscriber->getImagePath());
            } else {
                $img = \Image::make(public_path('images/subscriber-icon.jpg'));
            }
        }

        return $img->response();
    }

    /**
     * Resend confirmation email.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function resendConfirmationEmail(Request $request)
    {
        $subscribers = Subscriber::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );
        $list = CampaignMaillist::findByUid($request->list_uid);

        // Select all items
        if ($request->select_tool == 'all_items') {
            $subscribers = $this->search($list, $request);
        }

        // Launch re-sending job
        dispatch_now(new \Acelle\Jobs\SendConfirmationEmailJob($subscribers->get(), $list));

        // Redirect to my lists page
        echo trans('messages.subscribers.resend_confirmation_email.being_sent');
    }

    /**
     * Update tags.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function updateTags(Request $request, $list_uid, $uid)
    {
        $list = CampaignMaillist::findByUid($list_uid);
        $subscriber = Subscriber::findByUid($uid);

        // saving
        if ($request->isMethod('post')) {
            $subscriber->updateTags($request->tags);

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.subscriber.tagged', [
                    'subscriber' => $subscriber->getFullName(),
                ]),
            ], 201);
        }

        return view('managers.views.maillists.subscribers.updateTags', [
            'list' => $list,
            'subscriber' => $subscriber,
        ]);
    }

    /**
     * Automation remove contact tag.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function removeTag(Request $request, $list_uid, $uid)
    {
        $list = CampaignMaillist::findByUid($list_uid);
        $subscriber = Subscriber::findByUid($uid);


        $subscriber->removeTag($request->tag);

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.automation.contact.tag.removed', [
                'tag' => $request->tag,
            ]),
        ], 201);
    }

    public function bulkDelete(Request $request)
    {
        // init
        $list = CampaignMaillist::findByUid($request->list_uid);

        // validate and save posted data
        if ($request->isMethod('post')) {
            // make validator
            $validator = \Validator::make($request->all(), ['emails' => 'required']);

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('managers.views.maillists.subscribers.bulkDelete', [
                    'list' => $list,
                    'errors' => $validator->errors(),
                ], 400);
            }

            // get all emails
            $emails = array_unique(preg_split("/[\s,\r\n]+/", $request->emails));
            $subscribers = $list->subscribers()->whereIn('email', $emails)->get();

            //
            return view('managers.views.maillists.subscribers.bulkDeleteConfirm', [
                'list' => $list,
                'emails' => $emails,
                'subscribers' => $subscribers,
            ]);
        }

        return view('managers.views.maillists.subscribers.bulkDelete', [
            'list' => $list,
        ]);
    }

    public function import2Wizard(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);

        return view('managers.views.maillists.subscribers.import2.upload', [
            'list' => $list,
        ]);
    }

    public function bulkDeleteConfirm(Request $request)
    {
        // init
        $list = CampaignMaillist::findByUid($request->list_uid);

        // validate and save posted data
        if ($request->isMethod('post')) {
            // make validator
            $validator = \Validator::make($request->all(), ['emails' => 'required']);

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('managers.views.maillists.subscribers.bulkDelete', [
                    'list' => $list,
                    'errors' => $validator->errors(),
                ], 400);
            }

            // get all emails
            $emails = preg_split("/[\s,\r\n]+/", $request->emails);

            //
            return view('managers.views.maillists.subscribers.bulkDeleteConfirm', [
                'list' => $list,
                'emails' => $emails,
            ]);
        }

        return view('managers.views.maillists.subscribers.bulkDelete', [
            'list' => $list,
        ]);
    }

    public function import2(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);
        $currentJob = $list->importJobs()->first();


        return view('managers.views.maillists.subscribers.import2', [
            'list' => $list,
            'currentJob' => $currentJob,
        ]);
    }

    public function import2Upload(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);

        /***
         * Smetimes the mines type of uploaded file is application/octet, making it fail!!!
         * As a result, temporarily disable this validation
         *
        $validator = \Validator::make($request->all(), [
            'file' => 'required|mimes:csv,txt',
        ]);

        // redirect if fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }
        */

        $filepath = $list->uploadCsv($request->file('file'));

        // Redirect to my lists page
        return response()->json([
            'status' => 'success',
            'message' => trans('messages.subscriber.import.csv_uploaded'),
            'mappingUrl' => route('SubscriberController@import2Mapping', [
                'list_uid' => $list->uid,
                'filepath' => $filepath,
            ]),
        ]);
    }

    public function import2Mapping(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);
        $filepath = $request->filepath;


        try {
            list($headers, $total, $results) = $list->readCsv($filepath);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        }

        return view('managers.views.maillists.subscribers.import2.mapping', [
            'list' => $list,
            'headers' => $headers,
            'filepath' => $filepath,
        ]);
    }

    public function import2Validate(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);

        try {
            \Acelle\Library\MailListFieldMapping::parse($request->mapping, $list);
            return response()->json([
                'message' => 'success',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Import mapping fields.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function import2Run(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);


        $job = $list->dispatchImportJob($request->filepath, $request->mapping);

        return response()->json([
            // 'list_uid' => $list->uid,
            'job_uid' => $job->uid,
            'progressUrl' => route('SubscriberController@import2Progress', [
                'list_uid' => $list->uid,
                'job_uid' => $job->uid,
            ]),
            // 'progressCheckUrl' => route('SubscriberController@importProgress', [
            //     'job_uid' => $job->uid,
            //     'list_uid' => $list->uid
            // ]),
            // 'cancelUrl' => route('SubscriberController@cancelImport', [
            //     'job_uid' => $job->uid
            // ]),
            // 'logDownloadUrl' => route('SubscriberController@downloadImportLog', [
            //     'job_uid' => $job->uid
            // ])
        ]);
    }

    public function import2Progress(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);
        $customer = \Auth::user()->customer;

        // If the mail list dropdown is not populated
        // Then it is very likely a problem with the cronjob
        // Show this warning to user
        $lastExecutedTimeUtc = Carbon::createFromTimestamp(Setting::get('cronjob_last_execution') ?? 0);
        $now = Carbon::now();
        $lastExecutedTimeDiffInMinutes = $now->diffInMinutes($lastExecutedTimeUtc);
        $threshold = 20; // 15 minutes

        if ($lastExecutedTimeDiffInMinutes > $threshold) {
            $lastExecutedTime = $lastExecutedTimeUtc->timezone($customer->getTimezone());
            // If there is no data populated, then it is very likely that cronjob has not been set up correctly
            // Pass the last executed time to the view to show up with the warning
            $cronjobWarning = $lastExecutedTime;
        } else {
            $cronjobWarning = null;
        }

        return view('managers.views.maillists.subscribers.import2.progress', [
            'list' => $list,
            'job_uid' => $request->job_uid,
            'cronjobWarning' => $cronjobWarning,
        ]);
    }

    public function import2ProgressContent(Request $request)
    {
        $list = CampaignMaillist::findByUid($request->list_uid);
        $currentJob = $list->importJobs()->first();
        $progress = $list->getProgress($currentJob);


        return view('managers.views.maillists.subscribers.import2.progressContent', [
            'list' => $list,
            'currentJob' => $currentJob,
            'progress' => $progress,
        ]);
    }

    public function assignValues(Request $request, $list_uid)
    {
        // init
        $list = CampaignMaillist::findByUid($request->list_uid);
        $subscribers = Subscriber::whereIn('uid', $request->uids);

        // Select all items
        if ($request->select_tool == 'all_items') {
            $subscribers = $this->search($list, $request);
        }

        // validate and save posted data
        if ($request->isMethod('post')) {
            $validator = Subscriber::assginValues($subscribers, $request);

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('managers.views.maillists.subscribers.assignValues', [
                    'list' => $list,
                    'subscribers' => $subscribers,
                    'errors' => $validator->errors(),
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.subscribers.values_assigned'),
            ]);
        }

        return view('managers.views.maillists.subscribers.assignValues', [
            'list' => $list,
            'subscribers' => $subscribers,
        ]);
    }

    public function noList()
    {
        return view('managers.views.maillists.subscribers.noList');
    }
}

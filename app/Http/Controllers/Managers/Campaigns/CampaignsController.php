<?php

namespace App\Http\Controllers\Managers\Campaigns;

use App\Events\CampaignUpdated;
use App\Http\Controllers\Controller;
use App\Models\Campaign\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log as LaravelLog;
use Gate;
use Validator;
use Illuminate\Validation\ValidationException;
use App\Library\StringHelper;
use App\Jobs\ExportCampaignLog;
use App\Models\Template;
use App\Models\TrackingLog;
use App\Models\Setting;
use App\Models\Subscriber;
use App\Models\IpLocation;
use App\Models\ClickLog;
use App\Models\OpenLog;
use App\Models\TemplateCategory;
use App\Models\JobMonitor;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;

class CampaignsController extends Controller
{

    public function index(Request $request)
    {
        $campaigns = Campaign::all();

        return view('managers.views.campaigns.campaigns.index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function listing(Request $request)
    {
        $customer = $request->user()->customer;

        $campaigns = $customer->campaigns()
            ->search($request->keyword)
            ->filter($request);

        if ($request->status) {
            $campaigns = $campaigns->byStatus($request->status);
        }

        $campaigns = $campaigns->orderBy($request->sort_order, $request->sort_direction)
            ->paginate($request->per_page);

        return view('managers.views.campaigns.campaigns._list', [
            'campaigns' => $campaigns,
        ]);
    }

    public function create(Request $request)
    {
        $campaign = Campaign::newDefault();

        $campaign->saveFromArray([
            'type' => $request->type,
        ]);

        return redirect()->route('manager.campaigns.recipients', ['uid' => $campaign->uid]);
    }

    public function show($id)
    {
        $campaign = Campaign::findByUid($id);

        event(new \App\Events\CampaignUpdated($campaign));

        if ($campaign->status == 'new') {
            return redirect()->route('CampaignController@edit', ['uid' => $campaign->uid]);
        } else {
            return redirect()->route('CampaignController@overview', ['uid' => $campaign->uid]);
        }
    }

    public function edit($id)
    {
        $campaign = Campaign::findByUid($id);

        if ($campaign->step() == 0) {
            return redirect()->route('manager.campaigns.recipients', ['uid' => $campaign->uid]);
        } elseif ($campaign->step() == 1) {
            return redirect()->route('manager.campaigns.setup', ['uid' => $campaign->uid]);
        } elseif ($campaign->step() == 2) {
            return redirect()->route('manager.campaigns.template', ['uid' => $campaign->uid]);
        } elseif ($campaign->step() == 3) {
            return redirect()->route('manager.campaigns.schedule', ['uid' => $campaign->uid]);
        } elseif ($campaign->step() >= 4) {
            return redirect()->route('manager.campaigns.confirm', ['uid' => $campaign->uid]);
        }
    }

    public function recipients(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);
        $customer = $request->user()->customer;

        $lastExecutedTimeUtc = Carbon::createFromTimestamp(Setting::get('cronjob_last_execution') ?? 0);
        $lastExecutedTime = $lastExecutedTimeUtc->timezone('Europe/Madrid');

        $cronjobWarning = null;
        $rules = $campaign->recipientsRules($request->all());
        $campaign->fillRecipients($request->all());

        if (!empty($request->old())) {
            $rules = $campaign->recipientsRules($request->old());
            $campaign->fillRecipients($request->old());
        }

        if ($request->isMethod('post')) {

            $this->validate($request, $rules);
            $campaign->saveRecipients($request->all());
            event(new CampaignUpdated($campaign));

            return redirect()->route('CampaignController@setup', ['uid' => $campaign->uid]);
        }

        return view('managers.views.campaigns.campaigns.recipients', [
            'campaign' => $campaign,
            'rules' => $rules,
            'cronjobWarning' => $cronjobWarning,
        ]);
    }

    public function setup(Request $request)
    {
        $customer = $request->user()->customer;
        $campaign = Campaign::findByUid($request->uid);

        $campaign->from_name = !empty($campaign->from_name) ? $campaign->from_name : $campaign->defaultMailList->from_name;
        $campaign->from_email = !empty($campaign->from_email) ? $campaign->from_email : $campaign->defaultMailList->from_email;

        // Get old post values
        if ($request->old()) {
            $campaign->fillAttributes($request->old());
        }

        // validate and save posted data
        if ($request->isMethod('post')) {
            // Fill values
            $campaign->fillAttributes($request->all());

            // Check validation
            $this->validate($request, $campaign->rules($request));
            $campaign->save();

            // Log
            $campaign->log('created', $customer);

            return redirect()->route('CampaignController@template', ['uid' => $campaign->uid]);
        }

        $rules = $campaign->rules();

        return view('managers.views.campaigns.campaigns.setup', [
            'campaign' => $campaign,
            'rules' => $campaign->rules(),
        ]);
    }

    public function template(Request $request)
    {
        $customer = $request->user()->customer;
        $campaign = Campaign::findByUid($request->uid);

        if ($campaign->type == 'plain-text') {
            return redirect()->route('CampaignController@plain', ['uid' => $campaign->uid]);
        }

        // check if campagin does not have template
        if (!$campaign->template) {
            return redirect()->route('CampaignController@templateCreate', ['uid' => $campaign->uid]);
        }

        return view('managers.views.campaigns.campaigns.template.index', [
            'campaign' => $campaign,
            'spamscore' => Setting::isYes('spamassassin.enabled'),
        ]);
    }

    public function templateCreate(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        return view('managers.views.campaigns.campaigns.template.create', [
            'campaign' => $campaign,
        ]);
    }

    public function templateLayout(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        if ($request->isMethod('post')) {
            $template = \App\Models\Template::findByUid($request->template);
            $campaign->setTemplate($template);

            // return redirect()->route('CampaignController@templateEdit', $campaign->uid);
            return response()->json([
                'status' => 'success',
                'message' => trans('messages.campaign.theme.selected'),
                'url' => action('CampaignController@templateBuilderSelect', $campaign->uid),
            ]);
        }

        // default tab
        if ($request->from != 'mine' && !$request->category_uid) {
            $request->category_uid = TemplateCategory::first()->uid;
        }

        return view('managers.views.campaigns.campaigns.template.layout', [
            'campaign' => $campaign
        ]);
    }

    public function templateLayoutList(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        // from
        if ($request->from == 'mine') {
            $templates = $request->user()->customer->templates()->email();
        } elseif ($request->from == 'gallery') {
            $templates = Template::shared()->notPreserved()->email();
        } else {
            $templates = Template::shared()->notPreserved()->email()
                ->orWhere('customer_id', '=', $request->user()->customer->id);
        }

        $templates = $templates->notPreserved()->search($request->keyword);

        // category id
        if ($request->category_uid) {
            $templates = $templates->categoryUid($request->category_uid);
        }

        $templates = $templates->orderBy($request->sort_order, $request->sort_direction)
            ->paginate($request->per_page);

        return view('managers.views.campaigns.campaigns.template.layoutList', [
            'campaign' => $campaign,
            'templates' => $templates,
        ]);
    }

    public function templateBuilderSelect(Request $request, $uid)
    {
        $campaign = Campaign::findByUid($uid);

        return view('managers.views.campaigns.campaigns.template.templateBuilderSelect', [
            'campaign' => $campaign,
        ]);
    }

    public function templateEdit(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        // save campaign html
        if ($request->isMethod('post')) {
            $rules = array(
                'content' => 'required',
            );

            $this->validate($request, $rules);

            // template extra validation by plan (unsubscribe URL for example)
            if (get_tmp_quota($request->user()->customer, 'unsubscribe_url_required') == 'yes' && Setting::isYes('campaign.enforce_unsubscribe_url_check')) {
                if (strpos($request->content, '{UNSUBSCRIBE_URL}') === false) {
                    return response()->json(['message' => trans('messages.template.validation.unsubscribe_url_required')], 400);
                }
            }

            $campaign->setTemplateContent($request->content);
            $campaign->save();

            // update plain
            $campaign->updatePlainFromHtml();

            return response()->json([
                'status' => 'success',
            ]);
        }

        return view('managers.views.campaigns.campaigns.template.edit', [
            'campaign' => $campaign,
            'list' => $campaign->defaultMailList,
            'templates' => $request->user()->customer->getBuilderTemplates(),
        ]);
    }

    public function templateContent(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        return view('managers.views.campaigns.campaigns.template.content', [
            'content' => $campaign->template->content,
        ]);
    }

    public function templateUpload(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        // validate and save posted data
        if ($request->isMethod('post')) {
            $campaign->uploadTemplate($request);

            // return redirect()->route('CampaignController@template', $campaign->uid);
            return response()->json([
                'status' => 'success',
                'message' => trans('messages.campaign.template.uploaded'),
                'url' => action('CampaignController@templateBuilderSelect', $campaign->uid),
            ]);
        }

        return view('managers.views.campaigns.campaigns.template.upload', [
            'campaign' => $campaign,
        ]);
    }

    public function plain(Request $request)
    {
        $user = $request->user();
        $campaign = Campaign::findByUid($request->uid);

        // validate and save posted data
        if ($request->isMethod('post')) {
            // Check validation
            $this->validate($request, ['plain' => 'required']);

            // save campaign plain text
            $campaign->plain = $request->plain;
            $campaign->save();

            return redirect()->route('CampaignController@schedule', ['uid' => $campaign->uid]);
        }

        return view('managers.views.campaigns.campaigns.plain', [
            'campaign' => $campaign,
        ]);
    }

    public function templateIframe(Request $request)
    {
        $user = $request->user();
        $campaign = Campaign::findByUid($request->uid);


        return view('managers.views.campaigns.campaigns.preview', [
            'campaign' => $campaign,
        ]);
    }

    public function schedule(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);
        $currentTimezone = $campaign->customer->getTimezone();

        // check step
        if ($campaign->step() < 3) {
            return redirect()->route('CampaignController@template', ['uid' => $campaign->uid]);
        }

        // validate and save posted data
        if ($request->isMethod('post')) {
            if ($request->send_now == 'yes') {
                $campaign->run_at = null;
                $campaign->save();
            } else {
                $runAtStr = $request->delivery_date.' '.$request->delivery_time;
                $runAt = Carbon::createFromFormat('Y-m-d H:i', $runAtStr, $currentTimezone)->timezone(config('app.timezone'));
                $campaign->run_at = $runAt;
                $campaign->setScheduled();
            }

            return redirect()->route('CampaignController@confirm', ['uid' => $campaign->uid]);
        }

        // Get the run_at datetime in current customer timezone
        $runAt = is_null($campaign->run_at) ? Carbon::now($currentTimezone) : $campaign->run_at;
        $runAt->timezone($currentTimezone);

        $delivery_date = $runAt->format('Y-m-d');
        $delivery_time = $runAt->format('H:i');

        $rules = array(
            'delivery_date' => 'required',
            'delivery_time' => 'required',
        );

        // Get old post values
        if (null !== $request->old()) {
            $campaign->fill($request->old());
        }

        return view('managers.views.campaigns.campaigns.schedule', [
            'campaign' => $campaign,
            'rules' => $rules,
            'delivery_date' => $delivery_date,
            'delivery_time' => $delivery_time,
        ]);
    }

    public function confirm(Request $request)
    {
        $customer = $request->user()->customer;
        $campaign = Campaign::findByUid($request->uid);

        // check step
        if ($campaign->step() < 4) {
            return redirect()->route('CampaignController@schedule', ['uid' => $campaign->uid]);
        }


        try {
            $score = $campaign->score();
        } catch (\Exception $e) {
            $score = null;
        }

        // validate and save posted data
        if ($request->isMethod('post') && $campaign->step() >= 5) {
            // Japan + not license
            if(config('custom.japan') && !\App\Models\Setting::get('license')) {
                return response()->json([
                    'status' => 'error',
                    'message' => trans('messages.license.required'),
                ], 400);
            }

            // UGLY CODE
            if (get_tmp_quota($customer, 'unsubscribe_url_required') == 'yes' && Setting::isYes('campaign.enforce_unsubscribe_url_check')) {
                if (strpos($campaign->getTemplateContent(), '{UNSUBSCRIBE_URL}') === false) {
                    $request->session()->flash('alert-error', trans('messages.template.validation.unsubscribe_url_required'));
                    return view('managers.views.campaigns.campaigns.confirm', [
                        'campaign' => $campaign,
                        'score' => $score,
                    ]);
                }
            }

            // Save campaign
            // @todo: check campaign status before requeuing. Otherwise, several jobs shall be created and campaign will get sent several times
            $campaign->execute();

            // Log
            $campaign->log('started', $customer);

            return redirect()->route('CampaignController@index');
        }

        return view('managers.views.campaigns.campaigns.confirm', [
            'campaign' => $campaign,
            'score' => $score,
        ]);
    }


    public function delete(Request $request)
    {
        if (isSiteDemo()) {
            return response()->json(["message" => trans('messages.operation_not_allowed_in_demo')], 404);
        }

        $customer = $request->user()->customer;

        if (isSiteDemo()) {
            echo trans('messages.operation_not_allowed_in_demo');

            return;
        }

        if (!is_array($request->uids)) {
            $request->uids = explode(',', $request->uids);
        }

        $campaigns = Campaign::whereIn('uid', $request->uids);

        foreach ($campaigns->get() as $campaign) {

                $campaign->deleteAndCleanup();
        }

        // Redirect to my lists page
        echo trans('messages.campaigns.deleted');
    }

    /**
     * Campaign overview.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function overview(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        // Trigger the CampaignUpdate event to update the campaign cache information
        // The second parameter of the constructor function is false, meanining immediate update
        event(new \App\Events\CampaignUpdated($campaign));


        return view('managers.views.campaigns.campaigns.overview', [
            'campaign' => $campaign,
        ]);
    }

    public function links(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);
        $links = $campaign->clickLogs()
            ->select(
                'click_logs.url',
                DB::raw('count(*) AS clickCount'),
                DB::raw(sprintf('max(%s) AS lastClick', table('click_logs.created_at')))
            )->groupBy('click_logs.url')->get();


        return view('managers.views.campaigns.campaigns.links', [
            'campaign' => $campaign,
            'links' => $links,
        ]);
    }

    public function chart24h(Request $request)
    {
        $currentTimezone = $request->user()->customer->getTimezone();
        $nowInUserTimezone = Carbon::now($currentTimezone);
        $campaign = Campaign::findByUid($request->uid);

        $result = [
            'columns' => [],
            'opened' => [],
            'clicked' => [],
        ];

        // 24h collection
        if ($request->period == '24h') {
            $hours = [];

            // columns
            for ($i = 23; $i >= 0; --$i) {
                $time = $nowInUserTimezone->copy()->subHours($i);
                $result['columns'][] = $time->format('h') . ':00 ' . $time->format('A');
                $hours[] = $time->format('H');
            }

            $openData24h = $campaign->openUniqHours($nowInUserTimezone->copy()->subHours(24), $nowInUserTimezone);
            $clickData24h = $campaign->clickHours($nowInUserTimezone->copy()->subHours(24), $nowInUserTimezone);

            // data
            foreach ($hours as $hour) {
                $num = isset($openData24h[$hour]) ? count($openData24h[$hour]) : 0;
                $result['opened'][] = $num;

                $num = isset($clickData24h[$hour]) ? count($clickData24h[$hour]) : 0;
                $result['clicked'][] = $num;
            }
        } elseif ($request->period == '3_days') {
            $days = [];

            // columns
            for ($i = 2; $i >= 0; --$i) {
                $time = $nowInUserTimezone->copy()->subDays($i);
                $result['columns'][] = $time->format('m-d');
                $days[] = $time->format('Y-m-d');
            }

            $openData = $campaign->openUniqDays($nowInUserTimezone->copy()->subDays(3), $nowInUserTimezone->endOfDay());
            $clickData = $campaign->clickDays($nowInUserTimezone->copy()->subDays(3), $nowInUserTimezone->endOfDay());

            // data
            foreach ($days as $day) {
                $num = isset($openData[$day]) ? count($openData[$day]) : 0;
                $result['opened'][] = $num;

                $num = isset($clickData[$day]) ? count($clickData[$day]) : 0;
                $result['clicked'][] = $num;
            }
        } elseif ($request->period == '7_days') {
            $days = [];

            // columns
            for ($i = 6; $i >= 0; --$i) {
                $time = $nowInUserTimezone->copy()->subDays($i);
                $result['columns'][] = $time->format('m-d');
                $days[] = $time->format('Y-m-d');
            }

            $openData = $campaign->openUniqDays($nowInUserTimezone->copy()->subDays(7), $nowInUserTimezone->endOfDay());
            $clickData = $campaign->clickDays($nowInUserTimezone->copy()->subDays(7), $nowInUserTimezone->endOfDay());

            // data
            foreach ($days as $day) {
                $num = isset($openData[$day]) ? count($openData[$day]) : 0;
                $result['opened'][] = $num;

                $num = isset($clickData[$day]) ? count($clickData[$day]) : 0;
                $result['clicked'][] = $num;
            }
        } elseif ($request->period == 'last_month') {
            $days = [];

            // columns
            for ($i = $nowInUserTimezone->copy()->subMonths(1)->diff(Carbon::now())->days - 1; $i >= 0; --$i) {
                $time = $nowInUserTimezone->copy()->subDays($i);
                $result['columns'][] = $time->format('m-d');
                $days[] = $time->format('Y-m-d');
            }

            $openData = $campaign->openUniqDays($nowInUserTimezone->copy()->subMonths(1), $nowInUserTimezone->endOfDay());
            $clickData = $campaign->clickDays($nowInUserTimezone->copy()->subMonths(1), $nowInUserTimezone->endOfDay());

            // data
            foreach ($days as $day) {
                $num = isset($openData[$day]) ? count($openData[$day]) : 0;
                $result['opened'][] = $num;

                $num = isset($clickData[$day]) ? count($clickData[$day]) : 0;
                $result['clicked'][] = $num;
            }
        }

        return response()->json($result);
    }


    public function chart(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        $result = [
            [
                'name' => trans('messages.recipients'),
                'value' => $campaign->readCache('SubscriberCount', 0),
            ],
            [
                'name' => trans('messages.delivered'),
                'value' => $campaign->deliveredCount(),
            ],
            [
                'name' => trans('messages.failed'),
                'value' => $campaign->failedCount(),
            ],
            [
                'name' => trans('messages.Open'),
                'value' => $campaign->openUniqCount(),
            ],
            [
                'name' => trans('messages.Click'),
                'value' => $campaign->uniqueClickCount(),
            ],
            [
                'name' => trans('messages.Bounce'),
                'value' => $campaign->bounceCount(),
            ],
            [
                'name' => trans('messages.report'),
                'value' => $campaign->feedbackCount(),
            ],
            [
                'name' => trans('messages.unsubscribe'),
                'value' => $campaign->unsubscribeCount(),
            ],
        ];

        return response()->json($result);
    }

    public function chartCountry(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        $result = [
            'data' => [],
        ];

        // create data
        $total = $campaign->uniqueOpenCount();
        $count = 0;
        foreach ($campaign->topOpenCountries()->get() as $location) {
            $country_name = (!empty($location->country_name) ? $location->country_name : trans('messages.unknown'));
            $result['data'][] = ['value' => $location->aggregate, 'name' => $country_name];
            $count += $location->aggregate;
        }

        // Others
        if ($total > $count) {
            $result['data'][] = ['value' => $total - $count, 'name' => trans('messages.others')];
        }

        usort($result['data'], function ($a, $b) {
            return strcmp($a['value'], $b['value']);
        });
        $result['data'] = array_reverse($result['data']);

        return response()->json($result);
    }

    public function chartClickCountry(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);
        $result = [
            'data' => [],
        ];

        // create data
        $datas = [];
        $total = $campaign->clickCount();
        $count = 0;
        foreach ($campaign->topClickCountries()->get() as $location) {
            $result['data'][] = ['value' => $location->aggregate, 'name' => $location->country_name];
            $count += $location->aggregate;
        }

        // others
        if ($total > $count) {
            $result['data'][] = ['value' => $total - $count, 'name' => trans('messages.others')];
        }

        usort($result['data'], function ($a, $b) {
            return strcmp($a['value'], $b['value']);
        });
        $result['data'] = array_reverse($result['data']);

        return response()->json($result);
    }

    public function quickView(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        return view('managers.views.campaigns.campaigns._quick_view', [
            'campaign' => $campaign,
        ]);
    }

    public function select2(Request $request)
    {
        $data = ['items' => [], 'more' => true];

        $data['items'][] = ['id' => 0, 'text' => trans('messages.all')];
        foreach (Campaign::getAll()->get() as $campaign) {
            $data['items'][] = ['id' => $campaign->uid, 'text' => $campaign->name];
        }

        echo json_encode($data);
    }

    public function open(Request $request)
    {
        try {
            // Record open log
            $openLog = OpenLog::createFromRequest($request);

            // Execute open callbacks registered for the campaign
            if ($openLog->trackingLog && $openLog->trackingLog->campaign) {
                $openLog->trackingLog->campaign->queueOpenCallbacks($openLog);
            }
        } catch (\Exception $ex) {
            // do nothing
        }

        return response()->file(public_path('images/transparent.gif'));
    }

    public function click(Request $request)
    {
        list($url, $log) = ClickLog::createFromRequest($request);

        if ($log && $log->trackingLog && $log->trackingLog->campaign) {
            $log->trackingLog->campaign->queueClickCallbacks($log);
        }

        return redirect()->away($url);
    }

    public function unsubscribe(Request $request)
    {
        $subscriber = Subscriber::findByUid($request->subscriber);
        $message_id = StringHelper::base64UrlDecode($request->message_id);

        if (is_null($subscriber)) {
            LaravelLog::error('Subscriber does not exist');
            return view('somethingWentWrong', ['message' => trans('subscriber.invalid')]);
        }

        if ($subscriber->isUnsubscribed()) {
            return view('notice', ['message' => trans('messages.you_are_already_unsubscribed')]);
        }

        // User Tracking Information
        $trackingInfo = [
            'message_id' => $message_id,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        ];

        // GeoIP information
        $location = IpLocation::add($request->ip());
        if (!is_null($location)) {
            $trackingInfo['ip_address'] = $location->ip_address;
        }

        // Actually Unsubscribe with tracking information
        $subscriber->unsubscribe($trackingInfo);

        // Page content
        $list = $subscriber->mailList;
        $layout = \App\Models\Layout::where('alias', 'unsubscribe_success_page')->first();
        $page = \App\Models\Page::findPage($list, $layout);

        $page->renderContent(null, $subscriber);

        return view('pages.default', [
            'list' => $list,
            'page' => $page,
            'subscriber' => $subscriber,
        ]);
    }

    public function trackingLog(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        $items = $campaign->trackingLogs();

        return view('managers.views.campaigns.campaigns.tracking_log', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    public function trackingLogListing(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        $items = TrackingLog::search($request, $campaign)->paginate($request->per_page);

        return view('managers.views.campaigns.campaigns.tracking_logs_list', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    public function trackingLogDownload(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        $logtype = $request->input('logtype');

        $job = new ExportCampaignLog($campaign, $logtype);
        $monitor = $campaign->dispatchWithMonitor($job);

        return view('managers.views.campaigns.campaigns.download_tracking_log', [
            'campaign' => $campaign,
            'job' => $monitor,
        ]);
    }

    public function trackingLogExportProgress(Request $request)
    {
        $job = JobMonitor::findByUid($request->uid);

        $progress = $job->getJsonData();
        $progress['status'] = $job->status;
        $progress['error'] = $job->error;
        $progress['download'] = action('CampaignController@download', ['uid' => $job->uid]);

        return response()->json($progress);
    }

    public function download(Request $request)
    {
        $job = JobMonitor::findByUid($request->uid);
        $path = $job->getJsonData()['path'];
        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function bounceLog(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        $items = $campaign->bounceLogs();

        return view('managers.views.campaigns.campaigns.bounce_log', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    public function bounceLogListing(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        $items = \App\Models\BounceLog::search($request, $campaign)->paginate($request->per_page);

        return view('managers.views.campaigns.campaigns.bounce_logs_list', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * FBL logs.
     */
    public function feedbackLog(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        $items = $campaign->openLogs();

        return view('managers.views.campaigns.campaigns.feedback_log', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * FBL logs listing.
     */
    public function feedbackLogListing(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        $items = \App\Models\FeedbackLog::search($request, $campaign)->paginate($request->per_page);

        return view('managers.views.campaigns.campaigns.feedback_logs_list', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Open logs.
     */
    public function openLog(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        $items = $campaign->openLogs();

        return view('managers.views.campaigns.campaigns.open_log', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Open logs listing.
     */
    public function openLogListing(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);


        $items = \App\Models\OpenLog::search($request, $campaign)->paginate($request->per_page);

        return view('managers.views.campaigns.campaigns.open_log_list', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Click logs.
     */
    public function clickLog(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);


        $items = $campaign->clickLogs();

        return view('managers.views.campaigns.campaigns.click_log', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Click logs listing.
     */
    public function clickLogListing(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        $items = \App\Models\ClickLog::search($request, $campaign)->paginate($request->per_page);

        return view('managers.views.campaigns.campaigns.click_log_list', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Unscubscribe logs.
     */
    public function unsubscribeLog(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        $items = $campaign->unsubscribeLogs();

        return view('managers.views.campaigns.campaigns.unsubscribe_log', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Unscubscribe logs listing.
     */
    public function unsubscribeLogListing(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        $items = \App\Models\UnsubscribeLog::search($request, $campaign)->paginate($request->per_page);

        return view('managers.views.campaigns.campaigns.unsubscribe_logs_list', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Open map.
     */
    public function openMap(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        return view('managers.views.campaigns.campaigns.open_map', [
            'campaign' => $campaign,
        ]);
    }

    public function deleteConfirm(Request $request)
    {
        $lists = Campaign::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        return view('managers.views.campaigns.campaigns.delete_confirm', [
            'lists' => $lists,
        ]);
    }

    public function pause(Request $request)
    {
        $customer = $request->user()->customer;
        $campaigns = Campaign::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($campaigns->get() as $campaign) {
                $campaign->pause();
                $campaign->log('paused', $customer);
        }

        //
        return response()->json([
            'status' => 'success',
            'message' => trans('messages.campaigns.paused'),
        ]);
    }

    public function restart(Request $request)
    {
        $customer = $request->user()->customer;
        if (!is_array($request->uids)) {
            $request->uids = explode(',', $request->uids);
        }

        $items = Campaign::whereIn('uid', $request->uids);

        // Japan + not license
        if(config('custom.japan') && !\App\Models\Setting::get('license')) {
            return response()->json([
                'status' => 'error',
                'message' => trans('messages.license.required'),
            ], 400);
        }

        foreach ($items->get() as $item) {
                $item->resume();
                $item->log('restarted', $customer);
        }

        // Redirect to my lists page
        echo trans('messages.campaigns.restarted');
    }

    /**
     * Subscribers list.
     */
    public function subscribers(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        $subscribers = $campaign->subscribers();

        return view('managers.views.campaigns.campaigns.subscribers', [
            'subscribers' => $subscribers,
            'campaign' => $campaign,
            'list' => $campaign->defaultMailList,
        ]);
    }

    /**
     * Subscribers listing.
     */
    public function subscribersListing(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        // Subscribers
        $subscribers = $campaign->getDeliveryReport()
            ->addSelect('subscribers.*')
            ->addSelect('bounce_logs.raw AS bounced_message')
            ->addSelect('feedback_logs.feedback_type AS feedback_message')
            ->addSelect('tracking_logs.error AS failed_message');

        // Check open conditions
        if ($request->open) {
            // Query of email addresses that DID open
            $openByEmails = $campaign->openLogs()->join('subscribers', 'tracking_logs.subscriber_id', '=', 'subscribers.id')->groupBy('subscribers.email')->select('subscribers.email');

            if ($request->open == 'yes') {
                $subscribers = $subscribers->joinSub($openByEmails, 'OpenedByEmails', function ($join) {
                    $join->on('subscribers.email', '=', 'OpenedByEmails.email');
                });
            } elseif ($request->open = 'no') {
                $subscribers = $subscribers->leftJoinSub($openByEmails, 'OpenedByEmails', function ($join) {
                    $join->on('subscribers.email', '=', 'OpenedByEmails.email');
                })->whereNull('OpenedByEmails.email');
            }
        }

        // Check click conditions
        if ($request->click) {
            // Query of email addresses that DID click
            $clickByEmails = $campaign->clickLogs()->join('subscribers', 'tracking_logs.subscriber_id', '=', 'subscribers.id')->groupBy('subscribers.email')->select('subscribers.email');

            if ($request->click == 'clicked') {
                $subscribers = $subscribers->joinSub($clickByEmails, 'ClickedByEmails', function ($join) {
                    $join->on('subscribers.email', '=', 'ClickedByEmails.email');
                });
            } elseif ($request->click = 'not_clicked') {
                $subscribers = $subscribers->leftJoinSub($clickByEmails, 'ClickedByEmails', function ($join) {
                    $join->on('subscribers.email', '=', 'ClickedByEmails.email');
                })->whereNull('ClickedByEmails.email');
            }
        }

        // Paging
        $subscribers = $subscribers->search($request->keyword)->paginate($request->per_page ? $request->per_page : 50);

        // Field information
        $fields = $campaign->defaultMailList->getFields->whereIn('uid', $request->columns);

        return view('managers.views.campaigns.campaigns._subscribers_list', [
            'subscribers' => $subscribers,
            'list' => $campaign->defaultMailList,
            'campaign' => $campaign,
            'fields' => $fields,
        ]);
    }

    /**
     * Buiding email template.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function templateBuild(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        $elements = [];
        if (isset($request->style)) {
            $elements = \App\Models\Template::templateStyles()[$request->style];
        }

        return view('managers.views.campaigns.campaigns.template_build', [
            'campaign' => $campaign,
            'elements' => $elements,
            'list' => $campaign->defaultMailList,
        ]);
    }

    public function templateRebuild(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        return view('managers.views.campaigns.campaigns.template_rebuild', [
            'campaign' => $campaign,
            'list' => $campaign->defaultMailList,
        ]);
    }

    public function copy(Request $request)
    {
        $campaign = Campaign::findByUid($request->copy_campaign_uid);

        if ($request->isMethod('post')) {
            // make validator
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
            ]);

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('campaigns.copy', [
                    'campaign' => $campaign,
                    'errors' => $validator->errors(),
                ], 400);
            }

            $campaign->copy($request->name);
            return trans('messages.campaign.copied');
        }

        return view('managers.views.campaigns.campaigns.copy', [
            'campaign' => $campaign,
        ]);
    }

    public function sendTestEmail(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        if ($request->isMethod('post')) {
            // Japan + not license
            if(config('custom.japan') && !\App\Models\Setting::get('license')) {
                return response()->json([
                    'status' => 'error',
                    'message' => trans('messages.license.required'),
                ], 400);
            }

            $validator = \Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            //
            if ($validator->fails()) {
                return response()->view('campaigns.sendTestEmail', [
                    'campaign' => $campaign,
                    'errors' => $validator->errors(),
                ], 400);
            }

            $sending = $campaign->sendTestEmail($request->email);

            return response()->json($sending);
        }

        return view('managers.views.campaigns.campaigns.sendTestEmail', [
            'campaign' => $campaign,
        ]);
    }

    public function preview($id)
    {
        $campaign = Campaign::findByUid($id);

        return view('managers.views.campaigns.campaigns.preview', [
            'campaign' => $campaign,
        ]);
    }

    public function previewContent(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);
        $subscriber = Subscriber::findByUid($request->subscriber_uid);


        echo $campaign->getHtmlContent($subscriber);
    }


    public function listSegmentForm(Request $request)
    {
        // Get current user
        $campaign = Campaign::findByUid($request->uid);

        return view('managers.partials.campaigns.lists', [
            'campaign' => $campaign,
            'lists_segment_group' => [
                'list' => null,
                'is_default' => false,
            ],
        ]);
    }

    public function templateChangeTemplate(Request $request, $uid, $template_uid)
    {
        // Generate info
        $campaign = Campaign::findByUid($uid);
        $changeTemplate = Template::findByUid($template_uid);


        $campaign->changeTemplate($changeTemplate);
    }

    /**
     * Email web view.
     */
    public function webView(Request $request)
    {
        $message_id = StringHelper::base64UrlDecode($request->message_id);
        $tracking_log = TrackingLog::where('message_id', '=', $message_id)->first();

        try {
            if (!$tracking_log) {
                throw new \Exception(trans('messages.web_view_can_not_find_tracking_log_with_message_id'));
            }

            $subscriber = $tracking_log->subscriber;
            $campaign = $tracking_log->campaign;

            if (!$campaign || !$subscriber) {
                throw new \Exception(trans('messages.web_view_can_not_find_campaign_or_subscriber'));
            }

            return view('managers.views.campaigns.campaigns.web_view', [
                'campaign' => $campaign,
                'subscriber' => $subscriber,
                'message_id' => $message_id,
            ]);
        } catch (\Exception $e) {
            return view('somethingWentWrong', ['message' => trans('messages.the_email_no_longer_exists')]);
        }
    }

    public function webViewPreview(Request $request)
    {
        $subscriber = Subscriber::findByUid($request->subscriber_uid);
        $campaign = Campaign::findByUid($request->campaign_uid);

        if (is_null($subscriber) || is_null($campaign)) {
            throw new \Exception('Invalid subscriber or campaign UID');
        }

        return view('managers.views.campaigns.campaigns.web_view', [
            'campaign' => $campaign,
            'subscriber' => $subscriber,
            'message_id' => null,
        ]);
    }

    public function selectType(Request $request)
    {
        $types = Campaign::types();

        return view('managers.views.campaigns.campaigns.type', [
            'types' => $types,
        ]);
    }

    public function templateReview(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        return view('managers.views.campaigns.campaigns.template_review', [
            'campaign' => $campaign,
        ]);
    }

    public function templateReviewIframe(Request $request)
    {

        $campaign = Campaign::findByUid($request->uid);

        return view('managers.views.campaigns.campaigns.template_review_iframe', [
            'campaign' => $campaign,
        ]);
    }

    public function resend(Request $request, $uid)
    {
        $customer = $request->user()->customer;
        $campaign = Campaign::findByUid($uid);

        // do resend with option: $request->option : not_receive|not_open|not_click
        if ($request->isMethod('post')) {
            // Japan + not license
            if(config('custom.japan') && !\App\Models\Setting::get('license')) {
                return response()->json([
                    'status' => 'error',
                    'message' => trans('messages.license.required'),
                ], 400);
            }

                $campaign->resend($request->option);
                // Redirect to my lists page
                return response()->json([
                    'status' => 'success',
                    'message' => trans('messages.campaign.resent'),
                ]);

        }

        return view('managers.views.campaigns.campaigns.resend', [
            'campaign' => $campaign,
        ]);
    }

    public function spamScore(Request $request, $uid)
    {
        // Get current user
        $campaign = Campaign::findByUid($uid);

        try {
            $score = $campaign->score();
        } catch (\Exception $e) {
            return response()->json("Cannot get score. Make sure you setup for SpamAssassin correctly.\r\n".$e->getMessage(), 500); // Status code here
        }

        return view('managers.views.campaigns.campaigns.spam_score', [
            'score' => $score,
        ]);
    }

    /**
     * Edit email content.
     *
     */
    public function builderClassic(Request $request, $uid)
    {
        // Generate info
        $campaign = Campaign::findByUid($uid);

        // validate and save posted data
        if ($request->isMethod('post')) {
            $rules = array(
                'html' => 'required',
            );

            // make validator
            $validator = \Validator::make($request->all(), $rules);

            // redirect if fails
            if ($validator->fails()) {
                // faled
                return response()->json($validator->errors(), 400);
            }

            if (get_tmp_quota($request->user()->customer, 'unsubscribe_url_required') == 'yes' && Setting::isYes('campaign.enforce_unsubscribe_url_check')) {
                if (strpos($request->html, '{UNSUBSCRIBE_URL}') === false) {
                    return response()->json(['message' => trans('messages.template.validation.unsubscribe_url_required')], 400);
                }
            }

            // Save template
            $campaign->setTemplateContent($request->html);
            $campaign->preheader = $request->preheader;
            $campaign->save();

            // update plain
            $campaign->updatePlainFromHtml();

            // success
            return response()->json([
                'status' => 'success',
                'message' => trans('messages.template.updated'),
            ], 201);
        }

        return view('managers.views.campaigns.campaigns.builderClassic', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * Edit plain text.
     *
     */
    public function builderPlainEdit(Request $request, $uid)
    {
        // Generate info
        $campaign = Campaign::findByUid($uid);

        // validate and save posted data
        if ($request->isMethod('post')) {
            $rules = array(
                'plain' => 'required',
            );

            // make validator
            $validator = \Validator::make($request->all(), $rules);

            // redirect if fails
            if ($validator->fails()) {
                // faled
                return response()->json($validator->errors(), 400);
            }

            // Save template
            $campaign->plain = $request->plain;
            $campaign->save();

            // success
            return response()->json([
                'status' => 'success',
                'message' => trans('messages.template.updated'),
            ], 201);
        }

        return view('managers.views.campaigns.campaigns.builderPlainEdit', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * Upload attachment.
     *
     */
    public function uploadAttachment(Request $request, $uid)
    {
        // Generate info
        $campaign = Campaign::findByUid($uid);

        foreach ($request->file as $file) {
            $campaign->uploadAttachment($file);
        }
    }

    /**
     * Download attachment.
     *
     */
    public function downloadAttachment(Request $request, $uid)
    {
        // Generate info
        $campaign = Campaign::findByUid($uid);

        return response()->download($campaign->getAttachmentPath($request->name), $request->name);
    }

    /**
     * Remove attachment.
     *
     */
    public function removeAttachment(Request $request, $uid)
    {

        $campaign = Campaign::findByUid($uid);

        unlink($campaign->getAttachmentPath($request->name));
    }

    public function updateStats(Request $request, $uid)
    {
        $campaign = Campaign::findByUid($uid);

        $campaign->updateCache();
        echo $campaign->status;
    }

    public function notification(Request $request)
    {
        $message = StringHelper::base64UrlDecode($request->message);
        return response($message, 200)->header('Content-Type', 'text/plain');
    }

    public function customPlainOn(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);
        $campaign->plain = 'something';
        $campaign->save();

        return redirect()->route('CampaignController@builderPlainEdit', [
            'uid' => $campaign->uid,
        ]);
    }

    public function customPlainOff(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);
        $campaign->plain = null;
        $campaign->save();

        return redirect()->route('CampaignController@builderPlainEdit', [
            'uid' => $campaign->uid,
        ]);
    }

    public function previewAs(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        return view('managers.views.campaigns.campaigns.previewAs', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * Subscribers listing.
     */
    public function previewAsList(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        // Subscribers
        $subscribers = $campaign->subscribers()
            ->search($request->keyword)->paginate($request->per_page);

        return view('managers.views.campaigns.campaigns.previewAsList', [
            'subscribers' => $subscribers,
            'campaign' => $campaign,
        ]);
    }

    public function webhooks(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        return view('managers.views.campaigns.campaigns.webhooks', [
            'campaign' => $campaign,
        ]);
    }

    public function webhooksList(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        return view('managers.views.campaigns.campaigns.webhooksList', [
            'campaign' => $campaign,
        ]);
    }

    public function webhooksAdd(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);
        $webhook = $campaign->newWebhook();

        if ($request->isMethod('post')) {
            list($webhook, $validator) = $webhook->createFromArray($request->all());

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('campaigns.webhooksAdd', [
                    'campaign' => $campaign,
                    'webhook' => $webhook,
                    'errors' => $validator->errors(),
                ], 400);
            }

            return response()->json([
                'message' => trans('messages.webhook.added'),
            ]);
        }

        return view('managers.views.campaigns.campaigns.webhooksAdd', [
            'campaign' => $campaign,
            'webhook' => $webhook,
        ]);
    }

    public function webhooksLinkSelect(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        return view('managers.views.campaigns.campaigns.webhooksLinkSelect', [
            'campaign' => $campaign,
        ]);
    }

    public function webhooksEdit(Request $request)
    {
        $webhook = \App\Models\CampaignWebhook::findByUid($request->webhook_uid);

        if ($request->isMethod('post')) {
            list($webhook, $validator) = $webhook->updateFromArray($request->all());

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('campaigns.webhooksEdit', [
                    'webhook' => $webhook,
                    'errors' => $validator->errors(),
                ], 400);
            }

            return response()->json([
                'message' => trans('messages.webhook.updated'),
            ]);
        }

        return view('managers.views.campaigns.campaigns.webhooksEdit', [
            'webhook' => $webhook,
        ]);
    }

    public function webhooksDelete(Request $request)
    {
        $webhook = \App\Models\CampaignWebhook::findByUid($request->webhook_uid);

        $webhook->delete();

        return response()->json([
            'message' => trans('messages.webhook.deleted'),
        ]);
    }

    public function webhooksSampleRequest(Request $request)
    {
        $webhook = \App\Models\CampaignWebhook::findByUid($request->webhook_uid);

        return view('managers.views.campaigns.campaigns.webhooksSampleRequest', [
            'webhook' => $webhook,
        ]);
    }

    public function webhooksTest(Request $request)
    {
        $webhook = \App\Models\CampaignWebhook::findByUid($request->webhook_uid);
        $result = null;


        if ($request->isMethod('post')) {
            $client = new \GuzzleHttp\Client();

            try {
                $response = $client->request('GET', $webhook->endpoint, [
                    'headers' => [
                        "content-type" => "application/json"
                    ],
                    'body' => '{hello: "world"}',
                    'http_errors' => false,
                ]);

                $result = [
                    'status' => 'sent',
                    'code' => $response->getStatusCode(),
                    'message' => $response->getReasonPhrase(),
                ];
            } catch (\Exception $e) {
                $result = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return view('managers.views.campaigns.campaigns.webhooksTest', [
            'webhook' => $webhook,
            'result' => $result,
        ]);
    }

    public function webhooksTestMessage(Request $request, $webhook_uid, $message_id)
    {
        $webhook = \App\Models\CampaignWebhook::findByUid($request->webhook_uid);
        $result = null;


        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->request('GET', $webhook->endpoint, [
                'headers' => [
                    "content-type" => "application/json"
                ],
                'body' => '{hello: "world"}',
                'http_errors' => false,
            ]);

            $result = [
                'status' => 'sent',
                'code' => $response->getStatusCode(),
                'message' => $response->getReasonPhrase(),
                'message_id' => $message_id,
                'endpoint' => $webhook->endpoint,
                'responseBody' => $response->getBody(),
            ];
        } catch (\Exception $e) {
            $result = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'message_id' => $message_id,
                'endpoint' => $webhook->endpoint,
                'responseBody' => $response->getBody(),
            ];
        }

        return view('managers.views.campaigns.campaigns.webhooksTestMessage', [
            'webhook' => $webhook,
            'result' => $result,
        ]);
    }

    /**
     * Click logs execute.
     */
    public function clickLogExecute(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        return view('managers.views.campaigns.campaigns.clickLogExecute', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * Open logs execute.
     */
    public function openLogExecute(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        return view('managers.views.campaigns.campaigns.openLogExecute', [
            'campaign' => $campaign,
        ]);
    }

    public function preheader(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        return view('managers.views.campaigns.campaigns.preheader', [
            'campaign' => $campaign,
        ]);
    }

    public function preheaderAdd(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);


        // Save posted data
        if ($request->isMethod('post')) {
            $validator = \Validator::make($request->all(), [
                'preheader' => 'required',
            ]);

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('campaigns.preheaderAdd', [
                    'campaign' => $campaign,
                    'errors' => $validator->errors(),
                ], 400);
            }

            // update preheader
            $campaign->setPreheader($request->preheader);

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.preheader.updated'),
            ]);
        }

        return view('managers.views.campaigns.campaigns.preheaderAdd', [
            'campaign' => $campaign,
        ]);
    }

    public function preheaderRemove(Request $request)
    {
        $campaign = Campaign::findByUid($request->uid);

        $campaign->removePreheader();

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.preheader.removed'),
        ]);
    }
}

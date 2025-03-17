<?php
namespace App\Http\Controllers\Api;

use App\Events\Subscribers\SubscriberCheckatEvent;
use App\Jobs\Erp\SynchronizationSubscription;
use App\Jobs\Subscribers\SubscriberCategoriesCheackatJob;
use App\Jobs\Subscribers\SubscriberCategoriesJob;
use App\Jobs\Subscribers\SubscriberCheckatJob;
use App\Models\Lang;
use App\Models\Subscriber\Subscriber;
use App\Models\Subscriber\SubscriberList;
use App\Models\Subscriber\SubscriberLog;
use App\Services\ErpService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubscribersController extends ApiController
{


    public function campaigns(Request $request)
    {
        $action = $request->input('action');
        $data = $request->all();

        switch ($action) {
            case 'campaigns':
                return $this->suscriberCampaigns($data);
            default:
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Invalid action type'
                ], 400);
        }

    }

    public function process(Request $request)
    {
        $action = $request->input('action');
        $data = $request->all();

        switch ($action) {
            case 'rollback':
                return $this->suscriberRollback($data);
            case 'checkat':
                return $this->suscriberCheckat($data);
            case 'campaign':
                return $this->suscriberCampaigns($data);
            case 'subscribe':
                return $this->suscriberSubscribe($data);
            case 'unsubscribe_none':
                return $this->suscriberDischargersNone($data);
            case 'unsubscribe_parties':
                return $this->suscriberDischargersParties($data);
            case 'unsubscribe_sports':
                return $this->suscriberDischargersSports($data);
            default:
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Invalid action type'
                ], 400);
        }
    }


    public function suscriberCampaigns($data)
    {

        $lang = Lang::locate($data['lang']);

        $validation = Subscriber::checkWithPartition($data['email']);

        if ($validation["exists"]) {

            $subscriber = $validation["data"];

            $datas = [
                'email' => $data['email'],
                'firstname' => Str::upper($data['firstname']),
                'lastname' => Str::upper($data['lastname']),
                'commercial' => $data['commercial'] == true ? 0 : 1,
                'parties' => $data['parties'] == true ? 0 : 1,
                'commercial'  => 0,
                'parties'     => 0,
                'check_at'     => null,
                'unsubscriber_at'  => null,
            ];

            $subscriber->updateWithLog($datas);

            if ($subscriber->isPendingVerification() && isset($data['sports'])) {
                $blacklist = SubscriberList::getBlacklistByLang($lang->id);
                $subscriber->addToList($blacklist->id);
                $categoriesIds = array_filter(explode(',', $data['sports']));
                $subscriber->categories()->sync($categoriesIds);
                SubscriberCheckatJob::dispatch($subscriber);
                Log::info('Llamando a SubscriberCheckatJob para: ' . $subscriber->email);
            }

            if (!$subscriber->isPendingVerification() && isset($data['sports'])) {
                $categoriesIds = array_filter(explode(',', $data['sports']));
                SubscriberCategoriesJob::dispatch(
                    $subscriber,
                    $categoriesIds,
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Subscription successful',
                'data' => [
                    'subscriber' => [
                        'commercial' => $subscriber['commercial'],
                        'parties' => $subscriber['parties'],
                        'check' => $subscriber['check_at']!=null ? true : false,
                    ],
                    'type' => 'exist',
                ],
            ], 200);

        }else{

            return response()->json([
                'status' => 'warning',
                'message' => 'Subscription warning',
                'data' => [],
            ], 200);

        }


    }

    public function suscriberSubscribe($data)
    {

        $lang = Lang::locate($data['lang']);

        $validation = Subscriber::checkWithPartition($data['email']);

        if ($validation["exists"]) {

            $subscriber = $validation["data"];

            $subscriber->update([
                'email' => $data['email'],
                'firstname' => Str::upper($data['firstname']),
                'lastname' => Str::upper($data['lastname']),
                'commercial' => $data['commercial'] == true ? 0 : 1,
                'parties' => $data['parties'] == true ? 0 : 1,
            ]);

            if (!$subscriber->isPendingVerification() && isset($data['sports'])) {
                $categoriesIds = array_filter(explode(',', $data['sports']));
                SubscriberCategoriesJob::dispatch(
                    $subscriber,
                    $categoriesIds,
                );
            }

            if ($subscriber->isPendingVerification()){
                $blacklist = SubscriberList::getBlacklistByLang($lang->id);
                $subscriber->addToList($blacklist->id);
                SubscriberCheckatJob::dispatch($subscriber);
                Log::info('Llamando a SubscriberCheckatJob para: ' . $subscriber->email);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Subscription successful',
                'data' => [
                    'subscriber' => [
                        'commercial' => $subscriber['commercial'],
                        'parties' => $subscriber['parties'],
                        'check' => $subscriber['check_at']!=null ? true : false,
                    ],
                    'action' => 'update',
                ],
            ], 200);

        }else{

            $subscriber = Subscriber::create([
                'email' => $data['email'],
                'firstname' => Str::upper($data['firstname']),
                'lastname' => Str::upper($data['lastname']),
                'commercial' => $data['commercial'] == true ? 0 : 1,
                'parties' => $data['parties'] == true ? 0 : 1,
                'lang_id' => $lang->id ?? null,
                'condition' => Subscriber::CONDITION_UNCONFIRMED,
            ]);

            $blacklist = SubscriberList::getBlacklistByLang($lang->id);
            $subscriber->addToList($blacklist->id);


            if ($subscriber->isPendingVerification() && isset($data['sports'])) {
                $categoriesIds = array_filter(explode(',', $data['sports']));
                $subscriber->categories()->sync($categoriesIds);
            }

            SubscriberCheckatJob::dispatch($subscriber);
            Log::info('Llamando a SubscriberCheckatJob paras: ' . $subscriber->email);

            return response()->json([
                'status' => 'success',
                'message' => 'Subscription successful',
                'data' => [
                    'subscriber' => [
                        'commercial' => $subscriber['commercial'],
                        'parties' => $subscriber['parties'],
                        'check' => $subscriber['check_at']!=null ? true : false,
                    ],
                    'action' => 'create',
                ],
            ], 200);

        }

        return response()->json([
            'status' => 'success',
            'message' => 'Subscription successful',
            'suscriber' => $validation,
        ], 200);

    }

    public function suscriberDischargersNone($data)
    {

        $data = Subscriber::checkWithBTree($data['email']);
        $subscriber = $data["data"];

        $data = [
            'commercial'  => 1,
            'parties'     => 1,
            'check_at'     => null,
            'unsubscriber_at'  => Carbon::now()->setTimezone('Europe/Madrid'),
        ];

        $subscriber->updateWithLog($data);

        SubscriberCategoriesJob::dispatch(
            $subscriber,
            [],
            'none'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'You have unsubscribed from none emails.'
        ], 200);

    }

    public function suscriberDischargersParties($data)
    {
        $data = Subscriber::checkWithBTree($data['email']);

        if($data['exists']){

            $subscriber = $data["data"];

            $data = [
                'parties'=> 1,
                'check_at'     => null,
                'unsubscriber_at'     => null,
            ];

            $subscriber->updateWithLog($data);

            return response()->json([
                'status' => 'success',
                'message' => 'You have confirmed your emails.'
            ], 200);

        }else{
            return response()->json([
                'status' => 'failed',
                'message' => 'We did not find the email in our system.'
            ], 200);

        }
    }

    public function suscriberDischargersSports($datas)
    {

        $data = Subscriber::checkWithBTree($datas['email']);

        if($data['exists']){

            $subscriber = $data["data"];

            if (isset($datas['sports'])) {

                $removeCategoryIds = array_map('intval', array_filter(explode(',', $datas['sports'])));
                $currentCategoryIds = $subscriber->categories()->pluck('categories.id')->toArray();

                sort($removeCategoryIds);
                sort($currentCategoryIds);

                if ($removeCategoryIds !== $currentCategoryIds) {
                    SubscriberCategoriesJob::dispatch(
                        $subscriber,
                        $removeCategoryIds,
                        'sports'
                    );
                }
            }


            return response()->json([
                'status' => 'success',
                'data' => [],
                'message' => 'You have confirmed your emails.'
            ], 200);

        }else{
            return response()->json([
                'status' => 'failed',
                'message' => 'We did not find the email in our system.'
            ], 200);

        }

    }

    public function suscriberRollback($datas)
    {
        $uid = Crypt::decryptString($datas['uid']);
        $log = SubscriberLog::id($uid);

        if (!$log) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid tracking code or action already rolled back.'
            ], 400);
        }

        $subscriber = Subscriber::find($log->subject_id);

        if (!$subscriber) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Subscriber not found.'
            ], 404);
        }

        $changes = json_decode($log->properties, true);

        $rollbackData = [];
        foreach ($changes as $field => $values) {
            $rollbackData[$field] = $values['old_value'];
        }

        $subscriber->update($rollbackData);

        $log->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Your action has been reverted.',
            'restored_data' => $rollbackData
        ], 200);

    }


    public function suscriberCheckat($data)
    {

        $data = Subscriber::checkWithBTree(Crypt::decryptString($data['token']));

        if($data['exists']){

            $subscriber = $data["data"];
            $subscriber->check_at = Carbon::now()->setTimezone('Europe/Madrid');

            if ($subscriber->categories()->exists()) {

                $subscriber->removeFromBlacklist();
                $categoriesIds = $subscriber->categories()->pluck('categories.id')->implode(',');
                SubscriberCategoriesCheackatJob::dispatch(
                    $subscriber,
                    $categoriesIds,
                );

                SynchronizationSubscription::dispatch($subscriber->id)->onQueue('erp');
            }

            $subscriber->unsubscriber_at = null;
            $subscriber->update();

            return response()->json([
                'status' => 'success',
                'message' => 'You have confirmed your emails.'
            ], 200);

        }else{
            return response()->json([
                'status' => 'failed',
                'message' => 'We did not find the email in our system.'
            ], 200);

        }

    }
}








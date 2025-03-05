<?php
namespace App\Http\Controllers\Api;

use App\Jobs\Subscribers\SubscriberCategoriesJob;
use App\Jobs\Subscribers\SubscriberCheckatJob;
use App\Models\Lang;
use App\Models\Subscriber\Subscriber;
use App\Models\Subscriber\SubscriberList;
use App\Models\Subscriber\SubscriberLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
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

        $item = Subscriber::checkWithBTree($data['email']);

        if ($item["exists"]) {
            if (!$item["data"]->send) {
                $suscriber = $item["data"];
                $suscriber->firstname = Str::upper($data['firstname']);
                $suscriber->lastname  =  Str::upper($data['lastname']);
                $suscriber->commercial = 1;
                $suscriber->parties = 1;
                $suscriber->check_at = Carbon::now()->setTimezone('Europe/Madrid');
                $suscriber->update();

                if ($data['categories']) {
                    $categoriesIds = array_filter(explode(',', $data['categories']));
                    $suscriber->categories()->sync($categoriesIds);
                } else {
                    $suscriber->categories()->detach();
                }

                //GiftvoucherCreated::dispatch($suscriber);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Subscription successful'
                ], 200);
            }else{
                return response()->json([
                    'status' => 'warning',
                    'type' => 'send',
                    'message' => 'Subscription warning'
                ], 200);
            }
        }else{
            return response()->json([
                'status' => 'warning',
                'type' => 'exist',
                'message' => 'Subscription warning'
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
                SubscriberCheckatJob::dispatch($subscriber);
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

            if ($blacklist) {
                $subscriber->addToList($blacklist->id);
            }

            if ($subscriber->isPendingVerification() && isset($data['sports'])) {
                $categoriesIds = array_filter(explode(',', $data['sports']));
                $subscriber->categories()->sync($categoriesIds);
            }

            SubscriberCheckatJob::dispatch($subscriber);

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
        ];

        $subscriber->updateWithLog($data);

        SubscriberCategoriesJob::dispatch(
            $subscriber,
            [],
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

                $categoryIds = [];
                $categoriesIds = array_filter(explode(',', $datas['sports']));
                $currentCategoryIds = $subscriber->categories()->pluck('categories.id')->toArray();

                if (empty($currentCategoryIds)) {
                    $categoryIds  = $categoriesIds;
                } else {
                    $categoryIds = array_diff($currentCategoryIds,$categoriesIds);
                }

                SubscriberCategoriesJob::dispatch(
                    $subscriber,
                    $categoryIds,
                );

            }

            return response()->json([
                'status' => 'success',
                'data' => $subscriber->categories,
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

        $data = Subscriber::checkWithBTree($data['email']);

        if($data['exists']){

            $subscriber = $data["data"];
            $subscriber->check_at = Carbon::now()->setTimezone('Europe/Madrid');
            $subscriber->update();

            if (!$subscriber->isPendingVerification() && $subscriber->categories()->exists()) {

                $subscriber->removeFromBlacklist();
                $categoriesIds = $subscriber->categories()->pluck('categories.id')->implode(',');

                SubscriberCheckatJob::dispatch(
                    $subscriber,
                    $categoriesIds,
                );

            }

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





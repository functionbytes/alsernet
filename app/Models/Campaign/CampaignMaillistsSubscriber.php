<?php

/**
 * Subscriber class.
 *
 * Model class for Subscriber
 *
 * LICENSE: This product includes software developed at
 * the Acelle Co., Ltd. (http://acellemail.com/).
 *
 * @category   MVC Model
 *
 * @author     N. Pham <n.pham@acellemail.com>
 * @author     L. Pham <l.pham@acellemail.com>
 * @copyright  Acelle Co., Ltd
 * @license    Acelle Co., Ltd
 *
 * @version    1.0
 *
 * @link       http://acellemail.com
 */

namespace App\Models\Campaign;

use App\Models\Blacklist;
use App\Models\Segment;
use App\Models\SubscriberField;
use App\Models\Value;
use Illuminate\Database\Eloquent\Model;
use App\Models\Campaign\CampaignCampaignMaillist;
use App\Models\Setting;
use App\Events\CampaignMaillistSubscription;
use App\Events\CampaignMaillistUnsubscription;
use App\Library\Traits\HasUid;
use App\Library\StringHelper;
use DB;
use Exception;
use Closure;
use Carbon\Carbon;

class CampaignMaillistsSubscriber extends Model
{
    use HasUid;

    public const STATUS_SUBSCRIBED = 'subscribed';
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';
    public const STATUS_BLACKLISTED = 'blacklisted';
    public const STATUS_SPAM_REPORTED = 'spam-reported';
    public const STATUS_UNCONFIRMED = 'unconfirmed';

    public const SUBSCRIPTION_TYPE_ADDED = 'added';
    public const SUBSCRIPTION_TYPE_DOUBLE_OPTIN = 'double';
    public const SUBSCRIPTION_TYPE_SINGLE_OPTIN = 'single';
    public const SUBSCRIPTION_TYPE_IMPORTED = 'imported';

    public const VERIFICATION_STATUS_DELIVERABLE = 'deliverable';
    public const VERIFICATION_STATUS_UNDELIVERABLE = 'undeliverable';
    public const VERIFICATION_STATUS_UNKNOWN = 'unknown';
    public const VERIFICATION_STATUS_RISKY = 'risky';
    public const VERIFICATION_STATUS_UNVERIFIED = 'unverified';

    protected $dates = ['unsubscribed_at'];

    public static $rules = [
        'email' => ['required', 'email:rfc,filter'],
    ];

    protected $fillable = [
        'maillist_id',
        'subscriber_id',
        'image',
    ];

    protected $table = "campaigns_maillists_subscribers";

    public function subscriber()
    {
        return $this->belongsTo('App\Models\Subscriber\Subscriber');
    }

    public function CampaignMaillist()
    {
        return $this->belongsTo('App\Models\Campaign\CampaignMaillist');
    }

    public function subscriberFields()
    {
        return $this->hasMany('App\Models\SubscriberField');
    }

    public function trackingLogs()
    {
        return $this->hasMany('App\Models\TrackingLog');
    }

    public function unsubscribeLogs()
    {
        return $this->hasMany('App\Models\UnsubscribeLog');
    }

    public function scopeUnverified($query)
    {
        return $query->whereNull('subscribers.verification_status');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('subscribers.verification_status');
    }

    public function scopeDeliverable($query)
    {
        return $query->where('subscribers.verification_status', self::VERIFICATION_STATUS_DELIVERABLE);
    }

    public function scopeDeliverableOrNotVerified($query)
    {
        return $query->whereRaw(sprintf(
            "(%s = '%s' OR %s IS NULL)",
            table('subscribers.verification_status'),
            self::VERIFICATION_STATUS_DELIVERABLE,
            table('subscribers.verification_status')
        ));
    }

    public function scopeUndeliverable($query)
    {
        return $query->where('subscribers.verification_status', self::VERIFICATION_STATUS_UNDELIVERABLE);
    }

    public function scopeUnknown($query)
    {
        return $query->where('subscribers.verification_status', self::VERIFICATION_STATUS_UNKNOWN);
    }

    public function scopeRisky($query)
    {
        return $query->where('subscribers.verification_status', self::VERIFICATION_STATUS_RISKY);
    }

    public static function boot()
    {
        parent::boot();

        // Create uid when creating list.
        static::creating(function ($item) {
            $item->uid = uniqid();
        });

        static::updated(function ($item) {
            $item->reformatDateFields();
        });
    }

    public function sendToBlacklist($reason = null)
    {
        // blacklist all email
        self::where('email', $this->email)->update(['status' => self::STATUS_BLACKLISTED]);

        // create an entry in blacklists table
        $r = Blacklist::firstOrNew(['email' => $this->email]);
        $r->reason = $reason;
        $r->save();

        return true;
    }

    public function markAsSpamReported()
    {
        $this->status = self::STATUS_SPAM_REPORTED;
        $this->save();

        return true;
    }

    public function unsubscribe($trackingInfo)
    {
        // Transaction safe
        DB::transaction(function () use ($trackingInfo) {
            // Update status
            $this->status = self::STATUS_UNSUBSCRIBED;
            $this->save();

            // Trigger events
            CampaignMaillistUnsubscription::dispatch($this);

            // Create log
            $this->unsubscribeLogs()->create($trackingInfo);
        });
    }

    /**
     * Update fields from request.
     */
    public function updateFields($params)
    {
        foreach ($this->CampaignMaillist->getFields as $field) {
            // Thank you John Wigley and acorna.com team for pointing this out
            if (!isset($params[$field->tag])) {
                $params[$field->tag] = null;  // Fix for inability to clear checkboxes and multiselects, add in null elements for those missing from the form submission but defined as custom fields for that mailing list
            }
        }

        foreach ($params as $tag => $value) {
            $field = $this->CampaignMaillist->getFieldByTag(str_replace('[]', '', $tag));
            if ($field) {
                $fv = SubscriberField::where('subscriber_id', '=', $this->id)->where('field_id', '=', $field->id)->first();
                if (!$fv) {
                    $fv = new SubscriberField();
                    $fv->subscriber_id = $this->id;
                    $fv->field_id = $field->id;
                }
                if (is_array($value)) {
                    $fv->value = implode(',', $value);
                } else {
                    $fv->value = $value;
                }
                $fv->save();

                // update email attribute of subscriber
                if ($field->tag == 'EMAIL') {
                    $this->email = $fv->value;
                    $this->save();
                }
            }
        }
    }

    public function updateFields2($attributes)
    {
        foreach ($attributes as $tag => $value) {
            $field = $this->CampaignMaillist->getFieldByTag($tag);
            if (!is_null($field)) {
                $fv = $this->subscriberFields()->where('field_id', '=', $field->id)->first();

                if (is_null($fv)) {
                    $fv = $this->subscriberFields()->make();
                    $fv->field()->associate($field);
                }

                $fv->value = $value;
                $fv->save();

                // @IMPORTANT: avoid updating 'subscribers' table, especially for jobs!
                // update email attribute of subscriber
                // if (strcasecmp($field->tag, 'EMAIL') == 0) {
                //     $this->email = $fv->value;
                //     $this->save();
                // }
            }
        }
    }

    public static function filter($query, $request)
    {
        /* does not support searching on subscriber fields, for the sake of performance
        $query = $query->leftJoin('subscriber_fields', 'subscribers.id', '=', 'subscriber_fields.subscriber_id')
            ->leftJoin('mail_lists', 'subscribers.mail_list_id', '=', 'mail_lists.id');
        */
        $query = $query->leftJoin('mail_lists', 'subscribers.mail_list_id', '=', 'mail_lists.id');

        if (isset($request)) {
            // Keyword
            if (!empty(trim($request->keyword))) {
                foreach (explode(' ', trim($request->keyword)) as $keyword) {
                    $query = $query->where(function ($q) use ($keyword) {
                        $q->orwhere('subscribers.email', 'like', '%'.$keyword.'%');
                        /* does not support searching on subscriber fields, for the sake of performance
                        ->orWhere('subscriber_fields.value', 'like', '%'.$keyword.'%');
                        */
                    });
                }
            }

            // filters
            $filters = $request->filters;
            if (!empty($filters)) {
                if (!empty($filters['status'])) {
                    $query = $query->where('subscribers.status', '=', $filters['status']);
                }
                if (!empty($filters['verification_result'])) {
                    if ($filters['verification_result'] == 'unverified') {
                        $query = $query->whereNull('subscribers.verification_status');
                    } else {
                        $query = $query->where('subscribers.verification_status', '=', $filters['verification_result']);
                    }
                }
            }

            // outside filters
            if (!empty($request->status)) {
                $query = $query->where('subscribers.status', '=', $request->status);
            }
            if (!empty($request->verification_result)) {
                if ($request->verification_result == 'unverified') {
                    $query = $query->whereNull('subscribers.verification_status');
                } else {
                    $query = $query->where('subscribers.verification_status', '=', $request->verification_result);
                }
            }

            // Open
            if ($request->open == 'yes') {
                $query = $query->whereExists(function ($q) {
                    $q->select(\DB::raw(1))
                        ->from('open_logs')
                        ->join('tracking_logs', 'open_logs.message_id', '=', 'tracking_logs.message_id')
                        ->whereRaw(table('tracking_logs').'.subscriber_id = '.table('subscribers').'.id');
                });
            }

            // Not Open
            if ($request->open == 'no') {
                $query = $query->whereNotExists(function ($q) {
                    $q->select(\DB::raw(1))
                        ->from('open_logs')
                        ->join('tracking_logs', 'open_logs.message_id', '=', 'tracking_logs.message_id')
                        ->whereRaw(table('tracking_logs').'.subscriber_id = '.table('subscribers').'.id');
                });
            }

            // Click
            if ($request->click == 'yes') {
                $query = $query->whereExists(function ($q) {
                    $q->select(\DB::raw(1))
                        ->from('click_logs')
                        ->join('tracking_logs', 'click_logs.message_id', '=', 'tracking_logs.message_id')
                        ->whereRaw(table('tracking_logs').'.subscriber_id = '.table('subscribers').'.id');
                });
            }

            // Not Click
            if ($request->click == 'no') {
                $query = $query->whereNotExists(function ($q) {
                    $q->select(\DB::raw(1))
                        ->from('click_logs')
                        ->join('tracking_logs', 'click_logs.message_id', '=', 'tracking_logs.message_id')
                        ->whereRaw(table('tracking_logs').'.subscriber_id = '.table('subscribers').'.id');
                });
            }
        }

        return $query;
    }

    public static function search($request, $customer = null)
    {
        $query = self::select('subscribers.*');

        // Filter by customer
        if (!isset($customer)) {
            $customer = $request->user()->customer;
        }
        $query = $query->where('mail_lists.customer_id', '=', $customer->id);

        // Filter
        $query = self::filter($query, $request);

        // Order
        if (isset($request->sort_order)) {
            $query = $query->orderBy($request->sort_order, $request->sort_direction);
        }

        return $query;
    }

    public function getValueByField($field)
    {
        $fv = $this->subscriberFields->filter(function ($r, $key) use ($field) {
            return $r->field_id == $field->id;
        })->first();
        if ($fv) {
            return $fv->value;
        } else {
            return '';
        }
    }

    public function getValueByTag($tag)
    {
        $fv = SubscriberField::leftJoin('fields', 'fields.id', '=', 'subscriber_fields.field_id')
            ->where('subscriber_id', '=', $this->id)->where('fields.tag', '=', $tag)->first();
        if ($fv) {
            return $fv->value;
        } else {
            return '';
        }
    }

    public function setField($field, $value)
    {
        $fv = SubscriberField::where('subscriber_id', '=', $this->id)->where('field_id', '=', $field->id)->first();
        if (!$fv) {
            $fv = new SubscriberField();
            $fv->field_id = $field->id;
            $fv->subscriber_id = $this->id;
        }

        $fv->value = $value;
        $fv->save();
    }

    public static $itemsPerPage = 25;

    public function getSecurityToken($action)
    {
        $string = $this->email.$action.config('app.key');

        return md5($string);
    }

    public function log($name, $customer, $add_datas = [])
    {
        $data = [
                'id' => $this->id,
                'email' => $this->email,
                'list_id' => $this->mail_list_id,
                'list_name' => $this->CampaignMaillist->name,
        ];

        $data = array_merge($data, $add_datas);

        \App\Models\Log::create([
                                'customer_id' => $customer->id,
                                'type' => 'subscriber',
                                'name' => $name,
                                'data' => json_encode($data),
                            ]);
    }

    public function copy(CampaignMaillist $list, Closure $duplicateCallback = null)
    {
        // find exists
        $copy = $list->subscribers()->where('email', '=', $this->email)->first();

        if (!is_null($copy)) {
            if (!is_null($duplicateCallback)) {
                $duplicateCallback($this);
            }

            return null;
        }

        // Actually copy
        $copy = self::find($this->id)->replicate();
        $copy->uid = uniqid();
        $copy->mail_list_id = $list->id;
        $copy->save();

        // update fields
        foreach ($this->subscriberFields as $item) {
            foreach ($copy->CampaignMaillist->fields as $field) {
                if ($item->field->tag == $field->tag) {
                    $copy->setField($field, $item->value);
                }
            }
        }

        return $copy;
    }

    public function move($list)
    {
        $this->copy($list);
        $this->delete();
    }

    public function trackingLog($campaign)
    {
        $query = \App\Models\TrackingLog::where('tracking_logs.subscriber_id', '=', $this->id);
        $query = $query->where('tracking_logs.campaign_id', '=', $campaign->id)->orderBy('created_at', 'desc')->first();

        return $query;
    }

    public function openLogs($campaign = null)
    {
        $query = \App\Models\OpenLog::leftJoin('tracking_logs', 'tracking_logs.message_id', '=', 'open_logs.message_id')
            ->where('tracking_logs.subscriber_id', '=', $this->id);

        if (isset($campaign)) {
            $query = $query->where('tracking_logs.campaign_id', '=', $campaign->id);
        }

        return $query;
    }

    public function lastOpenLog($campaign = null)
    {
        $query = $this->openLogs($campaign);

        $query = $query->orderBy('open_logs.created_at', 'desc')->first();

        return $query;
    }

    public function clickLogs($campaign = null)
    {
        $query = \App\Models\ClickLog::leftJoin('tracking_logs', 'tracking_logs.message_id', '=', 'click_logs.message_id')
            ->where('tracking_logs.subscriber_id', '=', $this->id);

        if (isset($campaign)) {
            $query = $query->where('tracking_logs.campaign_id', '=', $campaign->id);
        }

        return $query;
    }

    public function lastClickLog($campaign = null)
    {
        $query = $this->clickLogs();
        $query = $query->orderBy('click_logs.created_at', 'desc')->first();

        return $query;
    }

    public static function copyMoveExistSelectOptions()
    {
        return [
            ['text' => trans('messages.update_if_subscriber_exists'), 'value' => 'update'],
            ['text' => trans('messages.keep_if_subscriber_exists'), 'value' => 'keep'],
        ];
    }

    public function verify($verifier)
    {
        list($status, $response) = $verifier->verify($this->email);
        $this->verification_status = $status;
        $this->last_verification_at = Carbon::now();
        $this->last_verification_by = $verifier->name;
        $this->last_verification_result = (string)$response->getBody();
        $this->save();
        return $this;
    }

    public function setVerificationStatus($status)
    {
        // note: status must be one of the pre-defined list: see related constants
        $this->verification_status = $status;
        $this->last_verification_at = Carbon::now();
        $this->last_verification_by = 'ADMIN';
        $this->last_verification_result = 'Manually set';
        $this->save();
        return $this;
    }

    public function resetVerification()
    {
        $this->verification_status = null;
        $this->last_verification_at = null;
        $this->last_verification_by = null;
        $this->last_verification_result = null;
        $this->save();
    }

    public function getImagePath()
    {
        $path = storage_path('app/subscriber/');

        // create if not exist
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        //
        return join_paths($path, $this->uid.'.jpg');
    }

    public function getImageOriginPath()
    {
        return $this->getImagePath() . '.origin.jpg';
    }

    public function uploadImage($file)
    {
        $path = $this->getImagePath();
        $originPath = $this->getImageOriginPath();

        // File name: avatar
        $filename = basename($originPath);

        // The base dir: /storage/app/users/000000/home/
        $dirname = dirname($originPath);

        // save to server
        $file->move($dirname, $filename);

        // create thumbnails
        $img = \Image::make($originPath);

        // resize image
        $img->resize(500, 500, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->save($originPath);

        // default size overwrite
        $img->fit(120, 120)->save($path);

        return $path;
    }

    public function removeImage()
    {
        if (!empty($this->uid)) {
            $path = storage_path('app/subscriber/'.$this->uid);
            if (is_file($path)) {
                unlink($path);
            }
            if (is_file($path.'.jpg')) {
                unlink($path.'.jpg');
            }
        }
    }

    public function isListedInBlacklist()
    {
        // @todo Filter by current user only
        return Blacklist::where('email', '=', $this->email)->exists();
    }

    public function getFullName($default = null)
    {

        $full = trim($this->getValueByTag('FIRST_NAME').' '.$this->getValueByTag('LAST_NAME'));

        if (empty($full)) {
            return $default;
        } else {
            return $full;
        }
    }

    public function getFullNameOrEmail()
    {
        $full = $this->getFullName();
        if (empty($full)) {
            return $this->email;
        } else {
            return $full;
        }
    }

    public function isActive()
    {
        return $this->status == self::STATUS_SUBSCRIBED;
    }

    public function getTags(): array
    {
        // Notice: json_decode() returns null if input is null or empty
        return json_decode($this->tags, true) ?: [];
    }

    public function getTagOptions()
    {
        $arr = [];
        foreach ($this->getTags() as $tag) {
            $arr[] = ['text' => $tag, 'value' => $tag];
        }

        return $arr;
    }

    public function addTags($arr)
    {
        $tags = $this->getTags();

        $nTags = array_values(array_unique(array_merge($tags, $arr)));

        $this->tags = json_encode($nTags);
        $this->save();
    }

    public function updateTags(array $newTags, $merge = false)
    {
        // remove trailing space
        array_walk($newTags, function (&$val) {
            $val = trim($val);
        });

        // remove empty tag
        $newTags = array_filter($newTags, function (&$val) {
            return !empty($val);
        });

        if ($merge == true) {
            $currentTags = $this->getTags();
            $newTags = array_values(array_unique(array_merge($currentTags, $newTags)));
        }

        // Without JSON_UNESCAPED_UNICODE specified
        // Results of json_encode(['русский']) may look like this
        //
        //     ["\u0440\u0443\u0441\u0441\u043a\u0438\u0439"]
        //
        // which cannot be searched for
        //
        $this->tags = json_encode($newTags, JSON_UNESCAPED_UNICODE);
        $this->save();
    }

    public function removeTag($tag)
    {
        $tags = $this->getTags();

        if (($key = array_search($tag, $tags)) !== false) {
            unset($tags[$key]);
        }

        $this->tags = json_encode($tags);
        $this->save();
    }

    public function scopeFilter($query, $request)
    {
        if (isset($request)) {
            // filters
            $filters = $request->filters;
            if (!empty($filters)) {
                if (!empty($filters['status'])) {
                    $query = $query->where('subscribers.status', '=', $filters['status']);
                }
                if (!empty($filters['verification_result'])) {
                    if ($filters['verification_result'] == 'unverified') {
                        $query = $query->whereNull('subscribers.verification_status');
                    } else {
                        $query = $query->where('subscribers.verification_status', '=', $filters['verification_result']);
                    }
                }
            }

            // outside filters
            if (!empty($request->status)) {
                $query = $query->where('subscribers.status', '=', $request->status);
            }
            if (!empty($request->verification_result)) {
                $query = $query->where('subscribers.verification_status', '=', $request->verification_result);
            }

            // Open
            if ($request->open == 'yes') {
                $query = $query->whereExists(function ($q) {
                    $q->select(\DB::raw(1))
                        ->from('open_logs')
                        ->join('tracking_logs', 'open_logs.message_id', '=', 'tracking_logs.message_id')
                        ->whereRaw(table('tracking_logs').'.subscriber_id = '.table('subscribers').'.id');
                });
            }

            // Not Open
            if ($request->open == 'no') {
                $query = $query->whereNotExists(function ($q) {
                    $q->select(\DB::raw(1))
                        ->from('open_logs')
                        ->join('tracking_logs', 'open_logs.message_id', '=', 'tracking_logs.message_id')
                        ->whereRaw(table('tracking_logs').'.subscriber_id = '.table('subscribers').'.id');
                });
            }

            // Click
            if ($request->click == 'yes') {
                $query = $query->whereExists(function ($q) {
                    $q->select(\DB::raw(1))
                        ->from('click_logs')
                        ->join('tracking_logs', 'click_logs.message_id', '=', 'tracking_logs.message_id')
                        ->whereRaw(table('tracking_logs').'.subscriber_id = '.table('subscribers').'.id');
                });
            }

            // Not Click
            if ($request->click == 'no') {
                $query = $query->whereNotExists(function ($q) {
                    $q->select(\DB::raw(1))
                        ->from('click_logs')
                        ->join('tracking_logs', 'click_logs.message_id', '=', 'tracking_logs.message_id')
                        ->whereRaw(table('tracking_logs').'.subscriber_id = '.table('subscribers').'.id');
                });
            }
        }

        return $query;
    }

    public function scopeSearch($query, $keyword)
    {
        /* does not support searching on subscriber fields, for the sake of performance
        $query = $query->leftJoin('subscriber_fields', 'subscribers.id', '=', 'subscriber_fields.subscriber_id')
            ->leftJoin('mail_lists', 'subscribers.mail_list_id', '=', 'mail_lists.id');
        */
        // Keyword
        if (!empty(trim($keyword))) {
            foreach (explode(' ', trim($keyword)) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('subscribers.email', 'like', '%'.$keyword.'%');
                    /* does not support searching on subscriber fields, for the sake of performance
                    ->orWhere('subscriber_fields.value', 'like', '%'.$keyword.'%');
                    */
                });
            }
        }

        return $query;
    }

    public function scopeSubscribed($query)
    {
        return $query->where('subscribers.status', self::STATUS_SUBSCRIBED);
    }

    public function isSubscribed()
    {
        return $this->status == self::STATUS_SUBSCRIBED;
    }

    public function isUnsubscribed()
    {
        return $this->status == self::STATUS_UNSUBSCRIBED;
    }

    public function getHistory()
    {
        $openLogs = table('open_logs');
        $clickLogs = table('click_logs');
        $subscribeLogs = table('subscribe_logs');
        $subscribers = table('subscribers');
        $CampaignMaillists = table('mail_lists');
        $campaigns = table('campaigns');
        $trackingLogs = table('tracking_logs');

        $sql = "
            SELECT subscriber_id, activity, list_id, list_name, campaign_id, campaign_name, at
            FROM
            (
                SELECT t.subscriber_id, 'open' as activity, null as list_id, null as list_name, t.campaign_id, c.name as campaign_name, open.created_at as at
                FROM {$openLogs} open
                JOIN {$trackingLogs} t on open.message_id = t.message_id
                JOIN {$subscribers} s on s.id = t.subscriber_id
                JOIN {$campaigns} c on c.id  = t.campaign_id
                WHERE s.email = '{$this->email}'
            ) AS open

            UNION
            (
                SELECT t.subscriber_id, 'click' as activity, null as list_id, null as list_name, t.campaign_id, c.name as campaign_name, click.created_at as at
                FROM {$clickLogs} click
                JOIN {$trackingLogs} t on click.message_id = t.message_id
                JOIN {$subscribers} s on s.id = t.subscriber_id
                JOIN {$campaigns} c on c.id  = t.campaign_id
                WHERE s.email = '{$this->email}'
            )

            UNION
            (
                SELECT s.id AS subscriber_id, 'subscribe' AS activity, l.id as list_id, l.name as list_name, null AS campaign_id, null AS campaign_name, s.created_at as at
                FROM {$subscribers} s
                JOIN {$CampaignMaillists} l on l.id  = s.mail_list_id
                WHERE s.email = '{$this->email}'
            )

            ORDER BY at DESC;
        ";

        $result = DB::select($sql);

        return json_decode(json_encode($result), true);
    }

    public function scopeSearchByEmail($query, $email)
    {
        return $query->where('subscribers.email', $email);
    }

    public function reformatDateFields()
    {
        $this->CampaignMaillist->reformatDateFields($this->id);
    }

    public static function assginValues($subscribers, $request)
    {
        if ($request->assign_type == 'single') {
            $rules = [
                'single_value' => 'required',
            ];
        } else {
            $rules = [
                'list_value' => 'required',
            ];
        }
        // make validator
        $validator = \Validator::make($request->all(), $rules);

        // redirect if fails
        if ($validator->fails()) {
            return $validator;
        }

        // do assign
        if ($request->assign_type == 'single') {
            // do assign a value: $request->single_value
        } else {
            // do assign a list: $request->list_value
        }

        return $validator;
    }


    // Confirm a subscription via double opt-in form
    public function confirm()
    {
        $this->status = self::STATUS_SUBSCRIBED;
        $this->save();

        CampaignMaillistSubscription::dispatch($this);
    }

    public function scopeUnsubscribed($query)
    {
        return $query->where('status', '=', self::STATUS_UNSUBSCRIBED);
    }

    public function generateUnsubscribeUrl($messageId = null, $absoluteUrl = true)
    {
        $url = route('unsubscribeUrl', [
            'message_id' => StringHelper::base64UrlEncode($messageId),
            'subscriber' => $this->uid
        ], $absoluteUrl);

        return $url;
    }

    public function generateUpdateProfileUrl()
    {
        return route('updateProfileUrl', ['list_uid' => $this->CampaignMaillist->uid, 'uid' => $this->uid, 'code' => $this->getSecurityToken('update-profile')]);
    }

    // Change status to SUBSCRIBED
    // @important: need subscription log in the future?
    public function subscribe()
    {
        $this->status = self::STATUS_SUBSCRIBED;
        $this->save();

        CampaignMaillistSubscription::dispatch($this);
    }

    public function scopeSimpleSearch($query, $keyword)
    {
        if (empty($keyword)) {
            return $query;
        }

        $cleanKeyword = preg_replace('/[^a-z0-9_\.@]+/i', ' ', $keyword);

        return $query->where(function ($query) use ($cleanKeyword) {
            $query->where('subscribers.email', 'LIKE', "%{$cleanKeyword}%");
        });
    }

    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Check if the email address is deliverable.
     *
     * @return bool
     */
    public function isDeliverable()
    {
        return $this->verification_result == self::VERIFICATION_STATUS_DELIVERABLE;
    }

    /**
     * Check if the email address is undeliverable.
     *
     * @return bool
     */
    public function isUndeliverable()
    {
        return $this->verification_result == self::VERIFICATION_STATUS_UNDELIVERABLE;
    }

    /**
     * Check if the email address is risky.
     *
     * @return bool
     */
    public function isRisky()
    {
        return $this->verification_result == self::VERIFICATION_STATUS_RISKY;
    }

    /**
     * Check if the email address is unknown.
     *
     * @return bool
     */
    public function isUnknown()
    {
        return $this->verification_result == self::VERIFICATION_STATUS_UNKNOWN;
    }

    /**
     * Email verification result types select options.
     *
     * @return array
     */
    public static function getVerificationStates()
    {
        return [
            ['value' => self::VERIFICATION_STATUS_DELIVERABLE, 'text' => trans('messages.email_verification_result_deliverable')],
            ['value' => self::VERIFICATION_STATUS_UNDELIVERABLE, 'text' => trans('messages.email_verification_result_undeliverable')],
            ['value' => self::VERIFICATION_STATUS_UNKNOWN, 'text' => trans('messages.email_verification_result_unknown')],
            ['value' => self::VERIFICATION_STATUS_RISKY, 'text' => trans('messages.email_verification_result_risky')],
            ['value' => self::VERIFICATION_STATUS_UNVERIFIED, 'text' => trans('messages.email_verification_result_unverified')],
        ];
    }

    public static function getByListsAndSegments(...$segmentsOrLists)
    {
        if (empty($segmentsOrLists)) {
            // this is a trick for returning an empty builder
            return static::limit(0);
        }

        $query = static::select('subscribers.*');

        // Get subscriber from mailist and segment
        $conditions = [];
        foreach ($segmentsOrLists as $listOrSegment) {
            if ($listOrSegment instanceof Segment) {
                // Segment
                $conds = $listOrSegment->getSubscribersConditions();

                // Break, otherwise it causes an error like:
                // Illuminate\Database\QueryException with message 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near ')))' at line 1 (SQL: select count(*) as aggregate from `subscribers` where ((subscribers.mail_list_id = 33 AND ())))'
                if (is_null($conds)) {
                    continue;
                }

                if (!empty($conds['joins'])) {
                    foreach ($conds['joins'] as $joining) {
                        $query = $query->leftJoin($joining['table'], function ($join) use ($joining) {
                            $join->on($joining['ons'][0][0], '=', $joining['ons'][0][1]);
                            if (isset($joining['ons'][1])) {
                                $join->on($joining['ons'][1][0], '=', $joining['ons'][1][1]);
                            }
                        });
                    }
                }

                // IMPORTANT: segment condition does not include list_id constraints, so we have to add it to make sure only the segment's list is considered
                $conds['conditions'] = '('.table('subscribers.mail_list_id').' = '.$listOrSegment->mail_list_id.' AND ('.$conds['conditions'].'))';
                $conditions[] = $conds['conditions'];
            } elseif ($listOrSegment instanceof CampaignMaillist) {
                // Entire list
                $listId = $listOrSegment->id;
                $conditions[] = '('.table('subscribers.mail_list_id').' = '.$listId.')';
            } else {
                throw new Exception('Object must be Segment or CampaignMaillist');
            }
        }

        if (!empty($conditions)) {
            $query = $query->whereRaw('('.implode(' OR ', $conditions).')');
        }

        return $query;
    }

    public static function scopeByEmail($query, $email)
    {
        $query = $query->where('email', $email);
    }

}

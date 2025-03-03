<?php

namespace App\Models\Campaign;

use App\Library\Log;
use App\Library\Traits\HasCache;
use App\Library\Traits\HasUid;
use App\Models\Campaign\CampaignMaillist;
use App\Models\Campaign\CampaignSegmentCondition;
use Illuminate\Database\Eloquent\Model;

class CampaignSegment extends Model
{
    use HasUid;
    use HasCache;

    protected $table = "campaigns_maillists_segments";

    protected $fillable = [
        'name', 'matching',
    ];

    public static $itemsPerPage = 25;

    public static $rules = array(
        'name' => 'required',
        'matching' => 'required',
    );

    public function mailList()
    {
        return $this->belongsTo('App\Models\Campaign\CampaignMaillist');
    }

    public function segmentConditions()
    {
        return $this->hasMany('App\Models\Maillist\SegmentCondition');
    }

    /**
     * Filter items.
     *
     * @return collect
     */
    public static function filter($request)
    {
        $user = $request->user();
        $list = CampaignMaillist::findByUid($request->list_uid);
        $query = self::select('segments.*')->where('segments.maillist_id', '=', $list->id);

        // Keyword
        if (!empty(trim($request->keyword))) {
            $query = $query->where('name', 'like', '%'.$request->keyword.'%');
        }

        return $query;
    }

    public static function search($request)
    {
        $query = self::filter($request);

        $query = $query->orderBy($request->sort_order, $request->sort_direction);

        return $query;
    }

    public static function getTypeOptions()
    {
        return [
            ['text' => trans('messages.all'), 'value' => 'all'],
            ['text' => trans('messages.any'), 'value' => 'any'],
        ];
    }

    public static function operators()
    {
        return [
            ['text' => trans('messages.equal'), 'value' => 'equal'],
            ['text' => trans('messages.not_equal'), 'value' => 'not_equal'],
            ['text' => trans('messages.contains'), 'value' => 'contains'],
            ['text' => trans('messages.not_contains'), 'value' => 'not_contains'],
            ['text' => trans('messages.starts'), 'value' => 'starts'],
            ['text' => trans('messages.ends'), 'value' => 'ends'],
            ['text' => trans('messages.not_starts'), 'value' => 'not_starts'],
            ['text' => trans('messages.not_ends'), 'value' => 'not_ends'],
            ['text' => trans('messages.greater'), 'value' => 'greater'],
            ['text' => trans('messages.less'), 'value' => 'less'],
            ['text' => trans('messages.blank'), 'value' => 'blank'],
            ['text' => trans('messages.not_blank'), 'value' => 'not_blank'],
        ];
    }

    public static function dateOperators()
    {
        return [
            ['text' => trans('messages.operator.later'), 'value' => 'greater'],
            ['text' => trans('messages.operator.earlier'), 'value' => 'less'],
            ['text' => trans('messages.operator.is'), 'value' => 'equal'],
            ['text' => trans('messages.operator.is_not'), 'value' => 'not_equal'],
            ['text' => trans('messages.blank'), 'value' => 'blank'],
            ['text' => trans('messages.not_blank'), 'value' => 'not_blank'],
        ];
    }

    public static function verificationOperators()
    {
        return [
            ['text' => trans('messages.equal'), 'value' => 'verification_equal'],
            ['text' => trans('messages.not_equal'), 'value' => 'verification_not_equal'],
        ];
    }

    public static function createdDateOperators()
    {
        return [
            ['text' => trans('messages.greater_than'), 'value' => 'created_date_greater'],
            ['text' => trans('messages.less_than'), 'value' => 'created_date_less'],
            ['text' => trans('messages.last_x_days'), 'value' => 'created_date_last_x_days'],
        ];
    }

    public static function tagOperators()
    {
        return [
            ['text' => trans('messages.contains'), 'value' => 'tag_contains'],
            ['text' => trans('messages.not_contains'), 'value' => 'tag_not_contains'],
        ];
    }

    public static function openMailOperators()
    {
        return [
            ['text' => trans('messages.segment.greater_than_days'), 'value' => 'last_open_email_greater_than_days'],
            ['text' => trans('messages.segment.less_than_days'), 'value' => 'last_open_email_less_than_days'],
        ];
    }
    public static function clickLinkOperators()
    {
        return [
            ['text' => trans('messages.segment.greater_than_days'), 'value' => 'last_link_click_greater_than_days'],
            ['text' => trans('messages.segment.less_than_days'), 'value' => 'last_link_click_less_than_days'],
        ];
    }

    public function getSubscribersConditions()
    {

        if (!$this->segmentConditions()->exists()) {
            return null;
        }

        $conditions = [];
        $joins = [];
        $joined_tables = [];
        foreach ($this->segmentConditions as $index => $condition) {
            $number = uniqid();

            $keyword = $condition->value;
            $keyword = str_replace('[EMPTY]', '', $keyword);
            $keyword = str_replace('[DATETIME]', date('Y-m-d H:i:s'), $keyword);
            $keyword = str_replace('[DATE]', date('Y-m-d'), $keyword);

            $keyword = trim(strtolower($keyword));

            // If conditions with fields
            if (isset($condition->field_id)) {
                $type = $condition->field->type;
                switch ($condition->operator) {
                    case 'equal':
                        if ($type == 'number') {
                            $cond = sprintf('CAST(sf%s.value AS SIGNED) = CAST(%s AS SIGNED)', $number, db_quote($keyword));
                        } else {
                            $cond = 'LOWER(sf'.$number.'.value) = '.db_quote($keyword);
                        }
                        break;
                    case 'not_equal':
                        if ($type == 'number') {
                            $cond = sprintf('CAST(sf%s.value AS SIGNED) != CAST(%s AS SIGNED)', $number, db_quote($keyword));
                        } else {
                            $cond = 'LOWER(sf'.$number.'.value) != '.db_quote($keyword);
                        }
                        break;
                    case 'contains':
                        $cond = 'LOWER(sf'.$number.'.value) LIKE '.db_quote('%'.$keyword.'%');
                        break;
                    case 'not_contains':
                        $cond = '(LOWER(sf'.$number.'.value) NOT LIKE '.db_quote('%'.$keyword.'%').' OR sf'.$number.'.value IS NULL)';
                        break;
                    case 'starts':
                        $cond = 'LOWER(sf'.$number.'.value) LIKE '.db_quote($keyword.'%');
                        break;
                    case 'ends':
                        $cond = 'LOWER(sf'.$number.'.value) LIKE '.db_quote('%'.$keyword);
                        break;
                    case 'greater':
                        if ($type == 'number') {
                            $cond = sprintf('CAST(sf%s.value AS SIGNED) > CAST(%s AS SIGNED)', $number, db_quote($keyword));
                        } else {
                            $cond = 'sf'.$number.'.value > '.db_quote($keyword);
                        }
                        break;
                    case 'less':
                        if ($type == 'number') {
                            $cond = sprintf('CAST(sf%s.value AS SIGNED) < CAST(%s AS SIGNED)', $number, db_quote($keyword));
                        } else {
                            $cond = 'sf'.$number.'.value < '.db_quote($keyword);
                        }

                        break;
                    case 'not_starts':
                        $cond = 'sf'.$number.'.value NOT LIKE '.db_quote($keyword.'%');
                        break;
                    case 'not_ends':
                        $cond = 'LOWER(sf'.$number.'.value) NOT LIKE '.db_quote('%'.$keyword);
                        break;
                    case 'not_blank':
                        $cond = '(LOWER(sf'.$number.".value) != '' AND LOWER(sf".$number.'.value) IS NOT NULL)';
                        break;
                    case 'blank':
                        $cond = '(LOWER(sf'.$number.".value) = '' OR LOWER(sf".$number.'.value) IS NULL)';
                        break;
                    default:
                        throw new \Exception("Unknown segment condition type (operator): ".$condition->operator);
                }

                // add to joins array
                $joins[] = [
                    'table' => \DB::raw(\DB::getTablePrefix().'subscriber_fields as sf'.$number),
                    'ons' => [
                        [\DB::raw('sf'.$number.'.subscriber_id'), \DB::raw(\DB::getTablePrefix().'subscribers.id')],
                        [\DB::raw('sf'.$number.'.field_id'), \DB::raw($condition->field_id)],
                    ],
                ];

                // add condition
                $conditions[] = $cond;
            } else {
                switch ($condition->operator) {
                    case 'verification_equal':
                        // add condition
                        $conditions[] = '('.\DB::getTablePrefix()."subscribers.verification_status = '".$condition->value."')";
                        break;
                    case 'verification_not_equal':
                        // add condition
                        $conditions[] = '('.\DB::getTablePrefix().'subscribers.verification_status IS NULL OR '.\DB::getTablePrefix()."subscribers.verification_status != '".$condition->value."')";
                        break;
                    case 'tag_contains':
                        // add condition
                        $conditions[] = '('.\DB::getTablePrefix().'subscribers.tags LIKE '.db_quote('%"'.$keyword.'"%').')';
                        break;
                    case 'tag_not_contains':
                        // add condition
                        $conditions[] = '('.\DB::getTablePrefix().'subscribers.tags NOT LIKE '.db_quote('%"'.$keyword.'"%').')';
                        break;
                    case 'last_open_email_less_than_days':
                        // IMPORTANT: NO LEFT JOIN HERE
                        $conditions[] = sprintf('(%s IN (SELECT s.id FROM %s s JOIN %s l ON s.id = l.subscriber_id JOIN %s o ON l.message_id = o.message_id WHERE DATEDIFF(now(), o.created_at) < %s))', table('subscribers.id'), table('subscribers'), table('tracking_logs'), table('open_logs'), db_quote($keyword));
                        break;
                    case 'last_open_email_greater_than_days':
                        // IMPORTANT: LEFT JOIN HERE
                        $conditions[] = sprintf('(%s IN (SELECT s.id FROM %s s LEFT JOIN %s l ON s.id = l.subscriber_id LEFT JOIN %s o ON l.message_id = o.message_id WHERE DATEDIFF(now(), o.created_at) > %s OR o.created_at IS NULL))', table('subscribers.id'), table('subscribers'), table('tracking_logs'), table('open_logs'), db_quote($keyword));
                        break;
                    case 'last_link_click_less_than_days':
                        // IMPORTANT: NO LEFT JOIN HERE
                        $conditions[] = sprintf('(%s IN (SELECT s.id FROM %s s JOIN %s l ON s.id = l.subscriber_id JOIN %s o ON l.message_id = o.message_id WHERE DATEDIFF(now(), o.created_at) < %s))', table('subscribers.id'), table('subscribers'), table('tracking_logs'), table('click_logs'), db_quote($keyword));
                        break;
                    case 'last_link_click_greater_than_days':
                        // IMPORTANT: LEFT JOIN HERE
                        $conditions[] = sprintf('(%s IN (SELECT s.id FROM %s s LEFT JOIN %s l ON s.id = l.subscriber_id LEFT JOIN %s o ON l.message_id = o.message_id WHERE DATEDIFF(now(), o.created_at) > %s OR o.created_at IS NULL))', table('subscribers.id'), table('subscribers'), table('tracking_logs'), table('click_logs'), db_quote($keyword));
                        break;
                    case 'created_date_greater':
                        $ts = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $condition->value)->timestamp;
                        $conditions[] = '(UNIX_TIMESTAMP('.\DB::getTablePrefix().'subscribers.created_at) > '.$ts.')';
                        break;
                    case 'created_date_less':
                        $ts = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $condition->value)->timestamp;
                        $conditions[] = '(UNIX_TIMESTAMP('.\DB::getTablePrefix().'subscribers.created_at) < '.$ts.')';
                        break;
                    case 'created_date_last_x_days':
                        $ts = \Carbon\Carbon::now()->subDays($condition->value)->timestamp;
                        $conditions[] = '(UNIX_TIMESTAMP('.\DB::getTablePrefix().'subscribers.created_at) >= '.$ts.')';
                        break;
                    default:
                        throw new \Exception("Unknown segment condition type (operator): ".$condition->operator);
                }
            }
        }

        //return $conditions;
        if ($this->matching == 'any') {
            $conditions = implode(' OR ', $conditions);
        } else {
            $conditions = implode(' AND ', $conditions);
        }

        return [
            'joins' => $joins,
            'conditions' => $conditions,
        ];
    }


    public function subscribers()
    {
        $query = $this->mailList->subscribers();
        $query->select('subscribers.*');

        // Get segment filter criteria
        $conditions = $this->getSubscribersConditions();

        // Apply the filter
        if (!empty($conditions['joins'])) {
            foreach ($conditions['joins'] as $joining) {
                $query = $query->leftJoin($joining['table'], function ($join) use ($joining) {
                    // Example: sf5da53d2f9c856.subscriber_id => m_subscribers.id
                    $join->on($joining['ons'][0][0], '=', $joining['ons'][0][1]);
                    if (isset($joining['ons'][1])) {
                        // Example: sf5da53d2f9c856.field_id = 6
                        $join->on($joining['ons'][1][0], '=', $joining['ons'][1][1]);
                    }
                });
            }
        }

        // var_dump($conditions['conditions']);die();
        if (!empty($conditions['conditions'])) {
            $query = $query->whereRaw('('.$conditions['conditions'].')');
        }

        // var_dump($query->toSql());die();

        return $query;
    }

    public function isSubscriberIncluded($subscriber)
    {
        return $this->subscribers()
                    ->where('uid', $subscriber->uid)
                    ->exists();
    }

    public function log($name, $customer, $add_datas = [])
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'list_id' => $this->maillist_id,
            'list_name' => $this->mailList->name,
        ];

        $data = array_merge($data, $add_datas);

        Log::create([
            'customer_id' => $customer->id,
            'type' => 'segment',
            'name' => $name,
            'data' => json_encode($data),
        ]);
    }

    public function subscribersCount($cache = false)
    {
        if ($cache) {
            return $this->readCache('SubscriberCount');
        }

        // return distinctCount($this->subscribers());
        return $this->subscribers()->count();
    }

    /**
     * Update segment cached data.
     */
    public function updateCacheDelayed()
    {
        dispatch(new \Acelle\Jobs\UpdateSegmentJob($this));
    }

    public function getCacheIndex()
    {
        // cache indexes
        return [
            'SubscriberCount' => function () {
                return $this->subscribersCount(false);
            },
        ];
    }

    public function updateConditions($conditions)
    {
        if ($this->id) {
            $this->segmentConditions()->delete();
        }
        foreach ($conditions as $key => $param) {
            $condition = new CampaignSegmentCondition();
            $condition->fill($param);

            if (strpos($condition->operator, 'created_date') === 0 && $condition->operator !== 'created_date_last_x_days') {
                $zone = $this->mailList->customer->getTimezone();
                $date = \Carbon\Carbon::createFromFormat('Y-m-d, H:i', $condition->value, $zone);
                $condition->value = $date->toDateTimeString();
            }

            $condition->segment_id = $this->id;
            $field = CampaignField::findByUid($param['field_id']);
            if ($field) {
                $condition->field_id = $field->id;
            } else {
                $condition->field_id = null;
            }

            $condition->save();
        }
    }
}

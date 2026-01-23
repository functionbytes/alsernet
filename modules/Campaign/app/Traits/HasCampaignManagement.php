<?php

namespace Modules\Campaign\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Modules\Campaign\Models\Template\Template;
use Modules\Subscriber\Models\Subscriber;

/**
 * Trait HasCampaignManagement
 *
 * Gestiona todas las funcionalidades relacionadas con campañas de email marketing,
 * incluyendo templates, subscribers, mail lists, automation, etc.
 */
trait HasCampaignManagement
{
    /**
     * Get user's contact
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo('App\Models\Contact');
    }

    /**
     * Get user's mail lists
     */
    public function mailLists(): HasMany
    {
        return $this->hasMany('Modules\Campaign\Entities\CampaignMaillist');
    }

    /**
     * Get user's mail lists (legacy)
     */
    public function lists(): HasMany
    {
        return $this->hasMany('modules\Mail\ModelsList');
    }

    /**
     * Get user's templates
     */
    public function templates(): HasMany
    {
        return $this->hasMany('App\Models\Template');
    }

    /**
     * Get user's campaigns
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany('Modules\Campaign\Entities\Campaign');
    }

    /**
     * Get sent campaigns
     */
    public function sentCampaigns(): HasMany
    {
        return $this->hasMany('Modules\Campaign\Entities\Campaign')
            ->where('status', '=', 'done')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get subscribers through mail lists
     */
    public function subscribers(): HasManyThrough
    {
        return $this->hasManyThrough('App\Models\Subscriber', 'modules\Mail\ModelsList');
    }

    /**
     * Get user's logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany('App\Models\Log')->orderBy('created_at', 'desc');
    }

    /**
     * Get tracking logs
     */
    public function trackingLogs(): HasMany
    {
        return $this->hasMany('App\Models\TrackingLog')->orderBy('created_at', 'asc');
    }

    /**
     * Get automations (version 2)
     */
    public function automation2s(): HasMany
    {
        return $this->hasMany('App\Models\Automation2');
    }

    /**
     * Get active automations
     */
    public function activeAutomation2s(): HasMany
    {
        return $this->hasMany('App\Models\Automation2')->where('status', 'active');
    }

    /**
     * Get user's forms
     */
    public function forms(): HasMany
    {
        return $this->hasMany('App\Models\Form');
    }

    /**
     * Get user's websites
     */
    public function websites(): HasMany
    {
        return $this->hasMany('App\Models\Website');
    }

    /**
     * Get user sources
     */
    public function sources(): HasMany
    {
        return $this->hasmany('App\Models\Source');
    }

    /**
     * Get blacklists
     */
    public function blacklists(): HasMany
    {
        return $this->hasMany('App\Models\Blacklist');
    }

    /**
     * Count lists
     */
    public function listsCount(): int
    {
        return $this->lists()->count();
    }

    /**
     * Count campaigns
     */
    public function campaignsCount(): int
    {
        return $this->campaigns()->count();
    }

    /**
     * Count automations
     */
    public function automationsCount(): int
    {
        return $this->automation2sCount();
    }

    /**
     * Count automation2s
     */
    public function automation2sCount(): int
    {
        return $this->automation2s()->count();
    }

    /**
     * Subscribers count by time
     */
    public static function subscribersCountByTime($begin, $end, $customer_id = null, $list_id = null, $status = null): int
    {
        $query = Subscriber::leftJoin('mail_lists', 'mail_lists.id', '=', 'subscribers.mail_list_id')
            ->leftJoin('customers', 'customers.id', '=', 'mail_lists.customer_id');

        if (isset($list_id)) {
            $query = $query->where('subscribers.mail_list_id', '=', $list_id);
        }
        if (isset($customer_id)) {
            $query = $query->where('customers.id', '=', $customer_id);
        }
        if (isset($status)) {
            $query = $query->where('subscribers.status', '=', $status);
        }

        $query = $query->where('subscribers.created_at', '>=', $begin)
            ->where('subscribers.created_at', '<=', $end);

        return $query->count();
    }

    /**
     * Count subscribers (with optional cache)
     */
    public function subscribersCount($cache = false): int
    {
        if ($cache) {
            return $this->readCache('SubscriberCount');
        }

        return $this->subscribers()->count();
    }

    /**
     * Get subscriber count by status
     */
    public function getSubscriberCountByStatus($status): int
    {
        $query = $this->subscribers()
            ->where('subscribers.status', $status)
            ->distinct('subscribers.email');

        return $query->count();
    }

    /**
     * Add email to blacklist
     */
    public function addEmaillToBlacklist($email): void
    {
        $email = trim(strtolower($email));

        if (\Acelle\Library\Tool::isValidEmail($email)) {
            $exist = $this->blacklists()->where('email', '=', $email)->count();
            if (! $exist) {
                $blacklist = new \App\Models\Blacklist;
                $blacklist->customer_id = $this->id;
                $blacklist->email = $email;
                $blacklist->save();
            }
        }
    }

    /**
     * Get contact for user
     */
    public function getContact()
    {
        if ($this->contact) {
            $contact = $this->contact;
        } else {
            $contact = new \App\Models\Contact([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->user->email,
            ]);
        }

        return $contact;
    }

    /**
     * Copy template as new template
     */
    public function copyTemplateAs(Template $template, $name)
    {
        $copy = $template->copy([
            'name' => $name,
            'customer_id' => $this->id,
        ]);

        return $copy;
    }

    /**
     * Get builder templates
     */
    public function getBuilderTemplates(): array
    {
        $result = [];

        $templates = \App\Models\Template::where('customer_id', '=', $this->id)
            ->orWhere(function ($query) {
                $query->notPreserved();
            })
            ->orderBy('customer_id')
            ->notPreserved()
            ->get();

        foreach ($templates as $template) {
            $result[] = [
                'name' => $template->name,
                'url' => '',
                'thumbnail' => $template->getThumbUrl(),
            ];
        }

        return $result;
    }

    /**
     * Get connected website select options
     */
    public function getConnectedWebsiteSelectOptions($long = true)
    {
        $query = $this->websites()->connected();

        $result = $query->orderBy('title')->get()->map(function ($item) use ($long) {
            if ($long) {
                return ['value' => $item->uid, 'text' => '<span class="fw-600">'.$item->title.'</span><br><span class="text-muted">'.$item->url.'</span>'];
            } else {
                return ['value' => $item->uid, 'text' => $item->title];
            }
        });

        return $result;
    }

    /**
     * Create abandoned email automation
     */
    public function createAbandonedEmailAutomation($store)
    {
        $auto = $this->automation2s()->create([
            'name' => 'Abandoned Cart Notification - Auto',
            'mail_list_id' => $store->getList()->id,
            'status' => 'inactive',
        ]);

        $email = new \App\Models\Email([
            'action_id' => '1000000001',
        ]);
        $email->customer_id = $this->id;
        $email->automation2_id = $auto->id;
        $email->save();

        $auto->data = json_encode([
            [
                'title' => 'Abandoned Cart Reminder',
                'id' => 'trigger',
                'type' => 'ElementTrigger',
                'child' => '1000000001',
                'options' => [
                    'key' => 'woo-abandoned-cart',
                    'type' => 'woo-abandoned-cart',
                    'source_uid' => $store->uid,
                    'wait' => '24_hour',
                    'init' => 'true',
                ],
            ], [
                'title' => 'Hey, you have an item left in cart',
                'id' => '1000000001',
                'type' => 'ElementAction',
                'child' => null,
                'options' => [
                    'init' => 'true',
                    'mail_uid' => $email->uid,
                ],
            ],
        ]);

        $auto->save();

        return $auto;
    }

    /**
     * Get abandoned email automation
     */
    public function getAbandonedEmailAutomation($store)
    {
        $auto = $this->automation2s()->where('mail_list_id', '=', $store->mail_list_id)->first();
        if (! $auto) {
            $auto = $this->createAbandonedEmailAutomation($store);
        }

        return $auto;
    }

    /**
     * Get cache index for campaigns
     */
    public function getCacheIndex(): array
    {
        return [
            'SubscriberCount' => function () {
                return $this->subscribersCount(false);
            },
            'SubscriberUsage' => function () {
                return $this->subscribersUsage(true);
            },
            'SubscribedCount' => function () {
                return $this->getSubscriberCountByStatus(Subscriber::STATUS_SUBSCRIBED);
            },
            'UnsubscribedCount' => function () {
                return $this->getSubscriberCountByStatus(Subscriber::STATUS_UNSUBSCRIBED);
            },
            'UnconfirmedCount' => function () {
                return $this->getSubscriberCountByStatus(Subscriber::STATUS_UNCONFIRMED);
            },
            'BlacklistedCount' => function () {
                return $this->getSubscriberCountByStatus(Subscriber::STATUS_BLACKLISTED);
            },
            'SpamReportedCount' => function () {
                return $this->getSubscriberCountByStatus(Subscriber::STATUS_SPAM_REPORTED);
            },
            'MailListSelectOptions' => function () {
                return $this->getMailListSelectOptions([], true);
            },
            'Bounce/FeedbackRate' => function () {
                return $this->getBounceFeedbackRate();
            },
        ];
    }

    /**
     * Get bounce/feedback rate
     */
    public function getBounceFeedbackRate()
    {
        $delivery = $this->trackingLogs()->count();

        if ($delivery == 0) {
            return 0;
        }

        $bounce = \DB::table('bounce_logs')
            ->leftJoin('tracking_logs', 'tracking_logs.message_id', '=', 'bounce_logs.message_id')
            ->count();
        $feedback = \DB::table('feedback_logs')
            ->leftJoin('tracking_logs', 'tracking_logs.message_id', '=', 'feedback_logs.message_id')
            ->count();

        return ($feedback + $bounce) / $delivery;
    }

    /**
     * Get mail list select options
     */
    public function getMailListSelectOptions($options = [], $cache = false)
    {
        $query = $this->mailLists();

        // Other list
        if (isset($options['other_list_of'])) {
            $query->where('id', '!=', $options['other_list_of']);
        }

        $result = $query->orderBy('name')->get()->map(function ($item) use ($cache) {
            return [
                'id' => $item->id,
                'value' => $item->uid,
                'text' => $item->name.' ('.$item->subscribersCount($cache).' '.strtolower(trans('messages.subscribers')).')',
            ];
        });

        return $result;
    }

    /**
     * Parse datetime with user timezone
     */
    public function parseDateTime($datetime, $fallback = false)
    {
        try {
            $dt = Carbon::parse($datetime, $this->timezone);
            $dt = $dt->timezone($this->timezone);
        } catch (\Exception $ex) {
            if ($fallback) {
                $dt = $this->parseDateTime('1900-01-01');
            } else {
                throw $ex;
            }
        }

        return $dt;
    }

    /**
     * Get current time in user timezone
     */
    public function getCurrentTime(): Carbon
    {
        return Carbon::now($this->timezone);
    }

    /**
     * Format current datetime
     */
    public function formatCurrentDateTime($name): string
    {
        return $this->formatDateTime($this->getCurrentTime(), $name);
    }

    /**
     * Format datetime
     */
    public function formatDateTime(Carbon $datetime, string $name): string
    {
        return format_datetime($datetime->timezone($this->getTimezone()), $name, $this->getLanguageCode());
    }

    /**
     * Create new default campaign
     */
    public function newDefaultCampaign()
    {
        $campaign = Campaign::newDefault();
        $campaign->customer_id = $this->id;

        return $campaign;
    }

    /**
     * Find product source
     */
    public function findProductSource($type)
    {
        $source = $this->sources()
            ->where('type', '=', $type)
            ->first();

        if (! $source) {
            $source = new \App\Models\Source;
            $source->customer_id = $this->id;
            $source->type = $type;
        }

        return $source;
    }

    /**
     * Get select options for sources
     */
    public function getSelectOptions($type = null)
    {
        $query = $this->sources();

        if ($type) {
            $query = $query->where('type', '=', $type);
        }

        return $query->get()->map(function ($source) {
            return ['text' => $source->getData()['data']['name'], 'value' => $source->uid];
        });
    }
}

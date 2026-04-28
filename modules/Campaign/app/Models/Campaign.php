<?php

namespace Modules\Campaign\Models;

use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Campaign\Jobs\SendMessage;
use Modules\Campaign\Library\BaseCampaign;
use Modules\Campaign\Library\Contracts\CampaignInterface;
use Modules\Campaign\Library\Contracts\HasTemplateInterface;
use Modules\Campaign\Library\Traits\HasTemplate;
use Modules\Campaign\Library\WebhookDispatcher;
use Modules\Campaign\Models\Template\Template;
use Modules\CampaignSendingServers\Library\RouletteWheel;
use Modules\CampaignSendingServers\Models\Blacklist;
use Modules\CampaignSendingServers\Models\SendingServer;
use Modules\Core\Models\Setting;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uid
 * @property string $type
 * @property string $title
 * @property string|null $subject
 * @property string|null $plain
 * @property string|null $from_email
 * @property string|null $from_name
 * @property string|null $reply_to
 * @property string|null $status
 * @property int|null $sign_dkim
 * @property int|null $track_open
 * @property int|null $track_click
 * @property int|null $resend
 * @property string|null $run_at
 * @property string|null $delivery_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $template_source
 * @property string|null $last_error
 * @property string|null $image
 * @property int|null $default_maillist_id
 * @property int|null $tracking_domain_id
 * @property int|null $running_pid
 * @property int|null $template_id
 * @property int $use_default_sending_server_from_email
 * @property string|null $preheader
 * @property-read Collection<int, CampaignLink> $campaignLinks
 * @property-read int|null $campaign_links_count
 * @property-read Collection<int, CampaignWebhook> $campaignWebhooks
 * @property-read int|null $campaign_webhooks_count
 * @property-read User|null $customer
 * @property-read CampaignMaillist|null $defaultMailList
 * @property-read Collection<int, JobMonitor> $jobMonitors
 * @property-read int|null $job_monitors_count
 * @property-read Collection<int, CampaignListsSegment> $listsSegments
 * @property-read int|null $lists_segments_count
 * @property-read Collection<int, CampaignMaillist> $mailLists
 * @property-read int|null $mail_lists_count
 * @property-read Template|null $template
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign byStatus($status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign filter($request)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign scheduled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign search($keyword)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereDefaultMaillistId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereDeliveryAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereFromEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereFromName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereLastError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign wherePlain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign wherePreheader($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereReplyTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereResend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereRunAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereRunningPid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereSignDkim($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereTemplateSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereTrackClick($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereTrackOpen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereTrackingDomainId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereUseDefaultSendingServerFromEmail($value)
 *
 * @mixin \Eloquent
 */
class Campaign extends BaseCampaign implements CampaignInterface, HasTemplateInterface
{
    use HasTemplate;
    use LogsActivity;
    use SoftDeletes;

    public const TYPE_REGULAR = 'regular';

    public const TYPE_PLAIN_TEXT = 'plain-text';

    public const DELIVERY_STATUS_FAILED = 'failed';

    public const DELIVERY_STATUS_SENT = 'sent';

    public const DELIVERY_STATUS_NEW = 'new';

    public const DELIVERY_STATUS_SKIPPED = 'skipped';

    public const DELIVERY_STATUS_BOUNCED = 'bounced';

    public const DELIVERY_STATUS_FEEDBACK = 'feedback';

    protected $table = 'campaigns';

    protected $dates = [
        'created_at',
        'updated_at',
        'run_at',
        'delivery_at',
    ];

    /**
     * Get campaign's default mail list.
     */
    public function defaultMailList()
    {
        return $this->belongsTo(CampaignMaillist::class, 'default_maillist_id');
    }

    /**
     * Get campaign's associated mail list.
     */
    public function mailLists()
    {
        return $this->belongsToMany(CampaignMaillist::class, 'campaign_lists_segments');
    }

    /**
     * Campaign has many campaign links.
     */
    public function campaignLinks()
    {
        return $this->hasMany('Modules\Campaign\Models\CampaignLink');
    }

    /**
     * Campaign has many campaign webhooks.
     */
    public function campaignWebhooks()
    {
        return $this->hasMany('Modules\Campaign\Models\CampaignWebhook');
    }

    /**
     * Get campaign's associated tracking domain.
     */
    public function trackingDomain()
    {
        return $this->belongsTo('Modules\CampaignSendingServers\Models\TrackingDomain', 'tracking_domain_id');
    }

    /**
     * Get campaign validation rules.
     */
    public function rules($request = null)
    {
        $rules = [
            'name' => 'required',
            'subject' => 'required',
            'from_email' => 'required|email',
            'from_name' => 'required',
            'reply_to' => 'required|email',
        ];

        if ($this->use_default_sending_server_from_email) {
            $rules['from_email'] = 'nullable|email';
        } else {
            $rules['from_email'] = 'required|email';
        }

        // tracking domain
        if (isset($request) && $request->custom_tracking_domain) {
            $rules['tracking_domain_uid'] = 'required';
        }

        return $rules;
    }

    /**
     * Get campaign tracking logs.
     */
    public function trackingLogs()
    {
        return $this->hasMany('Modules\Campaign\Models\CampaignTrackingLog');
    }

    public function jobMonitors()
    {
        return $this->hasMany(JobMonitor::class);
    }

    public function listsSegments()
    {
        return $this->hasMany('Modules\Campaign\Models\CampaignListsSegment');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "campaign:{$event}");
    }

    // ========================================================================
    // Métricas — agregados sobre tracking_logs + child logs.
    // Cacheadas por uid+key TTL 60s (bypassable con $cache=false).
    // ========================================================================

    protected function metric(string $key, Closure $callback, bool $cache = true)
    {
        if (! $cache) {
            return $callback();
        }

        return Cache::remember(
            "campaign:{$this->uid}:metric:{$key}",
            now()->addSeconds(60),
            $callback,
        );
    }

    public function subscribersCount(bool $cache = true): int
    {
        return $this->metric('subs', function () {
            $listIds = $this->mailLists()->pluck('campaign_maillists.id')->all();

            return DB::table('campaign_maillists_subscribers')
                ->whereIn('mail_list_id', $listIds)
                ->where('status', 'subscribed')
                ->distinct('subscriber_id')
                ->count('subscriber_id');
        }, $cache);
    }

    public function deliveredCount(bool $cache = true): int
    {
        return $this->metric('delivered', fn () => $this->trackingLogs()->where('status', 'sent')->count(), $cache);
    }

    public function deliveredRate(bool $cache = true): float
    {
        $total = $this->subscribersCount($cache);

        return $total > 0 ? round($this->deliveredCount($cache) * 100 / $total, 2) : 0.0;
    }

    public function uniqueOpenCount(bool $cache = true): int
    {
        return $this->metric('opens_unique', fn () => DB::table('campaign_open_logs')
            ->join('campaign_tracking_logs', 'campaign_tracking_logs.id', '=', 'campaign_open_logs.tracking_log_id')
            ->where('campaign_tracking_logs.campaign_id', $this->id)
            ->distinct('campaign_open_logs.tracking_log_id')
            ->count('campaign_open_logs.tracking_log_id'), $cache);
    }

    public function openRate(bool $cache = true): float
    {
        $delivered = $this->deliveredCount($cache);

        return $delivered > 0 ? round($this->uniqueOpenCount($cache) * 100 / $delivered, 2) : 0.0;
    }

    public function clickCount(bool $cache = true): int
    {
        return $this->metric('clicks', fn () => DB::table('campaign_click_logs')
            ->join('campaign_tracking_logs', 'campaign_tracking_logs.id', '=', 'campaign_click_logs.tracking_log_id')
            ->where('campaign_tracking_logs.campaign_id', $this->id)
            ->count(), $cache);
    }

    public function clickRate(bool $cache = true): float
    {
        $delivered = $this->deliveredCount($cache);

        return $delivered > 0 ? round($this->clickCount($cache) * 100 / $delivered, 2) : 0.0;
    }

    public function bounceCount(bool $cache = true): int
    {
        return $this->metric('bounces', fn () => $this->trackingLogs()->where('status', 'bounced')->count(), $cache);
    }

    public function unsubscribeCount(bool $cache = true): int
    {
        return $this->metric('unsubscribes', fn () => DB::table('campaign_unsubscribe_logs')
            ->join('campaign_tracking_logs', 'campaign_tracking_logs.id', '=', 'campaign_unsubscribe_logs.tracking_log_id')
            ->where('campaign_tracking_logs.campaign_id', $this->id)
            ->count(), $cache);
    }

    public function abuseFeedbackCount(bool $cache = true): int
    {
        return $this->metric('feedback', fn () => $this->trackingLogs()->where('status', 'feedback')->count(), $cache);
    }

    public function failedCount(bool $cache = true): int
    {
        return $this->metric('failed', fn () => $this->trackingLogs()->where('status', 'failed')->count(), $cache);
    }

    // ========================================================================
    // Pipeline de envío — métodos llamados por LoadCampaign / SendMessage
    // ========================================================================

    /**
     * Cache del RouletteWheel de servidores activos asociados a las listas
     * de esta campaña. Se inicializa lazy para no recalcular en cada batch.
     *
     * @var \Modules\Campaign\Library\RouletteWheel|null
     */
    protected $serversPool = null;

    /**
     * Construye el RouletteWheel de servidores disponibles para esta campaña.
     * Pondera por la columna `priority` del pivot maillist↔sending_server.
     */
    public function getServersPool(): RouletteWheel
    {
        if ($this->serversPool instanceof RouletteWheel) {
            return $this->serversPool;
        }

        $listIds = $this->mailLists()->pluck('campaign_maillists.id')->all();

        $pivots = DB::table('campaign_maillists_sending_servers')
            ->whereIn('mail_list_id', $listIds)
            ->select('sending_server_id', DB::raw('MAX(priority) AS weight'))
            ->groupBy('sending_server_id')
            ->get();

        $pool = new RouletteWheel;
        foreach ($pivots as $row) {
            $server = SendingServer::active()->find($row->sending_server_id);
            if ($server) {
                $pool->add($server->mapType(), max(1, (int) $row->weight));
            }
        }

        return $this->serversPool = $pool;
    }

    /**
     * Carga hasta $limit suscriptores pendientes y crea un SendMessage por cada uno.
     * Llamado desde LoadCampaign::handle().
     */
    public function loadDeliveryJobs(Closure $callback, int $limit = 100): void
    {
        $pool = $this->getServersPool();

        if ($pool->count() === 0) {
            $this->logger()->warning('No hay sending servers asociados a las listas de esta campaña.');

            return;
        }

        $subscribers = $this->subscribersToSend()
            ->limit($limit)
            ->get();

        if ($subscribers->isEmpty()) {
            $this->logger()->info('Sin suscriptores pendientes en este batch.');

            return;
        }

        $this->logger()->info(sprintf('Cargando %d suscriptores en este batch.', $subscribers->count()));

        foreach ($subscribers as $subscriber) {
            // Selección de servidor por roulette wheel (sin remover del pool).
            $server = $pool->select(false);
            if (! $server) {
                $this->logger()->warning("No hay servidor disponible para suscriptor {$subscriber->email}.");

                continue;
            }

            $job = new SendMessage($this, $subscriber, $server);
            $job->setStopOnError($this->stopOnError());
            $callback($job);
        }
    }

    /**
     * Query base de suscriptores pendientes de envío para esta campaña.
     *
     * Excluye:
     *   - Los que ya tienen un tracking_log para esta campaña con status != skipped
     *   - Los que están en blacklist global
     *   - Los que se han desuscrito de la lista
     *   - Los que han hecho hard bounce
     */
    public function subscribersToSend()
    {
        $query = $this->subscribersQuery();

        // 1) excluir los que ya recibieron este envío (excepto skipped → reintento)
        $query = $query->whereNotIn('campaign_subscribers.email', function ($sub) {
            $sub->select('email')
                ->from('campaign_tracking_logs')
                ->where('campaign_id', $this->id)
                ->where('status', '!=', self::DELIVERY_STATUS_SKIPPED);
        });

        // 2) excluir blacklist global
        if (\Schema::hasTable('campaign_sending_server_blacklists')) {
            $query = $query->whereNotIn(
                'campaign_subscribers.email',
                Blacklist::query()->select('email')
            );
        }

        return $query;
    }

    /**
     * Query base de TODOS los suscriptores asociados a esta campaña (vía sus
     * listas y, opcionalmente, segmentos del pivot campaign_lists_segments).
     *
     * Renombrado a subscribersQuery() para no colisionar con la magia de
     * relaciones dinámicas de Eloquent (`$campaign->subscribers`).
     */
    public function subscribersQuery()
    {
        $listIds = $this->mailLists()->pluck('campaign_maillists.id')->all();

        return CampaignSubscriber::query()
            ->select('campaign_subscribers.*')
            ->join('campaign_maillists_subscribers as cms', 'cms.subscriber_id', '=', 'campaign_subscribers.id')
            ->whereIn('cms.mail_list_id', $listIds)
            ->where('cms.status', 'subscribed')
            ->distinct();
    }

    /**
     * Persiste el resultado de cada SendMessage en campaign_tracking_logs y
     * dispara los webhooks asociados al evento (sent | failed).
     */
    public function trackMessage(array $result, $subscriber, SendingServer $server, string $msgId, ?string $triggerId = null): CampaignTrackingLog
    {
        $status = $result['status'] ?? self::DELIVERY_STATUS_FAILED;

        $log = CampaignTrackingLog::create([
            'campaign_id' => $this->id,
            'subscriber_id' => $subscriber->id ?? null,
            'sending_server_id' => $server->id,
            'email' => $subscriber->email ?? null,
            'message_id' => $msgId,
            'runtime_message_id' => $result['runtime_message_id'] ?? null,
            'status' => $status,
            'error' => $result['error'] ?? null,
            'trigger_id' => $triggerId,
        ]);

        WebhookDispatcher::emit($this, $status, [
            'subscriber_uid' => $subscriber->uid ?? null,
            'email' => $subscriber->email ?? null,
            'message_id' => $msgId,
            'runtime_message_id' => $result['runtime_message_id'] ?? null,
            'sending_server_uid' => $server->uid,
        ]);

        return $log;
    }

    /**
     * ¿Hay que abortar la campaña al primer error o saltarse el contacto fallido?
     * Configurable globalmente via Setting('campaign.stop_on_error').
     */
    public function stopOnError(): bool
    {
        if (! class_exists(Setting::class)) {
            return false;
        }

        return Setting::get('campaign.stop_on_error') === 'yes';
    }
}

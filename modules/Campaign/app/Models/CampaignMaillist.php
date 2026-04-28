<?php

namespace Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Campaign\Library\Traits\HasCache;
use Modules\Campaign\Library\Traits\TrackJobs;
use Modules\Campaign\Models\Automation\Automation;
use Modules\CampaignSendingServers\Models\SendingServer;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Lista de correo (mailing list).
 *
 * Versión slim del módulo. La versión completa heredada de Acelle (con
 * uploadCsv, dispatch jobs, métricas avanzadas, copy, export, etc.) vive
 * en CampaignMaillistLegacy.php para referencia. Si se necesita un método
 * concreto, portarlo aquí o a un trait dedicado en vez de extender del legacy.
 *
 * @property int $id
 * @property string $uid
 * @property string $name
 * @property string|null $description
 * @property string|null $from_email
 * @property string|null $from_name
 * @property string|null $default_subject
 * @property int $subscribe_confirmation
 * @property int $send_welcome_email
 * @property int $unsubscribe_notification
 * @property string|null $remind_message
 * @property array|null $options
 */
class CampaignMaillist extends Model
{
    use HasCache;
    use LogsActivity;
    use SoftDeletes;
    use TrackJobs;

    public const SOURCE_EMBEDDED_FORM = 'embedded-form';

    public const SOURCE_WEB = 'web';

    public const SOURCE_API = 'api';

    protected $table = 'campaign_maillists';

    protected $fillable = [
        'uid',
        'name',
        'description',
        'from_email',
        'from_name',
        'default_subject',
        'contact_company',
        'contact_state',
        'contact_city',
        'contact_zip',
        'contact_country_id',
        'contact_phone',
        'contact_email',
        'contact_url',
        'contact_address_1',
        'contact_address_2',
        'subscribe_confirmation',
        'send_welcome_email',
        'unsubscribe_notification',
        'remind_message',
        'subscribe_form_embed_code',
        'options',
    ];

    protected $casts = [
        'options' => 'array',
        'subscribe_confirmation' => 'integer',
        'send_welcome_email' => 'integer',
        'unsubscribe_notification' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $list): void {
            if (empty($list->uid)) {
                $list->uid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "campaign_maillist:{$event}");
    }

    // Relations

    public function fields()
    {
        return $this->hasMany(CampaignField::class, 'mail_list_id');
    }

    public function segments()
    {
        return $this->hasMany(CampaignSegment::class, 'mail_list_id');
    }

    public function automations()
    {
        return $this->hasMany(Automation::class, 'mail_list_id');
    }

    /**
     * Suscriptores del módulo (con su estado en esta lista). Pivot:
     * campaign_maillists_subscribers (status, tags, fechas).
     */
    public function subscribers()
    {
        return $this->belongsToMany(
            CampaignSubscriber::class,
            'campaign_maillists_subscribers',
            'mail_list_id',
            'subscriber_id',
        )->withPivot(['status', 'tags', 'subscribed_at', 'unsubscribed_at']);
    }

    /**
     * Suscriptores con status 'subscribed' (filtro por defecto del envío).
     */
    public function activeSubscribers()
    {
        return $this->subscribers()->wherePivot('status', 'subscribed');
    }

    public function campaigns()
    {
        return $this->belongsToMany(
            Campaign::class,
            'campaign_lists_segments',
            'mail_list_id',
            'campaign_id',
        );
    }

    /**
     * Sending servers asociados a la lista (cross-module). Pivot:
     * campaign_maillists_sending_servers (priority).
     */
    public function sendingServers()
    {
        return $this->belongsToMany(
            SendingServer::class,
            'campaign_maillists_sending_servers',
            'mail_list_id',
            'sending_server_id',
        )->withPivot('priority');
    }

    public function activeSendingServers()
    {
        return $this->sendingServers()->where('campaign_sending_servers.status', SendingServer::STATUS_ACTIVE);
    }

    // Scopes

    public function scopeSearch($query, ?string $keyword)
    {
        if (empty($keyword)) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword): void {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('description', 'like', "%{$keyword}%")
                ->orWhere('from_email', 'like', "%{$keyword}%");
        });
    }

    // Métricas básicas

    public function subscribeCount(): int
    {
        return $this->subscribers()->wherePivot('status', 'subscribed')->count();
    }

    public function unsubscribeCount(): int
    {
        return $this->subscribers()->wherePivot('status', 'unsubscribed')->count();
    }

    // Helpers para getCustomHeaders / prepareEmail

    /**
     * Devuelve el campo (CampaignField) cuyo tag coincide; null si no existe.
     */
    public function getFieldByTag(string $tag): ?CampaignField
    {
        return $this->fields()->where('tag', $tag)->first();
    }

    public function getEmailField(): ?CampaignField
    {
        return $this->getFieldByTag('EMAIL');
    }

    public function getFields()
    {
        return $this->fields;
    }
}

<?php

namespace Modules\CampaignSendingServers\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\CampaignSendingServers\Library\InMemoryRateTracker;
use Modules\CampaignSendingServers\Library\RateLimit;
use Modules\CampaignSendingServers\Library\RateTracker;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;

/**
 * Servidor de envío base. Cada proveedor (SES, SMTP, Sendmail, etc.) extiende
 * esta clase y sobrescribe send(), test() y opcionalmente
 * createSymfonyTransport().
 *
 * @property int $id
 * @property string $uid
 * @property string $name
 * @property string $type
 * @property string $status
 * @property string|null $host
 * @property string|null $smtp_username
 * @property string|null $smtp_password
 * @property int|null $smtp_port
 * @property string|null $smtp_protocol
 * @property string|null $sendmail_path
 * @property string|null $aws_access_key_id
 * @property string|null $aws_secret_access_key
 * @property string|null $aws_region
 * @property string|null $domain
 * @property string|null $api_key
 * @property string|null $api_secret_key
 * @property int|null $quota_value
 * @property int|null $quota_base
 * @property string|null $quota_unit
 * @property int|null $bounce_handler_id
 * @property int|null $feedback_loop_handler_id
 * @property string|null $default_from_email
 * @property string|null $username
 * @property array|null $options
 */
class SendingServer extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    public const DELIVERY_STATUS_SENT = 'sent';

    public const DELIVERY_STATUS_FAILED = 'failed';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const TYPE_AMAZON_API = 'amazon-api';

    public const TYPE_AMAZON_SMTP = 'amazon-smtp';

    public const TYPE_SENDGRID_API = 'sendgrid-api';

    public const TYPE_SENDGRID_SMTP = 'sendgrid-smtp';

    public const TYPE_MAILGUN_API = 'mailgun-api';

    public const TYPE_MAILGUN_SMTP = 'mailgun-smtp';

    public const TYPE_ELASTICEMAIL_API = 'elasticemail-api';

    public const TYPE_ELASTICEMAIL_SMTP = 'elasticemail-smtp';

    public const TYPE_SPARKPOST_API = 'sparkpost-api';

    public const TYPE_SPARKPOST_SMTP = 'sparkpost-smtp';

    public const TYPE_BREVO_API = 'brevo-api';

    public const TYPE_SENDMAIL = 'sendmail';

    public const TYPE_SMTP = 'smtp';

    protected $table = 'campaign_sending_servers';

    protected $fillable = [
        'uid',
        'name',
        'type',
        'status',
        'host',
        'smtp_username',
        'smtp_password',
        'smtp_port',
        'smtp_protocol',
        'sendmail_path',
        'aws_access_key_id',
        'aws_secret_access_key',
        'aws_region',
        'domain',
        'api_key',
        'api_secret_key',
        'webhook_signing_secret',
        'quota_value',
        'quota_base',
        'quota_unit',
        'bounce_handler_id',
        'feedback_loop_handler_id',
        'default_from_email',
        'username',
        'options',
    ];

    protected $casts = [
        'smtp_password' => 'encrypted',
        'aws_access_key_id' => 'encrypted',
        'aws_secret_access_key' => 'encrypted',
        'api_key' => 'encrypted',
        'api_secret_key' => 'encrypted',
        'webhook_signing_secret' => 'encrypted',
        'options' => 'array',
    ];

    /** Mapeo type → clase concreta. Lee desde config/campaign_sending_servers.php */
    public static function serverMapping(): array
    {
        $providers = config('campaign_sending_servers.providers', []);
        $map = [];
        foreach ($providers as $type => $meta) {
            if (! empty($meta['class'])) {
                $map[$type] = $meta['class'];
            }
        }

        return $map;
    }

    /** Devuelve la instancia concreta correspondiente al tipo. */
    public static function mapServerType(SendingServer $server): SendingServer
    {
        $map = self::serverMapping();
        if (! isset($map[$server->type])) {
            throw new Exception('Tipo de sending server desconocido: '.$server->type);
        }

        $class = $map[$server->type];

        $instance = $server->id
            ? $class::find($server->id)
            : new $class(['type' => $server->type]);

        $instance->fill($server->getAttributes());

        return $instance;
    }

    public function mapType(): SendingServer
    {
        return self::mapServerType($this);
    }

    /** Boot: generar uid automático si falta. */
    protected static function booted(): void
    {
        static::creating(function (SendingServer $server): void {
            if (empty($server->uid)) {
                $server->uid = (string) Str::uuid();
            }
            if (empty($server->status)) {
                $server->status = self::STATUS_ACTIVE;
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
            ->logExcept(['smtp_password', 'aws_secret_access_key', 'api_key', 'api_secret_key', 'webhook_signing_secret'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "sending_server:{$event}");
    }

    /** Relations */
    public function bounceHandler()
    {
        return $this->belongsTo(BounceHandler::class, 'bounce_handler_id');
    }

    public function feedbackLoopHandler()
    {
        return $this->belongsTo(FeedbackLoopHandler::class, 'feedback_loop_handler_id');
    }

    public function sendingDomains()
    {
        return $this->hasMany(SendingDomain::class, 'sending_server_id');
    }

    public function senders()
    {
        return $this->hasMany(Sender::class, 'sending_server_id');
    }

    /** Scopes */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeSearch($query, ?string $keyword)
    {
        if (empty($keyword)) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword): void {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('host', 'like', "%{$keyword}%")
                ->orWhere('default_from_email', 'like', "%{$keyword}%");
        });
    }

    /** Estado */
    public function enable(): void
    {
        $this->status = self::STATUS_ACTIVE;
        $this->save();
    }

    public function disable(): void
    {
        $this->status = self::STATUS_INACTIVE;
        $this->save();
    }

    /** Rate limits */
    public function getRateLimits(): array
    {
        $limits = [];

        if ($this->quota_value && $this->quota_value > 0) {
            $limits[] = new RateLimit(
                $this->quota_value,
                $this->quota_base ?? 1,
                $this->quota_unit ?? 'minute',
                "Cuota del servidor: {$this->quota_value} por {$this->quota_base} {$this->quota_unit}"
            );
        }

        return $limits;
    }

    public function getRateLimitTracker(): RateTracker|InMemoryRateTracker
    {
        $cacheStore = config('cache.default');
        if (in_array($cacheStore, ['redis', 'memcached'], true)) {
            return new InMemoryRateTracker(
                "sending-server-rate-{$this->uid}",
                $this->getRateLimits()
            );
        }

        $file = storage_path('app/quota/sending-server-rate-'.$this->uid);
        if (! is_dir(dirname($file))) {
            @mkdir(dirname($file), 0755, true);
        }

        return new RateTracker($file, $this->getRateLimits());
    }

    /** Operaciones de envío — sobrescritas por las subclases */
    public function send($email, array $params = []): array
    {
        throw new Exception('send() no implementado en '.static::class);
    }

    public function test(): bool
    {
        throw new Exception('test() no implementado en '.static::class);
    }

    public function createSymfonyTransport(): TransportInterface
    {
        throw new Exception('createSymfonyTransport() no implementado en '.static::class);
    }

    /** Dry-run para depuración: escribe el mensaje a /tmp en vez de enviarlo. */
    public function dryrun($email): array
    {
        $dir = sys_get_temp_dir().'/dryrun';
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $to = method_exists($email, 'getTo') ? ($email->getTo()[0] ?? null) : null;
        $toAddress = $to instanceof Address ? $to->getAddress() : (string) $to;
        $filename = 'dryrun-'.($toAddress ?: 'unknown').'-'.uniqid().'.eml';

        $payload = method_exists($email, 'toString') ? $email->toString() : (string) $email;
        @file_put_contents($dir.'/'.$filename, $payload);

        return ['status' => self::DELIVERY_STATUS_SENT, 'dryrun_file' => $dir.'/'.$filename];
    }

    /** Permite a esta clase aceptar dominios de envío adicionales. */
    public function allowOtherSendingDomains(): bool
    {
        return true;
    }

    /** ¿Permite Return-Path personalizado? */
    public function allowCustomReturnPath(): bool
    {
        return true;
    }

    public function getTypeName(): string
    {
        $providers = config('campaign_sending_servers.providers', []);

        return $providers[$this->type]['label'] ?? $this->type;
    }

    /** Opciones libres por servidor */
    public function getOption(string $key, $default = null)
    {
        return data_get($this->options ?? [], $key, $default);
    }

    public function setOption(string $key, $value): void
    {
        $opts = $this->options ?? [];
        data_set($opts, $key, $value);
        $this->options = $opts;
    }
}

<?php

namespace Modules\CampaignSendingServers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Dominio usado para reescribir URLs de tracking en los emails.
 *
 * Permite que los enlaces de tracking aparezcan bajo un dominio propio
 * (ej: track.example.com) en vez del dominio de la app, mejorando la
 * marca y la entregabilidad.
 *
 * @property int $id
 * @property string $uid
 * @property string $name
 * @property string $status pending|verified|failed
 * @property string|null $verification_method cname|host|caddy
 * @property string|null $verified_at
 */
class TrackingDomain extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_FAILED = 'failed';

    public const METHOD_CNAME = 'cname';

    public const METHOD_HOST = 'host';

    public const METHOD_CADDY = 'caddy';

    protected $table = 'campaign_sending_server_tracking_domains';

    protected $fillable = [
        'uid',
        'name',
        'status',
        'verification_method',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $domain): void {
            if (empty($domain->uid)) {
                $domain->uid = (string) Str::uuid();
            }
            if (empty($domain->status)) {
                $domain->status = self::STATUS_PENDING;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }
}

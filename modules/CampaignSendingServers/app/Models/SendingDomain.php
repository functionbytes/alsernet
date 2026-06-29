<?php

namespace Modules\CampaignSendingServers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Dominio de envío (DKIM/SPF/DMARC). Gestiona la verificación de la
 * autenticación del dominio para mejorar la entregabilidad.
 *
 * @property int $id
 * @property string $uid
 * @property string $name Dominio (ej: example.com)
 * @property int|null $sending_server_id
 * @property string $status pending|verified|failed
 * @property string|null $signing_enabled
 * @property string|null $dkim_selector
 * @property string|null $dkim_public_key
 * @property string|null $dkim_private_key
 * @property string|null $verified_at
 */
class SendingDomain extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_FAILED = 'failed';

    protected $table = 'campaign_sending_server_domains';

    protected $fillable = [
        'uid',
        'name',
        'sending_server_id',
        'status',
        'signing_enabled',
        'dkim_selector',
        'dkim_public_key',
        'dkim_private_key',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'signing_enabled' => 'boolean',
        'dkim_private_key' => 'encrypted',
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

    public function sendingServer()
    {
        return $this->belongsTo(SendingServer::class, 'sending_server_id');
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }
}

<?php

namespace Modules\CampaignSendingServers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Identidad de remitente verificada en un sending server (típicamente AWS SES).
 *
 * @property int $id
 * @property string $uid
 * @property string $email
 * @property string|null $name
 * @property int $sending_server_id
 * @property string $status pending|verified|failed
 * @property string|null $verified_at
 */
class Sender extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_FAILED = 'failed';

    protected $table = 'campaign_sending_server_senders';

    protected $fillable = [
        'uid',
        'email',
        'name',
        'sending_server_id',
        'status',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $sender): void {
            if (empty($sender->uid)) {
                $sender->uid = (string) Str::uuid();
            }
            if (empty($sender->status)) {
                $sender->status = self::STATUS_PENDING;
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

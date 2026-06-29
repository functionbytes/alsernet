<?php

namespace Modules\CampaignSendingServers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Handler de Feedback Loop (FBL). Como BounceHandler pero para reportes de
 * spam que envían los proveedores grandes (Yahoo, Hotmail) a un buzón
 * configurado al alta del programa FBL.
 *
 * @property int $id
 * @property string $uid
 */
class FeedbackLoopHandler extends Model
{
    public const TYPE_IMAP = 'imap';

    public const TYPE_POP3 = 'pop3';

    protected $table = 'campaign_sending_server_feedback_handlers';

    protected $fillable = [
        'uid',
        'name',
        'type',
        'host',
        'port',
        'protocol',
        'username',
        'password',
        'email',
    ];

    protected $casts = [
        'password' => 'encrypted',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $h): void {
            if (empty($h->uid)) {
                $h->uid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    public function sendingServers()
    {
        return $this->hasMany(SendingServer::class, 'feedback_loop_handler_id');
    }
}

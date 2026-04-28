<?php

namespace Modules\CampaignSendingServers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Handler de bounces (rebotes). Configura una conexión IMAP/POP3 a un buzón
 * que recibe notificaciones de no entrega para procesarlas y marcar
 * suscriptores como bounced.
 *
 * En Fase 1 sólo se persiste la configuración. El procesamiento real
 * (cron RunHandler) llega en Fase 2.
 *
 * @property int $id
 * @property string $uid
 * @property string $name
 * @property string $type imap|pop3
 * @property string|null $host
 * @property int|null $port
 * @property string|null $protocol
 * @property string|null $username
 * @property string|null $password
 * @property string|null $email
 */
class BounceHandler extends Model
{
    public const TYPE_IMAP = 'imap';

    public const TYPE_POP3 = 'pop3';

    protected $table = 'campaign_sending_server_bounce_handlers';

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
        return $this->hasMany(SendingServer::class, 'bounce_handler_id');
    }
}

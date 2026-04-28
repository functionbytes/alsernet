<?php

namespace Modules\CampaignSendingServers\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro de un bounce procesado por un BounceHandler.
 *
 * @property int $id
 * @property string $email
 * @property string $bounce_type hard|soft
 * @property string|null $message_id
 * @property string|null $description
 * @property int|null $bounce_handler_id
 */
class BounceLog extends Model
{
    public const TYPE_HARD = 'hard';

    public const TYPE_SOFT = 'soft';

    protected $table = 'campaign_sending_server_bounce_logs';

    protected $fillable = [
        'email',
        'bounce_type',
        'message_id',
        'description',
        'bounce_handler_id',
    ];

    public function bounceHandler()
    {
        return $this->belongsTo(BounceHandler::class, 'bounce_handler_id');
    }
}

<?php

namespace Modules\CampaignSendingServers\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lista negra global: emails que NO deben recibir envíos, sea cual sea
 * la campaña o lista. Cuando un suscriptor da feedback de spam, hace hard
 * bounce o se importa manualmente, su email cae aquí.
 *
 * @property int $id
 * @property string $email
 * @property string|null $reason
 * @property string|null $source manual|bounce|feedback|import
 */
class Blacklist extends Model
{
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_BOUNCE = 'bounce';

    public const SOURCE_FEEDBACK = 'feedback';

    public const SOURCE_IMPORT = 'import';

    protected $table = 'campaign_sending_server_blacklists';

    protected $fillable = [
        'email',
        'reason',
        'source',
    ];

    public static function isBlacklisted(string $email): bool
    {
        return self::where('email', $email)->exists();
    }
}

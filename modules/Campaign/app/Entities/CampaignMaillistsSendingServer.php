<?php

namespace Modules\Campaign\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $sending_server_id
 * @property int $maillist_id
 * @property int $fitness
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignMaillistsSendingServer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignMaillistsSendingServer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignMaillistsSendingServer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignMaillistsSendingServer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignMaillistsSendingServer whereFitness($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignMaillistsSendingServer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignMaillistsSendingServer whereMaillistId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignMaillistsSendingServer whereSendingServerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignMaillistsSendingServer whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CampaignMaillistsSendingServer extends Model
{
    protected $table = 'campaigns_maillists_sending_servers';
}

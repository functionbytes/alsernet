<?php

namespace Modules\Campaign\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uid
 * @property int $field_id
 * @property string $label
 * @property string $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CampaignField $field
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignFieldOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignFieldOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignFieldOption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignFieldOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignFieldOption whereFieldId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignFieldOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignFieldOption whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignFieldOption whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignFieldOption whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignFieldOption whereValue($value)
 *
 * @mixin \Eloquent
 */
class CampaignFieldOption extends Model
{
    use HasUid;

    protected $fillable = [
        'label',
        'value',
        'field_id',
    ];

    protected $table = 'campaign_field_options';

    public function field()
    {
        return $this->belongsTo('Modules\Campaign\Models\CampaignField');
    }
}

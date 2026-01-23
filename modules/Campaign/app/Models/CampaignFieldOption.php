<?php

namespace Modules\Campaign\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uid
 * @property int $field_id
 * @property string $label
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Modules\Campaign\Entities\CampaignField $field
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

    protected $table = 'campaigns_maillists_field_options';

    public function field()
    {
        return $this->belongsTo('Modules\Campaign\Entities\CampaignField');
    }
}

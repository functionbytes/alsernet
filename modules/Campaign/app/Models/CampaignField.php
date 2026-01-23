<?php

namespace Modules\Campaign\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uid
 * @property int $maillist_id
 * @property string $label
 * @property string $type
 * @property string $tag
 * @property string|null $default_value
 * @property int $visible
 * @property int $required
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Campaign\Entities\CampaignFieldOption> $fieldOptions
 * @property-read int|null $field_options_count
 * @property-read \Modules\Campaign\Entities\CampaignMaillist $mailList
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignField newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignField newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignField query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignField whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignField whereDefaultValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignField whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignField whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignField whereMaillistId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignField whereRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignField whereTag($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignField whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignField whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignField whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignField whereVisible($value)
 *
 * @mixin \Eloquent
 */
class CampaignField extends Model
{
    use HasUid;

    public const TYPE_DATE = 'date';

    public const TYPE_DATETIME = 'datetime';

    protected $fillable = [
        'maillist_id',
        'type',
        'label',
        'tag',
        'default_value',
        'visible',
        'required',
        'is_email',
    ];

    protected $table = 'campaigns_maillists_fields';

    public function mailList()
    {
        return $this->belongsTo('Modules\Campaign\Entities\CampaignMaillist', 'maillist_id');
    }

    public function fieldOptions()
    {
        return $this->hasMany('Modules\Campaign\Entities\CampaignFieldOption', 'maillist_id');
    }

    public static function formatTag($string)
    {
        return strtoupper(preg_replace('/[^0-9a-zA-Z_]/m', '', $string));
    }

    public function getSelectOptions()
    {
        $options = $this->fieldOptions->map(function ($item) {
            return ['value' => $item->value, 'text' => $item->label];
        });

        return $options;
    }

    public static function getControlNameByType($type)
    {
        if ($type == 'date') {
            return 'date';
        } elseif ($type == 'number') {
            return 'number';
        } elseif ($type == 'datetime') {
            return 'datetime';
        }

        return 'text';
    }
}

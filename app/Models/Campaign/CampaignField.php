<?php


namespace App\Models\Campaign;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;

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


    protected $table = "campaigns_maillists_fields";

    public function mailList()
    {
        return $this->belongsTo('App\Models\Campaign\CampaignMaillist', 'maillist_id');
    }

    public function fieldOptions()
    {
        return $this->hasMany('App\Models\Campaign\CampaignFieldOption', 'maillist_id');
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

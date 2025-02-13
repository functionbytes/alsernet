<?php


namespace App\Models\Campaign;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;

class CampaignFieldOption extends Model
{
    use HasUid;

    protected $fillable = [
        'label',
        'value',
        'field_id',
    ];

    protected $table = "campaigns_maillists_field_options";

    public function field()
    {
        return $this->belongsTo('App\Models\Campaign\CampaignField');
    }
}

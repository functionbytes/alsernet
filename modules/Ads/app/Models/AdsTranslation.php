<?php

namespace Modules\Ads\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdsTranslation extends Model
{
    protected $table = 'ad_translations';

    protected $fillable = ['ad_id', 'locale', 'name'];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ads::class, 'ad_id');
    }
}

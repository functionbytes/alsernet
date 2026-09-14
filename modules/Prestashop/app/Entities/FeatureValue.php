<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureValue extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_feature_value';

    protected $primaryKey = 'id_feature_value';

    public $timestamps = false;

    protected $fillable = [
        'id_feature',
        'value',
    ];

    protected $casts = [
        'id_feature' => 'integer',
    ];

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'id_feature');
    }
}

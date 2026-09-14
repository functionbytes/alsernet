<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RangeWeight extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_range_weight';

    protected $primaryKey = 'id_range_weight';

    public $timestamps = false;

    protected $fillable = [
        'id_range_weight',
        'id_carrier',
        'delimiter1',
        'delimiter2',
    ];

    protected $casts = [
        'id_range_weight' => 'integer',
        'id_carrier' => 'integer',
    ];

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'id_carrier');
    }
}

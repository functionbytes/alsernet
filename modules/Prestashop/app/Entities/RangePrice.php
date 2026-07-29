<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RangePrice extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_range_price';

    protected $primaryKey = 'id_range_price';

    public $timestamps = false;

    protected $fillable = [
        'id_range_price',
        'id_carrier',
        'delimiter1',
        'delimiter2',
    ];

    protected $casts = [
        'id_range_price' => 'integer',
        'id_carrier' => 'integer',
    ];

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'id_carrier');
    }
}

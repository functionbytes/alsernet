<?php

namespace Modules\Prestashop\Entities\Range;

use Illuminate\Database\Eloquent\Model;

class RangePrice extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_range_price';

    protected $primaryKey = 'id_range_price';

    public $timestamps = false;

    protected $fillable = [
        'id_carrier',
    ];

    protected $casts = [
        'id_carrier' => 'integer',
    ];
}

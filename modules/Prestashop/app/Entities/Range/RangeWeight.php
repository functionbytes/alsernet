<?php

namespace Modules\Prestashop\Entities\Range;

use Illuminate\Database\Eloquent\Model;

class RangeWeight extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_range_weight';

    protected $primaryKey = 'id_range_weight';

    public $timestamps = false;

    protected $fillable = [
        'id_carrier',
    ];

    protected $casts = [
        'id_carrier' => 'integer',
    ];
}

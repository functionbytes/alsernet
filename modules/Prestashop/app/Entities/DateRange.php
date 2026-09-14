<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class DateRange extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_date_range';

    protected $primaryKey = 'id_date_range';

    public $timestamps = false;

    protected $fillable = [
        'id_date_range',
        'time_start',
        'time_end',
    ];

    protected $casts = [
        'id_date_range' => 'integer',
    ];
}

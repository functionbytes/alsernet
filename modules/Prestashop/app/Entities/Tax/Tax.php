<?php

namespace Modules\Prestashop\Entities\Tax;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_tax';

    protected $primaryKey = 'id_tax';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'rate',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'rate' => 'float',
    ];
}

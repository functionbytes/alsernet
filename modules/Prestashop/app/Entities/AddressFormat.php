<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class AddressFormat extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_address_format';

    protected $primaryKey = 'id_country';

    public $timestamps = false;

    protected $fillable = [
        'id_address_format',
        'format',
    ];

    protected $casts = [
        'id_address_format' => 'integer',
    ];
}

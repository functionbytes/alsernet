<?php

namespace Modules\Prestashop\Entities\Webservice;

use Illuminate\Database\Eloquent\Model;

class WebserviceKey extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_webservice_account';

    protected $primaryKey = 'id_webservice_account';

    public $timestamps = false;

    protected $fillable = [
        'key',
        'description',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}

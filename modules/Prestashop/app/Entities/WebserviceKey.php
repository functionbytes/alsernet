<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class WebserviceKey extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_webservice_account';

    protected $primaryKey = 'id_webservice_account';

    public $timestamps = false;

    protected $fillable = [
        'id_webservice_account',
    ];

    protected $casts = [
        'id_webservice_account' => 'integer',
    ];
}

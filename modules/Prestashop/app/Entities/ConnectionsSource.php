<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class ConnectionsSource extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_connections_source';

    protected $primaryKey = 'id_connections_source';

    public $timestamps = false;

    protected $fillable = [
        'id_connections',
        'http_referer',
        'request_uri',
        'keywords',
        'date_add',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'id_connections' => 'integer',
    ];
}

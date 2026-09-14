<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;

class IpLocation extends Model
{
    protected $table = 'ip_locations';

    protected $fillable = [
        'ip_address',
        'country_code',
        'country_name',
        'region_code',
        'region_name',
        'city',
        'zipcode',
        'latitude',
        'longitude',
        'metro_code',
        'areacode',
    ];
}
